<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SetoranTunai;
use App\Models\MerchantProfile;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use Carbon\Carbon;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $tab = 'menunggu_penjemputan'; // 'menunggu_penjemputan' | 'selesai'
    public string $bulanAktif = '';

    // Modal Konfirmasi Penerimaan Uang
    public bool $isModalOpen = false;
    public ?int $selectedSetoranId = null;
    public string $nama_petugas = '';

    public function mount(): void
    {
        $this->bulanAktif = Carbon::now()->format('Y-m');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTab(): void { $this->resetPage(); }
    public function updatingBulanAktif(): void { $this->resetPage(); }

    #[Computed]
    public function daftarSetoran()
    {
        $query = SetoranTunai::with('merchant.merchantProfile')
            ->where('status', $this->tab)
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('nomor_setoran', 'like', '%' . $this->search . '%')
                       ->orWhereHas('merchant', function ($qqq) {
                           $qqq->where('name', 'like', '%' . $this->search . '%');
                       });
                });
            });

        // Filter bulan hanya berlaku untuk tab 'selesai' — kalau menunggu, tampilkan semua tiket aktif.
        if ($this->tab === 'selesai' && $this->bulanAktif !== '') {
            $parts = explode('-', $this->bulanAktif);
            if (count($parts) === 2) {
                $query->whereYear('updated_at', $parts[0])
                      ->whereMonth('updated_at', $parts[1]);
            }
        }

        return $query->latest()->paginate(10);
    }

    #[Computed]
    public function stats(): array
    {
        $now = Carbon::now();
        $monthStart = Carbon::createFromFormat('Y-m', $this->bulanAktif ?: $now->format('Y-m'))->startOfMonth();
        $monthEnd   = (clone $monthStart)->endOfMonth();

        $totalPending = (float) SetoranTunai::where('status', 'menunggu_penjemputan')->sum('nominal');
        $countPending = (int)   SetoranTunai::where('status', 'menunggu_penjemputan')->count();

        $totalSelesaiBulan = (float) SetoranTunai::where('status', 'selesai')
                                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                                ->sum('nominal');
        $countSelesaiBulan = (int) SetoranTunai::where('status', 'selesai')
                                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                                ->count();

        $kantinAktif = (int) SetoranTunai::where('status', 'menunggu_penjemputan')
                                ->distinct('merchant_id')->count('merchant_id');

        $avgSelesai = $countSelesaiBulan > 0 ? $totalSelesaiBulan / $countSelesaiBulan : 0;

        return [
            'total_pending'        => $totalPending,
            'count_pending'        => $countPending,
            'total_selesai_bulan'  => $totalSelesaiBulan,
            'count_selesai_bulan'  => $countSelesaiBulan,
            'kantin_aktif'         => $kantinAktif,
            'avg_selesai'          => $avgSelesai,
        ];
    }

    public function openTerimaModal(int $setoranId): void
    {
        $setoran = SetoranTunai::where('id', $setoranId)
                    ->where('status', 'menunggu_penjemputan')
                    ->first();

        if (!$setoran) {
            session()->flash('error', 'Tiket setoran tidak ditemukan atau sudah diproses.');
            return;
        }

        $this->selectedSetoranId = $setoranId;
        $this->nama_petugas = Auth::user()->name ?? '';
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->selectedSetoranId = null;
        $this->nama_petugas = '';
        $this->resetErrorBag();
    }

    /**
     * CORE: Konfirmasi penerimaan uang fisik dari kantin.
     * - Lock SetoranTunai + MerchantProfile + Wallet[LKBB_OPERATIONAL]
     * - Set status 'selesai' + nama petugas
     * - Decrement tagihan_setoran_tunai sebesar nominal yang dibawa
     * - Increment wallet LKBB_OPERATIONAL.balance + LedgerEntry REALIZE_TUNAI
     */
    public function terimaSetoran(): void
    {
        $this->validate([
            'nama_petugas' => 'required|string|min:3|max:120',
        ], [
            'nama_petugas.required' => 'Nama petugas wajib diisi sebagai catatan serah-terima.',
            'nama_petugas.min'      => 'Nama petugas minimal 3 karakter.',
        ]);

        if (!$this->selectedSetoranId) {
            session()->flash('error', 'Pilih tiket setoran terlebih dahulu.');
            return;
        }

        try {
            DB::transaction(function () {
                $setoran = SetoranTunai::where('id', $this->selectedSetoranId)
                            ->lockForUpdate()
                            ->firstOrFail();

                if ($setoran->status !== 'menunggu_penjemputan') {
                    throw new \Exception('Tiket setoran sudah diproses sebelumnya.');
                }

                $merchant = MerchantProfile::where('user_id', $setoran->merchant_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                $nominalSetor   = (float) $setoran->nominal;
                $hutangSaatIni  = (float) $merchant->tagihan_setoran_tunai;
                $sisaHutang     = max(0, $hutangSaatIni - $nominalSetor);

                $merchant->update(['tagihan_setoran_tunai' => $sisaHutang]);

                $walletOperasional = Wallet::where('type', 'LKBB_OPERATIONAL')
                                        ->lockForUpdate()
                                        ->first();

                if (!$walletOperasional) {
                    throw new \Exception('Wallet LKBB_OPERATIONAL belum dikonfigurasi. Hubungi admin.');
                }

                $walletOperasional->increment('balance', $nominalSetor);

                $setoran->update([
                    'status'       => 'selesai',
                    'nama_petugas' => $this->nama_petugas,
                ]);

                LedgerEntry::create([
                    'transaction_id' => null,
                    'wallet_id'      => $walletOperasional->id,
                    'entry_type'     => 'REALIZE_TUNAI',
                    'amount'         => $nominalSetor,
                    'balance_after'  => $walletOperasional->fresh()->balance,
                ]);
            });

            session()->flash('success', 'Uang tunai berhasil diterima dan tagihan kantin telah diperbarui.');
            $this->closeModal();
            unset($this->daftarSetoran, $this->stats);
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }
}; ?>

<div class="p-6">
    {{-- HEADER --}}
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('lkbb.dashboard') }}" class="text-sm font-bold text-gray-400 hover:text-amber-600 transition">Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-amber-600">Keuangan</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                <span class="text-amber-500">💵</span> Penagihan Setoran Tunai
            </h1>
            <p class="text-gray-500 text-sm mt-1">Kelola permintaan penjemputan uang fisik dari Kantin dan konfirmasi penerimaannya.</p>
        </div>

        <div class="flex gap-2 items-center">
            <span class="hidden md:inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-amber-200 rounded-xl text-xs font-bold text-amber-700 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                {{ $this->stats['count_pending'] }} Tiket Antrean
            </span>
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Card 1: Total Hutang Antrean (gradient amber) --}}
        <div class="bg-gradient-to-br from-amber-500 to-orange-700 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-5 rounded-full -mr-6 -mt-6 pointer-events-none transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="bg-white/20 px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-widest uppercase">Pending</span>
                </div>
                <p class="text-amber-100 text-[10px] font-bold tracking-wider mb-1 uppercase">Total Antrean Jemput</p>
                <h3 class="text-2xl font-extrabold tracking-tight drop-shadow-md">Rp {{ number_format($this->stats['total_pending'], 0, ',', '.') }}</h3>
                <p class="text-[10px] text-amber-100 mt-2 font-medium inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-amber-200 rounded-full animate-pulse"></span>
                    {{ $this->stats['count_pending'] }} tiket • {{ $this->stats['kantin_aktif'] }} kantin
                </p>
            </div>
        </div>

        {{-- Card 2: Sudah Disetor Bulan Ini (gradient emerald) --}}
        <div class="bg-gradient-to-br from-emerald-600 to-green-800 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-5 rounded-full -mr-6 -mt-6 pointer-events-none transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="bg-white/20 px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-widest uppercase">Realized</span>
                </div>
                <p class="text-emerald-100 text-[10px] font-bold tracking-wider mb-1 uppercase">Sudah Disetor (Bulan)</p>
                <h3 class="text-2xl font-extrabold tracking-tight drop-shadow-md">Rp {{ number_format($this->stats['total_selesai_bulan'], 0, ',', '.') }}</h3>
                <p class="text-[10px] text-emerald-100 mt-2 font-medium">{{ $this->stats['count_selesai_bulan'] }} setoran selesai bulan ini</p>
            </div>
        </div>

        {{-- Card 3: Jumlah Kantin Aktif --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500">
            <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Kantin Antre Jemput</p>
            <h3 class="text-xl font-black text-gray-900">
                {{ $this->stats['kantin_aktif'] }}
                <span class="text-xs text-gray-400 font-bold">kantin</span>
            </h3>
            <p class="text-[10px] text-sky-600 font-bold mt-1">Punya tiket aktif</p>
        </div>

        {{-- Card 4: Rata-rata Setoran --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-center bg-gray-50/50">
            <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-1">Rata-rata Setoran</p>
            <h3 class="text-xl font-black text-gray-900">Rp {{ number_format($this->stats['avg_selesai'], 0, ',', '.') }}</h3>
            <p class="text-[10px] text-gray-500 font-bold mt-1">Per tiket (bulan ini)</p>
        </div>
    </div>

    {{-- TABS + FILTER --}}
    <div class="bg-white p-2 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row gap-2 mb-6">
        {{-- Tabs --}}
        <div class="flex p-1 bg-gray-100 rounded-xl">
            <button wire:click="$set('tab', 'menunggu_penjemputan')"
                class="px-5 py-2 text-xs font-black tracking-wider rounded-lg transition-all flex items-center gap-2 {{ $tab === 'menunggu_penjemputan' ? 'bg-white text-amber-700 shadow-sm border border-amber-200/50' : 'text-gray-500 hover:text-gray-800' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $tab === 'menunggu_penjemputan' ? 'bg-amber-500' : 'bg-gray-300' }}"></span>
                Menunggu Penjemputan
                @if($this->stats['count_pending'] > 0)
                    <span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded text-[9px] font-extrabold">{{ $this->stats['count_pending'] }}</span>
                @endif
            </button>
            <button wire:click="$set('tab', 'selesai')"
                class="px-5 py-2 text-xs font-black tracking-wider rounded-lg transition-all flex items-center gap-2 {{ $tab === 'selesai' ? 'bg-white text-emerald-700 shadow-sm border border-emerald-200/50' : 'text-gray-500 hover:text-gray-800' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $tab === 'selesai' ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                Sudah Lunas
            </button>
        </div>

        {{-- Search + month filter --}}
        <div class="flex-1 flex gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nomor tiket atau nama kantin..."
                    class="w-full py-2.5 pl-11 pr-4 text-xs font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-100 transition">
            </div>

            @if($tab === 'selesai')
                <div class="w-full lg:w-44">
                    <input wire:model.live="bulanAktif" type="month"
                        class="w-full py-2.5 px-3 text-xs font-bold text-gray-700 bg-gray-50 border-transparent rounded-xl focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100 cursor-pointer">
                </div>
            @endif
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead class="bg-gray-50/80 border-b border-gray-100">
                    <tr class="text-[10px] text-gray-500 uppercase tracking-widest font-black">
                        <th class="px-5 py-4">Nomor Tiket & Waktu</th>
                        <th class="px-5 py-4">Kantin Penyetor</th>
                        <th class="px-5 py-4 text-right">Nominal Fisik</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->daftarSetoran as $setoran)
                        <tr class="hover:bg-amber-50/30 transition group">
                            {{-- Kolom 1: Nomor + waktu --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $setoran->status === 'menunggu_penjemputan' ? 'from-amber-100 to-orange-200 text-amber-700' : 'from-emerald-100 to-green-200 text-emerald-700' }} flex items-center justify-center shrink-0 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-mono text-xs font-black text-gray-900">{{ $setoran->nomor_setoran }}</div>
                                        <div class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ $setoran->created_at->format('d M Y, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Kolom 2: Kantin --}}
                            <td class="px-5 py-4">
                                @if($setoran->merchant)
                                    <a href="{{ route('lkbb.entitas.merchant-detail', $setoran->merchant->id) }}" wire:navigate
                                       class="text-sm font-bold text-gray-800 hover:text-amber-600 hover:underline transition flex items-center gap-1.5">
                                        🏪 {{ $setoran->merchant->merchantProfile->nama_kantin ?? $setoran->merchant->name }}
                                    </a>
                                @else
                                    <span class="text-sm font-bold text-gray-400 italic">Kantin Terhapus</span>
                                @endif
                                <div class="text-[10px] text-gray-400 mt-1 font-mono">UID-{{ $setoran->merchant_id }}</div>
                            </td>

                            {{-- Kolom 3: Nominal --}}
                            <td class="px-5 py-4 text-right">
                                <div class="text-sm font-black text-gray-900">
                                    Rp {{ number_format($setoran->nominal, 0, ',', '.') }}
                                </div>
                                <div class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Uang Fisik</div>
                            </td>

                            {{-- Kolom 4: Status --}}
                            <td class="px-5 py-4">
                                @if($setoran->status === 'menunggu_penjemputan')
                                    <span class="bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Menunggu Jemput
                                    </span>
                                @else
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Lunas
                                    </span>
                                    @if($setoran->nama_petugas)
                                        <div class="text-[10px] text-gray-500 mt-1.5 flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span class="font-semibold text-gray-700">{{ $setoran->nama_petugas }}</span>
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- Kolom 5: Aksi --}}
                            <td class="px-5 py-4 text-right">
                                @if($setoran->status === 'menunggu_penjemputan')
                                    <button wire:click="openTerimaModal({{ $setoran->id }})"
                                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-lg text-[11px] font-extrabold uppercase tracking-wider hover:from-amber-600 hover:to-orange-700 transition shadow-md shadow-amber-200 active:scale-95 inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        Terima &amp; Lunaskan
                                    </button>
                                @else
                                    <span class="text-[10px] text-gray-400 italic font-bold bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100">
                                        ✅ Selesai
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-24 text-center">
                                <div class="text-5xl mb-4 opacity-20 flex justify-center">
                                    @if($tab === 'menunggu_penjemputan') 📭 @else 📦 @endif
                                </div>
                                <h3 class="text-sm font-bold text-gray-600">
                                    @if($tab === 'menunggu_penjemputan')
                                        Tidak ada permintaan penjemputan saat ini.
                                    @else
                                        Belum ada setoran tunai lunas
                                        @if($bulanAktif) di bulan {{ \Carbon\Carbon::createFromFormat('Y-m', $bulanAktif)->isoFormat('MMMM YYYY') }} @endif.
                                    @endif
                                </h3>
                                <p class="text-xs text-gray-400 mt-1">
                                    @if($tab === 'menunggu_penjemputan')
                                        Tiket muncul otomatis ketika Kantin klik "Panggil Petugas".
                                    @else
                                        Coba ganti rentang bulan di filter.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($this->daftarSetoran->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gradient-to-r from-amber-50/30 to-orange-50/30">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <p class="text-[11px] font-bold text-gray-500">
                        Menampilkan
                        <span class="text-gray-900 font-extrabold">{{ $this->daftarSetoran->firstItem() }}</span>
                        –
                        <span class="text-gray-900 font-extrabold">{{ $this->daftarSetoran->lastItem() }}</span>
                        dari
                        <span class="text-gray-900 font-extrabold">{{ $this->daftarSetoran->total() }}</span>
                        tiket
                    </p>
                    <div class="penagihan-pagination">
                        {{ $this->daftarSetoran->onEachSide(1)->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- MODAL KONFIRMASI --}}
    @if($isModalOpen && $selectedSetoranId)
        @php
            $setoran = \App\Models\SetoranTunai::with('merchant.merchantProfile')->find($selectedSetoranId);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" wire:click.self="closeModal">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative overflow-hidden border border-gray-100">
                {{-- Modal Header --}}
                <div class="bg-gradient-to-br from-amber-500 to-orange-700 px-6 py-5 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <h3 class="text-lg font-black tracking-tight">Konfirmasi Terima Setoran</h3>
                        </div>
                        <p class="text-[11px] text-amber-100 font-medium leading-relaxed">Pastikan uang fisik sudah Anda hitung dan cocok dengan nominal di tiket.</p>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-5">
                    @if($setoran)
                        {{-- Ringkasan Tiket --}}
                        <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 space-y-2.5">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Nomor Tiket</span>
                                <span class="font-mono text-xs font-black text-gray-900">{{ $setoran->nomor_setoran }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Kantin</span>
                                <span class="text-xs font-extrabold text-gray-900 text-right max-w-[60%] truncate">
                                    {{ $setoran->merchant->merchantProfile->nama_kantin ?? ($setoran->merchant->name ?? '-') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-amber-200/60">
                                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Nominal Fisik</span>
                                <span class="text-xl font-black text-amber-700">Rp {{ number_format($setoran->nominal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Input Petugas --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-2 uppercase tracking-wider">
                            Nama Petugas Penerima <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <input type="text" wire:model="nama_petugas" wire:keydown.enter="terimaSetoran"
                                class="w-full pl-10 pr-4 py-3 text-sm font-bold text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100 transition"
                                placeholder="Contoh: Budi (Bendahara LKBB)">
                        </div>
                        @error('nama_petugas') <span class="text-rose-500 text-[10px] mt-1.5 font-bold block">{{ $message }}</span> @enderror
                        <p class="text-[10px] text-gray-400 mt-1.5">Catatan ini permanen di audit trail dan tidak bisa diubah.</p>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal"
                        class="px-5 py-2.5 text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 text-xs font-extrabold uppercase tracking-wider transition">
                        Batal
                    </button>
                    <button type="button" wire:click="terimaSetoran" wire:loading.attr="disabled"
                        class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700 text-xs font-extrabold uppercase tracking-wider transition shadow-md shadow-amber-200 disabled:opacity-50 inline-flex items-center gap-2">
                        <span wire:loading.remove wire:target="terimaSetoran" class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Ya, Terima Uangnya
                        </span>
                        <span wire:loading wire:target="terimaSetoran" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pagination Tailwind override (warna amber match tema) --}}
    <style>
        .penagihan-pagination nav { display: flex; align-items: center; gap: 0.25rem; }
        .penagihan-pagination nav span[aria-disabled="true"],
        .penagihan-pagination nav a {
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 11px;
            font-weight: 800;
            border: 1px solid #fde68a;
            background: #fff;
            color: #b45309;
            transition: all 0.15s;
        }
        .penagihan-pagination nav a:hover { background: #fef3c7; }
        .penagihan-pagination nav span[aria-current="page"] span {
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 11px;
            font-weight: 800;
            background: linear-gradient(to right, #f59e0b, #d97706);
            color: #fff;
            border: 1px solid #b45309;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);
        }
        .penagihan-pagination nav span[aria-disabled="true"] { opacity: 0.4; cursor: not-allowed; background: #f9fafb; color: #9ca3af; border-color: #e5e7eb; }
        .penagihan-pagination p { display: none; }
    </style>
</div>
