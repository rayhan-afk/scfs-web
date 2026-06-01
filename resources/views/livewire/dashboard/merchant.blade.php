<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\MerchantProfile;
use App\Models\MerchantProduct;
use App\Models\Transaction;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderDetail;
use App\Models\User;
use App\Notifications\NewMerchantSubmission;
use Carbon\Carbon;

new 
#[Layout('layouts.app')] 
class extends Component {
    use WithFileUploads;

    public $nama_kantin, $nama_pemilik, $nik, $no_hp, $lokasi_blok;

    // Usulan bagi hasil (persentase) yang merchant tawarkan ke LKBB.
    public $usulan_fee_merchant = null;

    public $nama_bank = 'BCA';
    public $bank_lainnya = '';
    public $no_rekening = '';
    public $foto_ktp, $foto_kantin;

    public string $chartFilter = 'month'; 

    public function mount()
    {
        $profile = MerchantProfile::where('user_id', Auth::id())->first();
        
        if ($profile) {
            $this->nama_kantin = $profile->nama_kantin;
            $this->nama_pemilik = $profile->nama_pemilik;
            $this->nik = $profile->nik;
            $this->no_hp = $profile->no_hp;
            $this->lokasi_blok = $profile->lokasi_blok;
            $standardBanks = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BJB', 'GoPay', 'OVO'];
                if (in_array($profile->nama_bank, $standardBanks)) {
                    $this->nama_bank = $profile->nama_bank;
                } elseif (!empty($profile->nama_bank)) {
                    $this->nama_bank = 'Lainnya';
                    $this->bank_lainnya = $profile->nama_bank;
                }

                $this->no_rekening = $profile->no_rekening;
            $this->usulan_fee_merchant = $profile->usulan_fee_merchant;
        } else {
            $this->nama_pemilik = Auth::user()->name;
        }
    }

    #[Computed]
    public function profile()
    {
        return MerchantProfile::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'status_verifikasi'       => 'belum_melengkapi',
                'nama_pemilik'            => Auth::user()->name,
                'persentase_fee_merchant' => 0, 
                'saldo_token'             => 0,
                'tagihan_setoran_tunai'   => 0,
                'status_toko'             => 'tutup',
            ]
        );
    }

    #[Computed]
    public function statHariIni()
    {
        $today = Carbon::today();

        $baseQuery = Transaction::where('merchant_id', Auth::id())
            ->whereIn('status', ['sukses', 'lunas'])
            ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai'])
            ->whereDate('created_at', $today);

        $penjualanTotal = (clone $baseQuery);
        $penjualanTunai = (clone $baseQuery)->where('type', 'pembayaran_makanan_tunai');

        return [
            'total_nominal'      => $penjualanTotal->sum('total_amount'),
            'total_pesanan'      => $penjualanTotal->count(),
            'uang_laci_hari_ini' => $penjualanTunai->sum('total_amount')
        ];
    }

    // Profit tunai cash di laci kantin (hari ini + bulan ini).
    // = total_amount - total_pokok - fee_lkbb untuk type tunai.
    // Display-only, TIDAK masuk saldo_token (sudah dipegang kantin sebagai uang fisik).
    #[Computed]
    public function profitTunai(): array
    {
        $now = Carbon::now();

        $baseQuery = Transaction::where('merchant_id', Auth::id())
            ->whereIn('status', ['sukses', 'lunas'])
            ->where('type', 'pembayaran_makanan_tunai');

        $hariIni = (clone $baseQuery)
            ->whereDate('created_at', Carbon::today())
            ->get()
            ->sum(fn($t) => (float) $t->total_amount - (float) $t->total_pokok - (float) $t->fee_lkbb);

        $bulanIni = (clone $baseQuery)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->get()
            ->sum(fn($t) => (float) $t->total_amount - (float) $t->total_pokok - (float) $t->fee_lkbb);

        return [
            'hari_ini'  => $hariIni,
            'bulan_ini' => $bulanIni,
        ];
    }

    // MENGHITUNG TOTAL TITIPAN LKBB BERDASARKAN FISIK BARANG SAAT INI
    #[Computed]
    public function totalModalLKBB()
    {
        // Nilai aset LKBB = sisa porsi di etalase * harga modalnya
        return MerchantProduct::where('merchant_id', Auth::id())
                ->selectRaw('SUM(stok * harga_pokok) as total_aset')
                ->value('total_aset') ?? 0;
    }

    public function getChartData()
    {
        $query = Transaction::where('merchant_id', Auth::id())
            ->whereIn('status', ['sukses', 'lunas'])
            ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']); 

        $labels = [];
        $series = [];

        if ($this->chartFilter === 'today') {
            $txs = (clone $query)->whereDate('created_at', Carbon::today())->get();
            $grouped = $txs->groupBy(function($item) {
                return Carbon::parse($item->created_at)->format('H'); 
            });

            for ($i = 6; $i <= 21; $i++) {
                $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
                $labels[] = $hour . ':00';
                $series[] = $grouped->has($hour) ? (int) $grouped->get($hour)->sum('total_amount') : 0;
            }
        } elseif ($this->chartFilter === 'month') {
            $now = Carbon::now();
            $txs = (clone $query)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->get();
            $grouped = $txs->groupBy(function($item) {
                return Carbon::parse($item->created_at)->format('j'); 
            });

            for ($i = 1; $i <= $now->daysInMonth; $i++) {
                $labels[] = $i . ' ' . $now->format('M');
                $series[] = $grouped->has((string)$i) ? (int) $grouped->get((string)$i)->sum('total_amount') : 0;
            }
        } elseif ($this->chartFilter === 'year') {
            $now = Carbon::now();
            $txs = (clone $query)->whereYear('created_at', $now->year)->get();
            $grouped = $txs->groupBy(function($item) {
                return Carbon::parse($item->created_at)->format('n'); 
            });

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            for ($i = 1; $i <= 12; $i++) {
                $labels[] = $months[$i - 1];
                $series[] = $grouped->has((string)$i) ? (int) $grouped->get((string)$i)->sum('total_amount') : 0;
            }
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function setFilter($filter)
    {
        $this->chartFilter = $filter;
        $data = $this->getChartData();
        $this->dispatch('update-chart', labels: $data['labels'], series: $data['series']);
    }

    // RIWAYAT PENJUALAN KASIR
    #[Computed]
    public function riwayatTransaksi()
    {
        return Transaction::with('user')
            ->where('merchant_id', Auth::id())
            ->whereIn('status', ['sukses', 'lunas'])
            ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']) 
            ->latest()
            ->take(3)
            ->get();
    }

    // FITUR BARU: RIWAYAT PEMESANAN BARANG (PO) KE LKBB
    #[Computed]
    public function riwayatPO()
    {
        return SupplyOrder::where('merchant_id', Auth::id())
            ->latest()
            ->take(3)
            ->get();
    }

    public function submitOnboarding()
    {
        $this->validate([
            'nama_kantin'    => 'required|string|max:255',
            'nama_pemilik'   => 'required|string|max:255',
            'nik'            => 'required|digits:16',
            'no_hp'          => 'required|string|max:20',
            'lokasi_blok'    => 'required|string|max:255',
            'nama_bank'      => 'required|string',
            'bank_lainnya'   => 'required_if:nama_bank,Lainnya',
            'no_rekening'    => 'required|numeric',
            'usulan_fee_merchant' => 'required|numeric|min:0|max:100',
            'foto_ktp'       => $this->profile->foto_ktp ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'foto_kantin'    => $this->profile->foto_kantin ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ], [
            'usulan_fee_merchant.required' => 'Usulan bagi hasil wajib diisi.',
            'usulan_fee_merchant.numeric'  => 'Usulan harus berupa angka.',
            'usulan_fee_merchant.max'      => 'Usulan maksimal 100%.',
        ]);

        $updateData = [
            'nama_kantin'         => $this->nama_kantin,
            'nama_pemilik'        => $this->nama_pemilik,
            'nik'                 => $this->nik,
            'no_hp'               => $this->no_hp,
            'lokasi_blok'         => $this->lokasi_blok,
            'usulan_fee_merchant' => $this->usulan_fee_merchant,
            'status_verifikasi'   => 'menunggu_review',
            'nama_bank'           => $this->nama_bank === 'Lainnya' ? $this->bank_lainnya : $this->nama_bank,
            'no_rekening'         => $this->no_rekening,
        ];

        if ($this->foto_ktp && !is_string($this->foto_ktp)) {
            $updateData['foto_ktp'] = $this->foto_ktp->store('merchants/ktp', 'public');
        }
        if ($this->foto_kantin && !is_string($this->foto_kantin)) {
            $updateData['foto_kantin'] = $this->foto_kantin->store('merchants/kantin', 'public');
        }

        $this->profile->update($updateData);
        $lkbbUsers = User::where('role', 'lkbb')->get();

        foreach ($lkbbUsers as $lkbb) {
            $lkbb->notify(new NewMerchantSubmission($this->profile));
        }
        session()->flash('message', 'Data berhasil dikirim! Silakan tunggu verifikasi LKBB.');
        unset($this->profile); 
    }

    public function perbaikiData()
    {
        $this->profile->update(['status_verifikasi' => 'belum_melengkapi']);
        unset($this->profile);
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- ========================================== --}}
    {{-- FASE 1: ONBOARDING BELUM MELENGKAPI DATA   --}}
    {{-- ========================================== --}}
    @if($this->profile->status_verifikasi === 'belum_melengkapi')
        <div class="max-w-5xl mx-auto">
            {{-- Hero header --}}
            <div class="mb-8 text-center sm:text-left">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-black uppercase tracking-widest mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Mitra Kantin SCFS
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Selamat Datang! Lengkapi Data Kantin Anda</h2>
                <p class="text-gray-500 text-sm sm:text-base mt-2 max-w-2xl">Lengkapi profil usaha, ajukan usulan bagi hasil, dan upload dokumen pendukung. Tim LKBB akan memverifikasi dalam 1×24 jam.</p>
            </div>

            <div class="space-y-5">

                {{-- SECTION 1: Informasi Usaha --}}
                <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <header class="px-5 sm:px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M5 9h14M5 13h14M5 17h14"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Informasi Usaha</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Identitas kantin & lokasi.</p>
                        </div>
                    </header>
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Kantin / Usaha</label>
                            <input wire:model="nama_kantin" type="text" placeholder="cth: Kantin Bu Sari" class="w-full text-base rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 py-3 px-4">
                            @error('nama_kantin') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Lokasi / Blok Kantin</label>
                            <input wire:model="lokasi_blok" type="text" placeholder="cth: Blok A No 5" class="w-full text-base rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 py-3 px-4">
                            @error('lokasi_blok') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                {{-- SECTION 2: Informasi Pemilik --}}
                <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <header class="px-5 sm:px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Informasi Pemilik</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Data diri sesuai KTP.</p>
                        </div>
                    </header>
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemilik (Sesuai KTP)</label>
                            <input wire:model="nama_pemilik" type="text" class="w-full text-base rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 py-3 px-4">
                            @error('nama_pemilik') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">NIK (16 digit)</label>
                            <input wire:model.defer="nik" type="text" maxlength="16" inputmode="numeric" pattern="[0-9]*"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,16)"
                                placeholder="3xxxxxxxxxxxxxxx"
                                class="w-full text-base font-mono rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 py-3 px-4">
                            @error('nik') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No HP / WA Aktif</label>
                            <input wire:model="no_hp" type="text" placeholder="08xxxxxxxxxx" class="w-full text-base font-mono rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 py-3 px-4">
                            @error('no_hp') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                {{-- SECTION 3: Rekening Pencairan --}}
                <section class="bg-white rounded-2xl border border-blue-200 shadow-sm overflow-hidden">
                    <header class="px-5 sm:px-6 py-4 border-b border-blue-100 bg-blue-50/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M5 7h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-blue-900 text-base">Rekening Pencairan</h3>
                            <p class="text-xs text-blue-700/70 mt-0.5">Tujuan transfer dana hasil penjualan.</p>
                        </div>
                    </header>
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-blue-700 uppercase tracking-wider mb-1.5">Bank / E-Wallet</label>
                            <select wire:model.live="nama_bank" class="w-full text-base rounded-xl border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 py-3 px-4 bg-white">
                                <option value="BCA">BCA</option>
                                <option value="BNI">BNI</option>
                                <option value="BRI">BRI</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="BJB">BJB</option>
                                <option value="GoPay">GoPay</option>
                                <option value="OVO">OVO</option>
                                <option value="Lainnya">Lainnya...</option>
                            </select>
                            @error('nama_bank') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                            @if($nama_bank === 'Lainnya')
                                <input wire:model="bank_lainnya" type="text" placeholder="Nama bank..." class="mt-2 w-full text-base rounded-xl border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 py-3 px-4 bg-white">
                                @error('bank_lainnya') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                            @endif
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-blue-700 uppercase tracking-wider mb-1.5">Nomor Rekening / E-Wallet</label>
                            <input wire:model="no_rekening" type="text" inputmode="numeric" placeholder="1234567890"
                                class="w-full text-base font-mono rounded-xl border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 py-3 px-4 bg-white">
                            @error('no_rekening') <span class="text-[11px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </section>

                {{-- SECTION 4: Usulan Bagi Hasil --}}
                <section class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
                    <header class="px-5 sm:px-6 py-4 border-b border-amber-100 bg-amber-50/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-amber-900 text-base">Usulan Bagi Hasil untuk Saya (Merchant)</h3>
                            <p class="text-xs text-amber-700/80 mt-0.5">Berapa % keuntungan tiap transaksi yang akan jadi bagian Anda? Sisanya jadi bagian LKBB.</p>
                        </div>
                    </header>
                    <div class="p-5 sm:p-6">
                        <p class="text-sm text-gray-600 leading-relaxed mb-4">
                            Tentukan persentase profit yang ingin Anda ambil untuk diri sendiri. Sisanya ({{ 100 - (int)($usulan_fee_merchant ?? 0) }}%) otomatis jadi bagian LKBB sebagai biaya layanan (talangan modal, sistem pembayaran, audit).
                            Nilai ini bersifat <span class="font-bold">usulan</span> — LKBB akan meninjau, kalau dirasa kebesaran mereka akan minta revisi.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Bagian Merchant (input) --}}
                            <div class="rounded-2xl border-2 border-amber-300 bg-amber-50/50 p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-700 mb-2">🏪 Bagian Saya (Merchant)</p>
                                <div class="relative">
                                    <input wire:model.live="usulan_fee_merchant" type="number" step="5" min="0" max="100" placeholder="70"
                                        class="w-full text-3xl font-black text-amber-900 rounded-xl border border-amber-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 py-4 pl-5 pr-14 bg-white">
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-5 text-amber-600 font-black text-xl">%</span>
                                </div>
                                @error('usulan_fee_merchant') <span class="text-[11px] text-red-500 mt-2 font-bold block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Bagian LKBB (preview, read-only) --}}
                            <div class="rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/50 p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-blue-700 mb-2">🏦 Bagian LKBB (Otomatis)</p>
                                <div class="relative">
                                    <div class="w-full text-3xl font-black text-blue-900 rounded-xl border-2 border-blue-200 py-4 pl-5 pr-14 bg-white">
                                        {{ 100 - (int)($usulan_fee_merchant ?? 0) }}
                                    </div>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-5 text-blue-600 font-black text-xl">%</span>
                                </div>
                                <p class="text-[10px] text-blue-700/80 mt-2 font-medium">Biaya layanan platform SCFS</p>
                            </div>
                        </div>

                        {{-- Preset Tombol Cepat (bagian Merchant) --}}
                        <div class="mt-5">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Pilih cepat (bagian Merchant)</p>
                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                @foreach([60, 65, 70, 75, 80, 85] as $preset)
                                    <button type="button" wire:click="$set('usulan_fee_merchant', {{ $preset }})"
                                        class="py-2 rounded-lg border font-bold text-sm transition
                                        {{ (int)$usulan_fee_merchant === $preset ? 'border-amber-500 bg-amber-500 text-white shadow-md shadow-amber-200' : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                        {{ $preset }}%
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-3">💡 Tombol naik/turun input menambah 5% per klik. Default standar industri: <span class="font-bold">70% merchant — 30% LKBB</span>.</p>
                    </div>
                </section>

                {{-- SECTION 5: Dokumen Pendukung --}}
                <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <header class="px-5 sm:px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Dokumen Pendukung</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Foto KTP & foto depan kantin (max 2MB).</p>
                        </div>
                    </header>
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <label class="block border-2 border-dashed border-gray-200 rounded-xl p-5 text-center hover:bg-gray-50 transition cursor-pointer">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Foto KTP *</span>
                            <input wire:model="foto_ktp" type="file" accept="image/*" class="w-full text-xs text-gray-500 cursor-pointer">
                            @error('foto_ktp') <span class="text-[11px] text-red-500 mt-2 font-bold block">{{ $message }}</span> @enderror
                            @if($foto_ktp && !is_string($foto_ktp))
                                <img src="{{ $foto_ktp->temporaryUrl() }}" class="mt-3 mx-auto h-28 rounded-lg object-cover shadow-sm border border-gray-200">
                            @elseif($this->profile->foto_ktp)
                                <div class="mt-3 text-xs text-emerald-600 font-bold">✓ KTP Tersimpan</div>
                            @endif
                        </label>
                        <label class="block border-2 border-dashed border-gray-200 rounded-xl p-5 text-center hover:bg-gray-50 transition cursor-pointer">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Foto Depan Kantin *</span>
                            <input wire:model="foto_kantin" type="file" accept="image/*" class="w-full text-xs text-gray-500 cursor-pointer">
                            @error('foto_kantin') <span class="text-[11px] text-red-500 mt-2 font-bold block">{{ $message }}</span> @enderror
                            @if($foto_kantin && !is_string($foto_kantin))
                                <img src="{{ $foto_kantin->temporaryUrl() }}" class="mt-3 mx-auto h-28 rounded-lg object-cover shadow-sm border border-gray-200">
                            @elseif($this->profile->foto_kantin)
                                <div class="mt-3 text-xs text-emerald-600 font-bold">✓ Foto Kantin Tersimpan</div>
                            @endif
                        </label>
                    </div>
                </section>

                {{-- SUBMIT --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-6 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Dengan menekan tombol di samping, Anda mengirim pengajuan pendaftaran ke LKBB. Tim akan meninjau dalam <span class="font-bold text-gray-700">1×24 jam</span>.
                    </p>
                    <button wire:click="submitOnboarding" wire:loading.attr="disabled"
                        class="px-8 py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-700 text-white font-black text-sm rounded-2xl shadow-lg shadow-emerald-200 hover:opacity-95 active:scale-[0.98] transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg wire:loading.remove wire:target="submitOnboarding" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="submitOnboarding" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span wire:loading.remove wire:target="submitOnboarding">Kirim Pengajuan ke LKBB</span>
                        <span wire:loading wire:target="submitOnboarding">Mengirim...</span>
                    </button>
                </div>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- FASE 2: MENUNGGU REVIEW LKBB               --}}
    {{-- ========================================== --}}
    @elseif($this->profile->status_verifikasi === 'menunggu_review')
        <div class="max-w-xl mx-auto mt-10">
            <div class="bg-white p-8 rounded-3xl shadow-xl text-center border border-gray-100">
                <div class="w-20 h-20 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl animate-pulse">⏳</div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Data Sedang Ditinjau</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">Terima kasih telah melengkapi data! Tim LKBB saat ini sedang melakukan verifikasi terhadap dokumen kantin Anda. Proses ini biasanya memakan waktu maksimal 1x24 Jam.</p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-700 rounded-full text-xs font-bold border border-yellow-200 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 animate-ping"></span> Status: Pending Review
                </div>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- FASE 3: PENGAJUAN DITOLAK                  --}}
    {{-- ========================================== --}}
    @elseif($this->profile->status_verifikasi === 'ditolak')
        <div class="max-w-xl mx-auto mt-10">
            <div class="bg-white p-8 rounded-3xl shadow-xl text-center border border-red-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-red-500"></div>
                <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">❌</div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Pengajuan Ditolak</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">Mohon maaf, pengajuan pendaftaran merchant Anda dikembalikan oleh LKBB karena alasan berikut:</p>
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm font-medium mb-6 text-left">
                    "{{ $this->profile->catatan_penolakan ?? 'Dokumen tidak jelas atau tidak lengkap.' }}"
                </div>
                <button wire:click="perbaikiData" class="px-6 py-2.5 bg-gray-900 text-white font-bold text-sm rounded-xl shadow-sm hover:bg-gray-800 transition flex items-center justify-center gap-2 mx-auto">
                    Perbaiki & Kirim Ulang
                </button>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- FASE 4: DASHBOARD UTAMA (DISETUJUI)        --}}
    {{-- ========================================== --}}
    @elseif($this->profile->status_verifikasi === 'disetujui')
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-gray-200 pb-5">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Halo, {{ $this->profile->nama_kantin }}! 👋</h2>
                <p class="text-gray-500 text-sm mt-1">Siap melayani mahasiswa hari ini? Buka kasir POS sekarang.</p>
            </div>
            <a href="{{ route('merchant.pos') }}" wire:navigate class="px-6 py-3 bg-[#059669] text-white font-bold text-sm rounded-xl transition shadow-lg shadow-emerald-200 flex items-center gap-2 hover:bg-emerald-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                Buka Mesin Kasir POS
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">

            {{-- Card 1: Split jadi 2 mini-card vertikal — Profit kantin per jalur --}}
            {{--   (a) Saldo E-Wallet digital (dari QR Beasiswa, bisa di-withdraw)        --}}
            {{--   (b) Profit Tunai (uang fisik di laci, sudah cash di tangan kantin)     --}}
            <div class="flex flex-col gap-3">
                {{-- 1a. Saldo E-Wallet (QR Digital) --}}
                <div class="bg-gradient-to-br from-[#059669] to-teal-700 rounded-2xl p-4 text-white shadow-lg shadow-emerald-200/50 relative overflow-hidden flex-1">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-6 -mt-6 pointer-events-none"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-emerald-100 text-[9px] font-extrabold tracking-widest">💳 SALDO QR DIGITAL</p>
                            <span class="bg-white/20 px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider">Withdraw</span>
                        </div>
                        <h3 class="text-xl font-black tracking-tight truncate">Rp {{ number_format($this->profile->saldo_token ?? 0, 0, ',', '.') }}</h3>
                        <a href="{{ route('merchant.withdraw') ?? '#' }}" wire:navigate class="text-[9px] font-bold text-white hover:text-emerald-100 underline underline-offset-2 mt-0.5 inline-block">Tarik Dana →</a>
                    </div>
                </div>

                {{-- 1b. Profit Tunai (Cash di Laci) --}}
                <div class="bg-gradient-to-br from-emerald-700 to-green-900 rounded-2xl p-4 text-white shadow-lg shadow-emerald-300/40 relative overflow-hidden flex-1">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-6 -mt-6 pointer-events-none"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-emerald-100 text-[9px] font-extrabold tracking-widest">💵 PROFIT TUNAI (HARI INI)</p>
                            <span class="bg-white/20 px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider">Cash</span>
                        </div>
                        <h3 class="text-xl font-black tracking-tight truncate">Rp {{ number_format($this->profitTunai['hari_ini'], 0, ',', '.') }}</h3>
                        <p class="text-[9px] text-emerald-200 mt-0.5 font-medium">Bulan ini: Rp {{ number_format($this->profitTunai['bulan_ini'], 0, ',', '.') }} • Sudah di laci</p>
                    </div>
                </div>
            </div>

            {{-- Card 2: Tagihan Setoran (Hutang LKBB dari Jualan Tunai) --}}
            <div class="bg-gradient-to-br from-amber-500 to-amber-700 rounded-2xl p-5 text-white shadow-lg shadow-amber-200/50 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
                <div class="relative z-10 flex flex-col h-full justify-between">
                    <p class="text-amber-100 text-[10px] font-extrabold tracking-widest mb-1 flex items-center justify-between">
                        TAGIHAN SETORAN LKBB
                        <span class="bg-red-500 text-white px-1.5 py-0.5 rounded text-[8px] animate-pulse">Wajib Bayar</span>
                    </p>
                    <h3 class="text-3xl font-black tracking-tight truncate py-2">Rp {{ number_format($this->profile->tagihan_setoran_tunai ?? 0, 0, ',', '.') }}</h3>
                    <a href="{{ route('merchant.setoran') ?? '#' }}" wire:navigate class="text-[10px] font-bold text-white hover:text-amber-100 underline underline-offset-2">Lunasi Tagihan Sekarang →</a>
                </div>
            </div>

            {{-- Card 3: Total Titipan Barang LKBB (BERDASARKAN STOK AKTIF) --}}
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-indigo-500">
                <p class="text-[10px] text-gray-500 font-extrabold tracking-widest mb-1">TOTAL MODAL LKBB (BARANG)</p>
                <h3 class="text-2xl font-black text-gray-900 truncate my-1">Rp {{ number_format($this->totalModalLKBB, 0, ',', '.') }}</h3>
                <p class="text-[10px] font-bold text-gray-400">Nilai sisa barang titipan LKBB di etalase Anda.</p>
            </div>

            {{-- Card 4: Volume Transaksi Hari Ini --}}
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500">
                <p class="text-[10px] text-gray-500 font-extrabold tracking-widest mb-1">VOLUME JUALAN (HARI INI)</p>
                <h3 class="text-2xl font-black text-gray-900 truncate my-1">Rp {{ number_format($this->statHariIni['total_nominal'], 0, ',', '.') }}</h3>
                @if($this->statHariIni['total_pesanan'] > 0)
                    <p class="text-[10px] text-sky-600 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        {{ $this->statHariIni['total_pesanan'] }} Struk Dicetak
                    </p>
                @else
                    <p class="text-[10px] text-gray-400 font-medium">- Belum ada penjualan</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            
            {{-- AREA GRAFIK (CHART) INTERAKTIF --}}
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                    <div>
                        <h3 class="font-black text-gray-900 text-sm">Grafik Penjualan Kantin</h3>
                        <p class="text-[10px] font-bold text-gray-500 mt-0.5">Gabungan Saldo Beasiswa & Tunai Umum</p>
                    </div>
                    
                    <div class="inline-flex bg-gray-200 p-1 rounded-lg">
                        <button wire:click="setFilter('today')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'today' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Hari Ini</button>
                        <button wire:click="setFilter('month')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'month' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Bulan Ini</button>
                        <button wire:click="setFilter('year')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'year' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tahun Ini</button>
                    </div>
                </div>
                
                <div class="p-6 flex-1">
                    <div 
                        x-data="{
                            chart: null,
                            initChart() {
                                let options = {
                                    chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
                                    series: [{ name: 'Volume Jualan (Rp)', data: [] }],
                                    xaxis: { categories: [], tooltip: { enabled: false }, axisBorder: { show: false } },
                                    yaxis: { labels: { formatter: function(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val); } } },
                                    stroke: { curve: 'smooth', width: 3 },
                                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                                    colors: ['#059669'],
                                    dataLabels: { enabled: false },
                                    grid: { strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
                                    tooltip: { y: { formatter: function(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val); } } }
                                };
                                this.chart = new ApexCharts(this.$refs.chartContainer, options);
                                this.chart.render();

                                let initialData = @js($this->getChartData());
                                this.chart.updateOptions({ xaxis: { categories: initialData.labels } });
                                this.chart.updateSeries([{ data: initialData.series }]);
                            }
                        }"
                        x-init="initChart()"
                        @update-chart.window="
                            chart.updateOptions({ xaxis: { categories: $event.detail.labels } });
                            chart.updateSeries([{ data: $event.detail.series }]);
                        "
                    >
                        <div x-ref="chartContainer" wire:ignore></div>
                    </div>
                </div>
            </div>

            {{-- KUMPULAN RIWAYAT (KANAN) --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                
                {{-- RIWAYAT TRANSAKSI KASIR --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col flex-1">
                    <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-900 text-sm">Penjualan Terakhir</h3>
                        <a href="{{ route('merchant.riwayat') ?? '#' }}" wire:navigate class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800 uppercase tracking-widest">Lihat</a>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <div class="space-y-1">
                            @forelse($this->riwayatTransaksi as $trx)
                            <div class="flex items-center justify-between p-2.5 hover:bg-gray-50 rounded-xl transition group border border-transparent hover:border-gray-100">
                                <div class="flex items-center gap-3 min-w-0">
                                    @if($trx->type === 'pembayaran_makanan_tunai')
                                        <div class="h-8 w-8 rounded-full bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs">💵</span>
                                        </div>
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-sky-50 text-sky-600 border border-sky-200 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs">💳</span>
                                        </div>
                                    @endif
                                    
                                    <div class="min-w-0">
                                        <div class="font-black text-xs text-gray-900 truncate">{{ str_replace(['[QR] ', '[TUNAI] '], '', $trx->description) }}</div>
                                        <div class="text-[8px] font-bold mt-0.5 tracking-wider {{ $trx->type === 'pembayaran_makanan_tunai' ? 'text-amber-600' : 'text-sky-600' }}">
                                            {{ $trx->type === 'pembayaran_makanan_tunai' ? 'TUNAI' : 'QR BEASISWA' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 pl-2">
                                    <div class="text-xs font-black text-gray-900">Rp{{ number_format($trx->total_amount, 0, ',', '.') }}</div>
                                    <div class="text-[8px] text-gray-400 mt-0.5 font-bold">{{ $trx->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="py-6 text-center text-gray-400">
                                <p class="text-[10px] font-bold mt-1">Belum ada transaksi hari ini.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- RIWAYAT ORDER BARANG KE LKBB (PO) --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col flex-1">
                    <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-900 text-sm">Status Order Barang</h3>
                        <a href="{{ route('merchant.penerimaan') ?? '#' }}" wire:navigate class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-widest">Resi</a>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <div class="space-y-1">
                            @forelse($this->riwayatPO as $po)
                            <div class="flex items-center justify-between p-2.5 hover:bg-gray-50 rounded-xl transition group border border-transparent hover:border-gray-100">
                                <div class="min-w-0 flex-1">
                                    <div class="font-black text-xs text-gray-900 flex items-center gap-2">
                                        {{ $po->nomor_order }}
                                    </div>
                                    <div class="text-[9px] font-bold text-gray-500 mt-0.5 truncate">
                                        Rp {{ number_format($po->total_estimasi, 0, ',', '.') }} • Tgl: {{ Carbon::parse($po->tanggal_kebutuhan)->format('d M') }}
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 pl-2">
                                    @if($po->status === 'menunggu_lkbb')
                                        <span class="inline-flex px-2 py-1 bg-amber-50 text-amber-600 text-[8px] font-extrabold uppercase tracking-wider rounded border border-amber-200">Review LKBB</span>
                                    @elseif($po->status === 'diproses_pemasok')
                                        <span class="inline-flex px-2 py-1 bg-blue-50 text-blue-600 text-[8px] font-extrabold uppercase tracking-wider rounded border border-blue-200">Disiapkan</span>
                                    @elseif($po->status === 'dikirim')
                                        <span class="inline-flex px-2 py-1 bg-purple-50 text-purple-600 text-[8px] font-extrabold uppercase tracking-wider rounded border border-purple-200">Dikirim</span>
                                    @elseif($po->status === 'selesai')
                                        <span class="inline-flex px-2 py-1 bg-emerald-50 text-emerald-600 text-[8px] font-extrabold uppercase tracking-wider rounded border border-emerald-200">Diterima</span>
                                    @elseif($po->status === 'ditolak')
                                        <span class="inline-flex px-2 py-1 bg-rose-50 text-rose-600 text-[8px] font-extrabold uppercase tracking-wider rounded border border-rose-200">Ditolak</span>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="py-6 text-center text-gray-400">
                                <p class="text-[10px] font-bold mt-1">Belum ada riwayat pesanan barang.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

        </div>

    @endif
</div>