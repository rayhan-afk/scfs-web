<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\MerchantProduct;
use App\Models\MerchantProfile;
use App\Models\MahasiswaProfile;
use App\Models\Transaction;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    // POS State
    public $kategoriAktif = 'semua';
    public $search = '';
    
    // Cart State
    public array $cart = []; 
    
    // Payment State
    public $metode_pembayaran = 'digital'; // 'digital' (Beasiswa) atau 'tunai' (Umum)
    
    // Variabel untuk menampung String QR Enkripsi (Bisa di-paste manual / ditembak scanner USB)
    public $qr_scanned_string = ''; 
    public $uang_diterima = ''; 

    #[Computed]
    public function profile()
    {
        return MerchantProfile::where('user_id', Auth::id())->firstOrFail();
    }

    #[Computed]
    public function products()
    {
        $query = MerchantProduct::where('merchant_id', Auth::id())->where('is_tersedia', true);

        if ($this->search) {
            $query->where('nama_produk', 'like', '%' . $this->search . '%');
        }
        if ($this->kategoriAktif !== 'semua') {
            $query->where('kategori', $this->kategoriAktif);
        }

        return $query->get();
    }

    // --- CART LOGIC ---
    
    public function addToCart($id)
    {
        $product = MerchantProduct::find($id);
        if (!$product) return;

        if (isset($this->cart[$id])) {
            $this->cart[$id]['qty']++;
        } else {
            $this->cart[$id] = [
                'id' => $product->id,
                'nama' => $product->nama_produk,
                'harga_jual' => (float)$product->harga_jual,
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

    public function removeFromCart($id)
    {
        if (isset($this->cart[$id])) unset($this->cart[$id]);
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->reset(['qr_scanned_string', 'uang_diterima']);
    }

    #[Computed]
    public function cartSummary()
    {
        $total = 0;
        $items = 0;
        foreach ($this->cart as $item) {
            $total += $item['harga_jual'] * $item['qty'];
            $items += $item['qty'];
        }
        return ['total' => $total, 'items' => $items];
    }

    // --- PAYMENT CORE (HIGH SECURITY DECRYPTION) ---

    public function prosesPembayaran()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Keranjang masih kosong!');
            return;
        }

        // 1. Validasi Input Berdasarkan Metode
        if ($this->metode_pembayaran === 'digital') {
            $this->validate(['qr_scanned_string' => 'required|string'], ['qr_scanned_string.required' => 'Arahkan kursor ke kotak dan Scan QR Mahasiswa!']);
        } else {
            $this->validate(
                ['uang_diterima' => 'required|numeric|min:' . $this->cartSummary['total']],
                ['uang_diterima.min' => 'Uang tunai kurang dari total belanja!']
            );
        }

        try {
            DB::transaction(function () {
                $merchant = MerchantProfile::where('user_id', Auth::id())->lockForUpdate()->firstOrFail();
                
                // 2. SERVER-SIDE RECALCULATION (Mencegah Tampering Cart)
                $dbTotalAmount = 0;
                $dbTotalProfit = 0;
                $deskripsiTransaksi = [];

                foreach ($this->cart as $item) {
                    $realProduct = MerchantProduct::where('merchant_id', $merchant->user_id)->findOrFail($item['id']);
                    
                    $subtotalJual = $realProduct->harga_jual * $item['qty'];
                    $subtotalPokok = $realProduct->harga_pokok * $item['qty'];
                    
                    $dbTotalAmount += $subtotalJual;
                    $dbTotalProfit += ($subtotalJual - $subtotalPokok); 
                    
                    $deskripsiTransaksi[] = "{$item['qty']}x {$realProduct->nama_produk}";
                }

                $deskripsiFinal = implode(', ', $deskripsiTransaksi);
                
                // Kalkulasi Fee LKBB 
                $persentaseLKBB = $merchant->persentase_bagi_hasil ?? 0;
                $feeLKBB = ($dbTotalProfit * $persentaseLKBB) / 100;

                // 3. LOGIKA PERCABANGAN 
                if ($this->metode_pembayaran === 'digital') {
                    
                    // --- ARUS DIGITAL DENGAN DEKRIPSI QR (SOP BARU) ---
                    $mahasiswaUserId = null;

                    try {
                        // Buka Gembok Enkripsi QR Code
                        $decrypted = Crypt::decryptString($this->qr_scanned_string);
                        $payload = json_decode($decrypted, true);

                        // Validasi Format
                        if (!isset($payload['user_id']) || !isset($payload['exp'])) {
                            throw new \Exception('Format QR Code tidak dikenali.');
                        }

                        // Validasi Anti-Screenshot (Waktu Kedaluwarsa)
                        if (now()->timestamp > $payload['exp']) {
                            throw new \Exception('QR Code sudah kedaluwarsa (Lebih dari 2 menit). Minta mahasiswa refresh QR di aplikasinya!');
                        }

                        $mahasiswaUserId = $payload['user_id'];

                    } catch (DecryptException $e) {
                        throw new \Exception('QR Code Ditolak! Pastikan itu QR dari Aplikasi Mahasiswa SCFS.');
                    }

                    // Proses Tarik Data Mahasiswa yang Tervalidasi
                    $mahasiswa = MahasiswaProfile::where('user_id', $mahasiswaUserId)->lockForUpdate()->first();
                    
                    if (!$mahasiswa || $mahasiswa->status_bantuan !== 'disetujui') {
                        throw new \Exception('Akun mahasiswa tidak valid atau belum menerima bantuan.');
                    }
                    if ($mahasiswa->saldo < $dbTotalAmount) {
                        throw new \Exception("Saldo mahasiswa tidak cukup. Sisa: Rp " . number_format($mahasiswa->saldo, 0, ',', '.'));
                    }

                    // Potong Saldo MHS
                    $mahasiswa->decrement('saldo', $dbTotalAmount);

                    // Tambah Hak Digital Merchant 
                    $merchant->increment('saldo_token', ($dbTotalAmount - $feeLKBB));

                    // Rekam Transaksi Digital
                    Transaction::create([
                        'order_id'    => 'DIG-' . strtoupper(uniqid()),
                        'user_id'     => $mahasiswa->user_id,
                        'merchant_id' => $merchant->user_id,
                        'type'        => 'pembayaran_makanan', 
                        'total_amount'=> $dbTotalAmount,
                        'fee_lkbb'    => $feeLKBB,
                        'status'      => 'sukses',
                        'description' => '[QR] ' . $deskripsiFinal
                    ]);

                } else {
                    
                    // --- ARUS TUNAI ---
                    $merchant->increment('tagihan_setoran_tunai', $feeLKBB);
                    
                    Transaction::create([
                        'order_id'    => 'CSH-' . strtoupper(uniqid()),
                        'user_id'     => Auth::id(), 
                        'merchant_id' => $merchant->user_id,
                        'type'        => 'pembayaran_makanan_tunai',
                        'total_amount'=> $dbTotalAmount,
                        'fee_lkbb'    => $feeLKBB,
                        'status'      => 'sukses',
                        'description' => '[TUNAI] ' . $deskripsiFinal
                    ]);
                }
            });

            // 4. Feedback Success
            $kembalian = $this->metode_pembayaran === 'tunai' ? ((int)$this->uang_diterima - $this->cartSummary['total']) : 0;
            $msg = $this->metode_pembayaran === 'tunai' 
                    ? "Pembayaran Tunai Berhasil. Kembalian: Rp " . number_format($kembalian, 0, ',', '.') 
                    : "Pembayaran Digital (QR) Berhasil!";
            
            $this->clearCart();
            session()->flash('success', $msg);

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

<div class="h-[calc(100vh-5rem)] w-full flex flex-col md:flex-row bg-gray-100/50">
    
    {{-- BAGIAN KIRI: KATALOG PRODUK (2/3 Layar) --}}
    <div class="w-full md:w-2/3 h-full flex flex-col p-4 md:p-6 overflow-hidden">
        
        <div class="mb-4">
            <h2 class="text-2xl font-extrabold text-gray-900">Sistem Kasir Terpadu</h2>
            <p class="text-xs font-medium text-gray-500 mt-1">Pilih menu dari katalog untuk memproses transaksi.</p>
        </div>

        {{-- Filter Categories --}}
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide mb-4">
            @foreach(['semua' => 'Semua', 'makanan' => 'Makanan', 'minuman' => 'Minuman', 'barang_koperasi' => 'Koperasi'] as $val => $label)
                <button wire:click="$set('kategoriAktif', '{{ $val }}')" 
                    class="px-5 py-2.5 text-xs font-bold rounded-full whitespace-nowrap transition-all shadow-sm border 
                    {{ $kategoriAktif === $val ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Product Grid (Scrollable) --}}
        <div class="flex-1 overflow-y-auto scrollbar-hide pr-2">
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10">
                @forelse($this->products as $item)
                    <div wire:click="addToCart({{ $item->id }})" class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all cursor-pointer group overflow-hidden flex flex-col">
                        <div class="h-28 w-full bg-gray-100 relative overflow-hidden">
                            @if($item->foto_produk)
                                <img src="{{ asset('storage/' . $item->foto_produk) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-xs font-bold text-gray-900 leading-tight line-clamp-2 mb-1 group-hover:text-emerald-700 transition-colors">{{ $item->nama_produk }}</h3>
                            <p class="text-sm font-extrabold text-emerald-600">Rp{{ number_format($item->harga_jual, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-10 text-center">
                        <p class="text-gray-400 text-sm font-bold">Tidak ada produk ditemukan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- BAGIAN KANAN: KERANJANG (CART) & PEMBAYARAN (1/3 Layar) --}}
    <div class="w-full md:w-1/3 h-full bg-white border-l border-gray-200 shadow-xl flex flex-col relative z-10">
        
        {{-- Area Cart Items --}}
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-extrabold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    Nota Pesanan
                </h3>
                <button wire:click="clearCart" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 uppercase tracking-wider transition-colors">Kosongkan</button>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50/30">
                @if(empty($cart))
                    <div class="h-full flex flex-col items-center justify-center text-gray-400 opacity-70">
                        <svg class="w-16 h-16 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <p class="text-sm font-bold">Keranjang Kosong</p>
                    </div>
                @else
                    @foreach($cart as $id => $item)
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                            <div class="flex-1 pr-3">
                                <h4 class="text-xs font-bold text-gray-900 truncate">{{ $item['nama'] }}</h4>
                                <p class="text-[10px] font-bold text-emerald-600 mt-0.5">Rp{{ number_format($item['harga_jual'], 0, ',', '.') }}</p>
                            </div>
                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-1 border border-gray-200">
                                <button wire:click="decreaseQty({{ $id }})" class="w-6 h-6 flex items-center justify-center bg-white text-gray-600 rounded shadow-sm hover:bg-gray-100 font-bold focus:outline-none">-</button>
                                <span class="text-xs font-extrabold w-4 text-center">{{ $item['qty'] }}</span>
                                <button wire:click="addToCart({{ $id }})" class="w-6 h-6 flex items-center justify-center bg-emerald-100 text-emerald-700 rounded shadow-sm hover:bg-emerald-200 font-bold focus:outline-none">+</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Area Pembayaran (Payment Gateway) --}}
            <div class="p-5 bg-white border-t border-gray-200 shadow-[0_-10px_20px_rgba(0,0,0,0.03)]">
                
                {{-- Payment Method Tabs --}}
                <div class="flex p-1 bg-gray-100 rounded-xl mb-4">
                    <button wire:click="$set('metode_pembayaran', 'digital')" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all {{ $metode_pembayaran == 'digital' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">💳 Beasiswa (QR)</button>
                    <button wire:click="$set('metode_pembayaran', 'tunai')" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all {{ $metode_pembayaran == 'tunai' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">💵 Umum (Tunai)</button>
                </div>

                {{-- Input Area (Dynamic based on Tab) --}}
                <div class="space-y-3 mb-4">
                    @if($metode_pembayaran === 'digital')
                        <div class="relative">
                            {{-- Fitur Kasir Fisik: Jika mereka pakai alat Scanner Barcode USB, alat tsb akan otomatis menekan "Enter" (wire:keydown.enter) --}}
                            <input wire:model="qr_scanned_string" wire:keydown.enter="prosesPembayaran" type="text" placeholder="Klik disini & Tembak Scanner QR..." 
                                class="w-full py-3 px-4 text-sm font-bold text-gray-900 bg-blue-50/50 border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 text-center transition shadow-inner">
                            @error('qr_scanned_string') <span class="text-[10px] font-bold text-rose-500 text-center block mt-1">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-600 font-bold text-sm">Rp</span>
                                <input wire:model="uang_diterima" wire:keydown.enter="prosesPembayaran" type="number" placeholder="Uang Tunai Diterima" 
                                    class="w-full py-3 pl-12 pr-4 text-sm font-extrabold text-emerald-900 bg-emerald-50/50 border border-emerald-200 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition">
                            </div>
                            @error('uang_diterima') <span class="text-[10px] font-bold text-rose-500 block mt-1">{{ $message }}</span> @enderror
                            
                            {{-- Realtime Kembalian --}}
                            @if($uang_diterima && (int)$uang_diterima >= $this->cartSummary['total'] && $this->cartSummary['total'] > 0)
                                <div class="mt-2 text-right text-xs font-bold text-gray-600">
                                    Kembalian: <span class="text-orange-600">Rp{{ number_format((int)$uang_diterima - $this->cartSummary['total'], 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Grand Total & Submit --}}
                <div class="border-t border-gray-200 pt-3 mb-4 flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total</span>
                    <span class="text-2xl font-extrabold text-gray-900">Rp{{ number_format($this->cartSummary['total'], 0, ',', '.') }}</span>
                </div>

                <button wire:click="prosesPembayaran" wire:loading.attr="disabled"
                    @if(empty($cart)) disabled @endif
                    class="w-full py-4 text-sm font-extrabold text-white rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 focus:ring-4 disabled:opacity-50 disabled:cursor-not-allowed
                    {{ $metode_pembayaran == 'digital' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200 focus:ring-emerald-100' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200 focus:ring-emerald-100' }}">
                    
                    <span wire:loading.remove wire:target="prosesPembayaran">
                        {{ $metode_pembayaran == 'digital' ? 'PROSES QR BEASISWA' : 'PROSES BAYAR TUNAI' }}
                    </span>
                    <span wire:loading wire:target="prosesPembayaran">MEMPROSES...</span>
                </button>
                
                {{-- Global Error / Success Messages --}}
                @if(session('error'))
                    <div class="mt-3 text-[10px] font-bold text-rose-600 bg-rose-50 px-3 py-2 rounded-lg text-center border border-rose-200 animate-pulse">
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('success'))
                    <div class="mt-3 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg text-center border border-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>