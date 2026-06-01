<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\MerchantProduct;
use App\Models\MerchantProfile;
use App\Models\Transaction;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $kategoriAktif = 'semua';
    public $search = '';
    public array $cart = [];
    public $metode_pembayaran = 'digital';

    // STATE UNTUK QRIS / SCAN MAHASISWA
    public $showQrModal = false;
    public $pendingOrderId = null;
    public $qrPayloadString = '';

    #[Computed]
    public function profile()
    {
        return MerchantProfile::where('user_id', Auth::id())->firstOrFail();
    }

    #[Computed]
    public function products()
    {
        $query = MerchantProduct::where('merchant_id', Auth::id())
                    ->where('is_tersedia', 1)
                    ->where('stok', '>', 0);

        if ($this->search) $query->where('nama_produk', 'like', '%' . $this->search . '%');
        if ($this->kategoriAktif !== 'semua') $query->where('kategori', $this->kategoriAktif);

        return $query->get();
    }

    public function addToCart($id)
    {
        $product = MerchantProduct::find($id);
        if (!$product || $product->stok <= 0) {
            session()->flash('error', 'Stok barang ini sudah habis!');
            return;
        }

        if (isset($this->cart[$id])) {
            if ($this->cart[$id]['qty'] < $product->stok) {
                $this->cart[$id]['qty']++;
            } else {
                session()->flash('error', 'Maksimal pesanan hanya ' . $product->stok);
            }
        } else {
            $this->cart[$id] = [
                'id' => $product->id,
                'nama' => $product->nama_produk,
                'harga_jual' => (float)$product->harga_jual,
                'harga_pokok' => (float)$product->harga_pokok,
                'qty' => 1
            ];
        }
    }

    public function decreaseQty($id)
    {
        if (isset($this->cart[$id])) {
            if ($this->cart[$id]['qty'] > 1) {
                $this->cart[$id]['qty']--;
            } else {
                unset($this->cart[$id]);
            }
        }
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->reset(['pendingOrderId', 'showQrModal', 'qrPayloadString']);
    }

    #[Computed]
    public function cartSummary()
    {
        $total = 0;
        foreach ($this->cart as $item) {
            $total += $item['harga_jual'] * $item['qty'];
        }
        return ['total' => $total];
    }

    // =========================================================================
    // ALUR 1: PEMBAYARAN TUNAI (UANG FISIK)
    // =========================================================================
    public function prosesPembayaranTunai()
    {
        if (empty($this->cart)) return;

        try {
            DB::transaction(function () {
                $merchant = MerchantProfile::where('user_id', Auth::id())->lockForUpdate()->firstOrFail();

                $dbTotalAmount = 0; $dbTotalPokok = 0; $dbTotalProfit = 0;
                $deskripsiTransaksi = []; $cartSnapshot = [];

                foreach ($this->cart as $item) {
                    $realProduct = MerchantProduct::where('merchant_id', $merchant->user_id)->lockForUpdate()->findOrFail($item['id']);
                    if ($realProduct->stok < $item['qty']) throw new \Exception("Stok {$realProduct->nama_produk} habis.");

                    $realProduct->decrement('stok', $item['qty']);

                    $subtotalJual  = $item['harga_jual']  * $item['qty'];
                    $subtotalPokok = $item['harga_pokok'] * $item['qty'];

                    $dbTotalAmount += $subtotalJual;
                    $dbTotalPokok  += $subtotalPokok;
                    $dbTotalProfit += ($subtotalJual - $subtotalPokok);

                    $deskripsiTransaksi[] = "{$item['qty']}x {$realProduct->nama_produk}";
                    $cartSnapshot[] = [
                        'product_id'  => $realProduct->id,
                        'nama_produk' => $realProduct->nama_produk,
                        'qty'         => (int) $item['qty'],
                        'harga_jual'  => (float) $item['harga_jual'],
                        'harga_pokok' => (float) $item['harga_pokok'],
                    ];
                }

                $persentaseLKBB = $merchant->persentase_fee_merchant ?? 0;
                $feeLKBB        = ($dbTotalProfit * $persentaseLKBB) / 100;
                $tagihanKeLKBB  = $dbTotalPokok + $feeLKBB;

                $merchant->increment('tagihan_setoran_tunai', $tagihanKeLKBB);

                $trx = Transaction::create([
                    'order_id'      => 'UMM-' . strtoupper(uniqid()),
                    'user_id'       => Auth::id(),
                    'merchant_id'   => $merchant->user_id,
                    'type'          => 'pembayaran_makanan_tunai',
                    'total_amount'  => $dbTotalAmount,
                    'total_pokok'   => $dbTotalPokok,
                    'fee_lkbb'      => $feeLKBB,
                    'status'        => 'sukses',
                    'description'   => '[UMUM] ' . implode(', ', $deskripsiTransaksi),
                    'cart_snapshot' => $cartSnapshot,
                ]);

                // Audit ledger: bagi hasil + modal LKBB diakrukan sebagai klaim tagihan
                // (uang fisik belum masuk ke wallet — direalisasikan saat petugas terima setoran).
                $walletOperasional = \App\Models\Wallet::where('type', 'LKBB_OPERATIONAL')->first();
                if ($walletOperasional && $tagihanKeLKBB > 0) {
                    \App\Models\LedgerEntry::create([
                        'transaction_id' => $trx->id,
                        'wallet_id'      => $walletOperasional->id,
                        'entry_type'     => 'ACCRUAL_TUNAI',
                        'amount'         => $tagihanKeLKBB,
                        'balance_after'  => $walletOperasional->balance,
                    ]);
                }
            });

            $this->clearCart();
            session()->flash('success', 'Transaksi Tunai Berhasil!');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // =========================================================================
    // ALUR 2: PEMBAYARAN DIGITAL (GENERATE QR & TUNGGU MAHASISWA SCAN)
    // =========================================================================
    public function buatQrPembayaran()
    {
        if (empty($this->cart)) return;

        try {
            DB::transaction(function () {
                $merchant = MerchantProfile::where('user_id', Auth::id())->firstOrFail();

                $dbTotalAmount = 0; $dbTotalPokok = 0; $dbTotalProfit = 0;
                $deskripsiTransaksi = []; $cartSnapshot = [];

                foreach ($this->cart as $item) {
                    $realProduct = MerchantProduct::where('merchant_id', $merchant->user_id)->lockForUpdate()->findOrFail($item['id']);
                    if ($realProduct->stok < $item['qty']) throw new \Exception("Stok {$realProduct->nama_produk} tidak mencukupi.");

                    $realProduct->decrement('stok', $item['qty']);

                    $subtotalJual  = $item['harga_jual']  * $item['qty'];
                    $subtotalPokok = $item['harga_pokok'] * $item['qty'];

                    $dbTotalAmount += $subtotalJual;
                    $dbTotalPokok  += $subtotalPokok;
                    $dbTotalProfit += ($subtotalJual - $subtotalPokok);

                    $deskripsiTransaksi[] = "{$item['qty']}x {$realProduct->nama_produk}";
                    $cartSnapshot[] = [
                        'product_id'  => $realProduct->id,
                        'nama_produk' => $realProduct->nama_produk,
                        'qty'         => (int) $item['qty'],
                        'harga_jual'  => (float) $item['harga_jual'],
                        'harga_pokok' => (float) $item['harga_pokok'],
                    ];
                }

                $persentaseLKBB = $merchant->persentase_fee_merchant ?? 0;
                $feeLKBB        = ($dbTotalProfit * $persentaseLKBB) / 100;

                $this->pendingOrderId = 'DIG-' . strtoupper(uniqid());

                Transaction::create([
                    'order_id'      => $this->pendingOrderId,
                    'user_id'       => Auth::id(),
                    'merchant_id'   => $merchant->user_id,
                    'type'          => 'pembayaran_makanan',
                    'total_amount'  => $dbTotalAmount,
                    'total_pokok'   => $dbTotalPokok,
                    'fee_lkbb'      => $feeLKBB,
                    'status'        => 'pending',
                    'description'   => '[QR] ' . implode(', ', $deskripsiTransaksi),
                    'cart_snapshot' => $cartSnapshot,
                ]);

                $this->qrPayloadString = json_encode([
                    'order_id'     => $this->pendingOrderId,
                    'merchant_id'  => $merchant->user_id,
                    'total_amount' => $dbTotalAmount
                ]);

                $this->showQrModal = true;
            });

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function batalkanQrBayar()
    {
        if ($this->pendingOrderId) {
            DB::transaction(function () {
                $trx = Transaction::where('order_id', $this->pendingOrderId)
                            ->lockForUpdate()
                            ->first();

                if ($trx && $trx->status === 'pending') {
                    // Restore stok dari snapshot (lebih reliable daripada $this->cart in-memory)
                    $snapshot = $trx->cart_snapshot ?? [];
                    foreach ($snapshot as $item) {
                        MerchantProduct::where('id', $item['product_id'])
                            ->increment('stok', (int) $item['qty']);
                    }
                    $trx->delete();
                }
            });
            $this->pendingOrderId = null;
            $this->showQrModal = false;
        }
    }

    public function cekStatusPembayaranQr()
    {
        if (!$this->pendingOrderId) return;
        $trx = Transaction::where('order_id', $this->pendingOrderId)->first();
        if ($trx && $trx->status === 'sukses') {
            $this->showQrModal = false;
            $this->pendingOrderId = null;
            $this->clearCart();
            session()->flash('success', 'Pembayaran QR Beasiswa BERHASIL diterima! ✅');
        }
    }
}; ?>

<div class="py-6 px-4 md:px-6 w-full">
    
    {{-- PERBAIKAN: Kontainer Master dibungkus rapi seperti aplikasi mandiri --}}
    <div class="w-full h-[calc(100vh-6rem)] min-h-[600px] bg-white rounded-3xl shadow-sm border border-gray-200 flex flex-col md:flex-row overflow-hidden">
        
        {{-- ========================================================================= --}}
        {{-- BAGIAN KIRI: ETALASE MENU (FLEX-1)                                        --}}
        {{-- ========================================================================= --}}
        <div class="flex-1 flex flex-col h-full bg-slate-50/50">
            
            {{-- Header Kiri --}}
            <div class="px-6 py-5 bg-white border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 z-10 shrink-0">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                        <span class="text-[#059669]">🍔</span> Mesin Kasir
                    </h2>
                    <p class="text-[11px] font-bold text-gray-500 mt-0.5 uppercase tracking-wider">Point of Sale Merchant</p>
                </div>
                
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama menu..." 
                        class="w-full py-2.5 pl-9 pr-4 text-xs font-bold text-gray-700 bg-gray-100/80 border-transparent rounded-xl focus:border-[#059669] focus:bg-white focus:ring-2 focus:ring-emerald-100 transition shadow-inner">
                </div>
            </div>

            {{-- Filter Kategori (FIX: Flex-wrap agar tidak ada scrollbar jelek) --}}
            <div class="px-6 py-3 bg-white border-b border-gray-100 shrink-0 shadow-[0_4px_10px_rgba(0,0,0,0.02)] z-10">
                <div class="flex flex-wrap gap-2">
                    @foreach(['semua' => 'Semua', 'makanan' => '🍔 Makanan', 'minuman' => '🥤 Minuman', 'barang_koperasi' => '👕 Koperasi', 'lainnya' => '📦 Lainnya'] as $val => $label)
                        <button wire:click="$set('kategoriAktif', '{{ $val }}')" 
                            class="px-4 py-2 text-[11px] font-black rounded-lg transition-all border 
                            {{ $kategoriAktif === $val ? 'bg-[#059669] text-white border-emerald-700 shadow-sm' : 'bg-white border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Grid Produk --}}
            <div class="flex-1 overflow-y-auto p-6 scrollbar-hide">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-10">
                    @forelse($this->products as $item)
                        <div wire:click="addToCart({{ $item->id }})" class="bg-white rounded-[20px] border border-gray-200 shadow-sm hover:shadow-lg hover:border-[#059669] transition-all cursor-pointer group overflow-hidden flex flex-col relative">
                            {{-- Label Stok --}}
                            <div class="absolute top-2 left-2 z-10">
                                <span class="bg-white/90 backdrop-blur-md px-2 py-1 rounded shadow-sm text-[9px] font-black text-gray-800 border border-gray-100">
                                    Stok: {{ $item->stok }}
                                </span>
                            </div>

                            {{-- Foto Produk --}}
                            <div class="h-32 w-full bg-slate-100 relative overflow-hidden">
                                @if($item->foto_produk)
                                    <img src="{{ asset('storage/' . $item->foto_produk) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Info Produk --}}
                            <div class="p-3.5 flex-1 flex flex-col justify-between bg-white">
                                <h3 class="text-xs font-black text-gray-800 leading-snug line-clamp-2 mb-2 group-hover:text-[#059669] transition-colors">{{ $item->nama_produk }}</h3>
                                <p class="text-sm font-black text-[#059669]">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-16 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200">
                            <div class="text-5xl mb-3 opacity-30">🛒</div>
                            <p class="text-gray-500 text-sm font-bold">Menu tidak ditemukan atau stok habis.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- BAGIAN KANAN: STRUK PEMBAYARAN (FIXED WIDTH)                              --}}
        {{-- ========================================================================= --}}
        <div class="w-full md:w-[380px] bg-white border-l border-gray-200 flex flex-col h-full z-20 shrink-0 shadow-[-10px_0_20px_rgba(0,0,0,0.03)]">
            
            {{-- Header Keranjang --}}
            <div class="px-5 py-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                <h3 class="font-black text-gray-900 flex items-center gap-2 text-sm uppercase tracking-wider">
                    🧾 Nota Pesanan
                </h3>
                <button wire:click="clearCart" class="text-[10px] font-black text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-100 px-2.5 py-1 rounded-md transition-colors">KOSONGKAN</button>
            </div>

            {{-- Daftar Pesanan (Scrollable) --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/50 relative">
                @if(empty($cart))
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 opacity-60">
                        <svg class="w-16 h-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <p class="text-xs font-bold uppercase tracking-widest">Belum Ada Pesanan</p>
                    </div>
                @else
                    @foreach($cart as $id => $item)
                        <div class="flex flex-col bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden group">
                            <div class="p-3.5 flex justify-between items-start">
                                <div class="flex-1 pr-3">
                                    <h4 class="text-xs font-black text-gray-900 leading-tight">{{ $item['nama'] }}</h4>
                                    <p class="text-[10px] font-black text-[#059669] mt-1">Rp {{ number_format($item['harga_jual'], 0, ',', '.') }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-black text-gray-900">Rp {{ number_format($item['harga_jual'] * $item['qty'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50/80 px-3.5 py-2.5 border-t border-gray-100 flex justify-end">
                                <div class="flex items-center gap-1 bg-white rounded-lg p-1 border border-gray-200 shadow-sm">
                                    <button wire:click="decreaseQty({{ $id }})" class="w-7 h-7 flex items-center justify-center text-rose-500 hover:bg-rose-50 rounded-md font-bold transition">-</button>
                                    <span class="text-xs font-black w-6 text-center text-gray-900">{{ $item['qty'] }}</span>
                                    <button wire:click="addToCart({{ $id }})" class="w-7 h-7 flex items-center justify-center bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-md font-bold transition">+</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Area Checkout Bawah (Fixed) --}}
            <div class="mt-auto bg-white border-t border-gray-200 p-5 shrink-0 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                
                {{-- Tab Pilihan Pembayaran --}}
                <div class="flex p-1 bg-gray-100 rounded-xl mb-5">
                    <button wire:click="$set('metode_pembayaran', 'digital')" class="flex-1 py-2.5 text-[11px] font-black tracking-wide rounded-lg transition-all {{ $metode_pembayaran == 'digital' ? 'bg-white text-blue-600 shadow-sm border border-gray-200/50' : 'text-gray-500 hover:text-gray-800' }}">📱 QR BEASISWA</button>
                    <button wire:click="$set('metode_pembayaran', 'tunai')" class="flex-1 py-2.5 text-[11px] font-black tracking-wide rounded-lg transition-all {{ $metode_pembayaran == 'tunai' ? 'bg-[#059669] text-white shadow-sm border border-emerald-700' : 'text-gray-500 hover:text-gray-800' }}">💵 TUNAI UMUM</button>
                </div>

                {{-- Info Pembayaran --}}
                <div class="space-y-4 mb-5 min-h-[4.5rem]">
                    @if($metode_pembayaran === 'tunai')
                        <div class="flex items-center gap-3 p-3.5 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                            <div class="p-2 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-emerald-800 leading-relaxed">Terima uang tunai sesuai total tagihan. Klik tombol di bawah untuk mencatat transaksi.</p>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl">
                            <div class="p-2 bg-blue-100 text-blue-600 rounded-lg shrink-0">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-blue-800 leading-relaxed">Minta mahasiswa untuk melakukan Scan QR menggunakan aplikasi mobile SCFS mereka.</p>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-end mb-4 border-t border-gray-100 pt-4">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Tagihan</span>
                    <span class="text-3xl font-black text-gray-900 leading-none tracking-tight">Rp{{ number_format($this->cartSummary['total'], 0, ',', '.') }}</span>
                </div>

                @if($metode_pembayaran === 'digital')
                    <button wire:click="buatQrPembayaran" wire:loading.attr="disabled"
                        @if(empty($cart)) disabled @endif
                        class="w-full py-4 text-sm font-black tracking-widest text-white rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 focus:ring-4 disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 shadow-blue-200/50 focus:ring-blue-100">
                        <span wire:loading.remove wire:target="buatQrPembayaran">TAMPILKAN QR BAYAR</span>
                        <span wire:loading wire:target="buatQrPembayaran">MEMBUAT QR...</span>
                    </button>
                @else
                    <button wire:click="prosesPembayaranTunai" wire:loading.attr="disabled"
                        @if(empty($cart)) disabled @endif
                        class="w-full py-4 text-sm font-black tracking-widest text-white rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 focus:ring-4 disabled:opacity-50 disabled:cursor-not-allowed bg-[#059669] hover:bg-emerald-700 shadow-emerald-200/50 focus:ring-emerald-100">
                        <span wire:loading.remove wire:target="prosesPembayaranTunai">TERIMA UANG TUNAI</span>
                        <span wire:loading wire:target="prosesPembayaranTunai">MEMPROSES...</span>
                    </button>
                @endif
                
                @if(session('error'))
                    <div class="mt-4 text-[10px] font-bold text-rose-600 bg-rose-50 px-3 py-2.5 rounded-lg text-center border border-rose-100 shadow-sm">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="mt-4 text-[10px] font-bold text-[#059669] bg-emerald-50 px-3 py-2.5 rounded-lg text-center border border-emerald-100 shadow-sm">{{ session('success') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- MODAL QR CODE (Z-INDEX SUPER TINGGI AGAR DI ATAS SIDEBAR) --}}
    {{-- ========================================================= --}}
    @if($showQrModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm" wire:poll.2s="cekStatusPembayaranQr">
        <div class="bg-white rounded-[30px] w-full max-w-sm shadow-2xl overflow-hidden flex flex-col text-center p-8 relative transform transition-all">
            
            <h3 class="font-black text-blue-900 text-xl mb-1">Scan untuk Membayar</h3>
            <p class="text-xs font-bold text-gray-500 mb-6">Arahkan kamera HP ke QR Code ini</p>

            {{-- QR Code API --}}
            <div class="p-4 bg-white rounded-3xl border-2 border-dashed border-blue-200 inline-block mx-auto mb-6 shadow-inner">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrPayloadString) }}&margin=0" class="w-52 h-52 mx-auto rounded-xl" alt="QR Code">
            </div>

            <p class="text-[10px] font-bold text-gray-400 font-mono mb-2 bg-gray-50 px-2 py-1 rounded inline-block mx-auto border border-gray-100">ID: {{ $pendingOrderId }}</p>
            <h2 class="text-4xl font-black text-gray-900 mb-6 tracking-tighter">Rp{{ number_format($this->cartSummary['total'], 0, ',', '.') }}</h2>

            <div class="flex items-center justify-center gap-2 text-blue-600 text-[11px] font-black uppercase tracking-wider mb-6 bg-blue-50 px-4 py-3 rounded-xl border border-blue-100">
                <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Menunggu Pembayaran...
            </div>

            <button wire:click="batalkanQrBayar" class="w-full py-3.5 bg-white hover:bg-rose-50 border-2 border-gray-100 text-gray-400 hover:text-rose-600 hover:border-rose-200 text-sm font-black rounded-xl transition-all shadow-sm">
                Batalkan Transaksi
            </button>
        </div>
    </div>
    @endif

</div>