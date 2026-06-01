<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Transaction;
use App\Models\PengajuanBantuan;

new
#[Layout('layouts.lkbb')]
class extends Component {
    use WithPagination;

    public int $mahasiswaId;
    public string $activeTab = 'mutasi'; // mutasi | jajan | pengajuan

    public function mount(int $id): void
    {
        $user = User::where('id', $id)->where('role', 'mahasiswa')->firstOrFail();
        $this->mahasiswaId = $user->id;
    }

    public function setTab(string $t): void
    {
        $this->activeTab = $t;
        $this->resetPage();
    }

    #[Computed]
    public function user()
    {
        return User::with(['mahasiswaProfile.pengajuans', 'wallet'])->findOrFail($this->mahasiswaId);
    }

    /** Tab 1: rekening koran (semua transaksi user_id=mahasiswa). */
    #[Computed]
    public function mutasi()
    {
        return Transaction::with('merchant.merchantProfile')
            ->where('user_id', $this->mahasiswaId)
            ->whereIn('status', ['success', 'sukses', 'lunas'])
            ->latest()
            ->paginate(15, ['*'], 'mutasiPage');
    }

    /** Tab 2: log jajan (pembayaran_makanan). */
    #[Computed]
    public function jajan()
    {
        return Transaction::with('merchant.merchantProfile')
            ->where('user_id', $this->mahasiswaId)
            ->where('type', 'pembayaran_makanan')
            ->whereIn('status', ['success', 'sukses', 'lunas'])
            ->latest()
            ->paginate(15, ['*'], 'jajanPage');
    }

    /** Tab 3: pengajuan bantuan. */
    #[Computed]
    public function pengajuan()
    {
        $profileId = $this->user->mahasiswaProfile?->id;
        if (! $profileId) {
            return PengajuanBantuan::whereRaw('1=0')->paginate(15, ['*'], 'ajuPage');
        }
        return PengajuanBantuan::where('mahasiswa_profile_id', $profileId)
            ->latest()
            ->paginate(15, ['*'], 'ajuPage');
    }

    #[Computed]
    public function totalBantuan(): float
    {
        return (float) Transaction::where('user_id', $this->mahasiswaId)
            ->where('type', 'penerimaan_bantuan')
            ->whereIn('status', ['success', 'sukses', 'lunas'])
            ->sum('total_amount');
    }

    public function isIncome(string $type): bool
    {
        return in_array($type, ['penerimaan_bantuan', 'TOPUP'], true);
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">

    {{-- BREADCRUMB --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('lkbb.entitas.mahasiswa-index') }}" wire:navigate class="hover:text-blue-600 transition">Buku Besar Mahasiswa</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-gray-900">Detail Mahasiswa</span>
        </div>
    </div>

    @php $user = $this->user; $profile = $user->mahasiswaProfile; @endphp

    {{-- HEADER PROFIL --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-5">
            <div class="h-20 w-20 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold shadow-inner flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                    @php $sb = $profile?->status_bantuan ?? 'belum_diajukan'; @endphp
                    @if($sb === 'disetujui')
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-emerald-100 uppercase tracking-wide">Dana Aktif</span>
                    @elseif($sb === 'diajukan')
                        <span class="bg-blue-50 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-blue-100 uppercase tracking-wide flex items-center gap-1">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Diproses LKBB
                        </span>
                    @elseif($sb === 'ditolak')
                        <span class="bg-red-50 text-red-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-red-100 uppercase tracking-wide">Ditolak LKBB</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">Belum Diajukan</span>
                    @endif
                </div>
                <p class="text-gray-500 font-medium text-sm">NIM: <span class="font-mono text-gray-700 font-bold">{{ $profile->nim ?? '-' }}</span> • {{ $profile->jurusan ?? '-' }}</p>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Terdaftar: {{ $user->created_at->format('d M Y') }}
                    </span>
                </div>
            </div>
        </div>

        <a href="{{ route('lkbb.entitas.mahasiswa-index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium text-sm transition text-center w-full md:w-auto focus:ring-4 focus:ring-gray-100">
            Kembali
        </a>
    </div>

    {{-- 3-KOLOM STATS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

        {{-- Info Pribadi --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Informasi Pribadi
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email</p>
                    <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No HP Aktif</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $profile->no_hp ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Alamat Tempat Tinggal</p>
                    <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $profile->alamat ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Info Akademik --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"/></svg>
                Data Akademik
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Institusi</p>
                    <p class="text-gray-900 font-medium text-sm">Institut Teknologi Bandung</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Program Studi</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $profile->jurusan ?: '-' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Semester</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $profile->semester ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">IPK Terakhir</p>
                        <p class="text-gray-900 font-extrabold text-sm">{{ $profile->ipk ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- E-Wallet Card --}}
        <div class="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 pointer-events-none transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase">E-WALLET</span>
                </div>
                <div>
                    <p class="text-blue-200 text-[10px] font-bold tracking-wider mb-1 uppercase">Sisa Saldo Bantuan</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        Rp {{ number_format($profile?->saldo ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            <div class="flex justify-between items-end pt-5 border-t border-blue-400/30 relative z-10 mt-auto">
                <div class="min-w-0 pr-2">
                    <p class="text-[9px] text-blue-200 mb-0.5 font-bold tracking-wider truncate uppercase">Total Dana Diterima</p>
                    <p class="font-bold text-sm truncate">Rp {{ number_format($this->totalBantuan, 0, ',', '.') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[9px] text-blue-200 mb-0.5 font-bold tracking-wider uppercase">Status</p>
                    <p class="font-bold text-xs text-emerald-300 tracking-wide flex items-center gap-1.5 justify-end uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 {{ ($profile?->status_bantuan ?? '') === 'disetujui' ? 'animate-pulse' : 'opacity-50' }}"></span>
                        {{ ($profile?->status_bantuan ?? '') === 'disetujui' ? 'Active' : 'Locked' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB DATA --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4 overflow-hidden">

        {{-- Tab nav border-b style --}}
        <div class="flex border-b border-gray-100 px-2 sm:px-6 gap-2 sm:gap-6 overflow-x-auto bg-gray-50/50">
            <button wire:click="setTab('mutasi')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'mutasi' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Mutasi Saldo
            </button>
            <button wire:click="setTab('jajan')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'jajan' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Riwayat Jajan
            </button>
            <button wire:click="setTab('pengajuan')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'pengajuan' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Riwayat Bantuan
            </button>
        </div>

        <div class="p-0 overflow-x-auto">

            {{-- TAB 1: MUTASI SALDO --}}
            @if($activeTab === 'mutasi')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Waktu & Resi</th>
                        <th class="px-6 py-4">Jenis</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->mutasi as $tx)
                        @php $income = $this->isIncome($tx->type); @endphp
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="px-6 py-4">
                                <div class="font-bold text-xs text-gray-900 font-mono mb-0.5">{{ $tx->order_id }}</div>
                                <div class="text-[10px] text-gray-500">{{ $tx->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $typeBadge = match($tx->type) {
                                        'penerimaan_bantuan'       => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'label' => 'Bantuan Cair'],
                                        'TOPUP'                    => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'label' => 'Top-Up'],
                                        'pembayaran_makanan'       => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'label' => 'QR Jajan'],
                                        'pembayaran_makanan_tunai' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'label' => 'Tunai'],
                                        default                    => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $tx->type],
                                    };
                                @endphp
                                <span class="{{ $typeBadge['bg'] }} {{ $typeBadge['text'] }} text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">{{ $typeBadge['label'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-700 truncate max-w-[300px]" title="{{ $tx->description }}">{{ str_replace(['[QR] ', '[TUNAI] '], '', $tx->description ?? '—') }}</div>
                                @if($tx->merchant)
                                    <a href="{{ route('lkbb.entitas.merchant-detail', $tx->merchant->id) }}" wire:navigate
                                       class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline mt-0.5 inline-block">
                                        → {{ $tx->merchant->merchantProfile->nama_kantin ?? $tx->merchant->name }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-extrabold text-sm text-right {{ $income ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $income ? '+' : '-' }} Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">💸</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada mutasi saldo.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->mutasi->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->mutasi->links() }}</div>
            @endif
            @endif

            {{-- TAB 2: JAJAN --}}
            @if($activeTab === 'jajan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Waktu & Resi</th>
                        <th class="px-6 py-4">Keterangan / Kantin</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->jajan as $trx)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono mb-0.5">{{ $trx->order_id }}</div>
                            <div class="text-[10px] text-gray-500">{{ $trx->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($trx->merchant)
                                <a href="{{ route('lkbb.entitas.merchant-detail', $trx->merchant->id) }}" wire:navigate
                                   class="font-bold text-sm text-gray-900 hover:text-emerald-600 hover:underline transition">
                                    {{ $trx->merchant->merchantProfile->nama_kantin ?? $trx->merchant->name }}
                                </a>
                            @else
                                <div class="font-bold text-sm text-gray-400">Kantin Terhapus</div>
                            @endif
                            <div class="text-[10px] text-gray-500 uppercase tracking-wider flex items-center gap-1 mt-0.5">
                                <svg class="w-3 h-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ str_replace(['[QR] ', '[TUNAI] '], '', $trx->description ?? 'Pengeluaran') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-emerald-50 text-emerald-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Sukses</span>
                        </td>
                        <td class="px-6 py-4 font-extrabold text-sm text-right text-gray-900">
                            - Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">🍱</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada riwayat jajan.</p>
                        <p class="text-gray-400 text-xs mt-1">Transaksi muncul otomatis saat mahasiswa belanja di kantin.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->jajan->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->jajan->links() }}</div>
            @endif
            @endif

            {{-- TAB 3: PENGAJUAN BANTUAN --}}
            @if($activeTab === 'pengajuan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Pengajuan</th>
                        <th class="px-6 py-4">Nominal Subsidi</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4 text-right">Status Verifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->pengajuan as $pengajuan)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-900 font-mono">{{ $pengajuan->nomor_pengajuan }}</td>
                        <td class="px-6 py-4 font-extrabold text-sm text-blue-600">Rp {{ number_format($pengajuan->nominal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $pengajuan->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            @if($pengajuan->status === 'disetujui')
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-emerald-100">Telah Cair</span>
                            @elseif($pengajuan->status === 'diajukan')
                                <span class="bg-yellow-50 text-yellow-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-yellow-100">Menunggu LKBB</span>
                            @else
                                <span class="bg-red-50 text-red-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-red-100">Ditolak</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">📬</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada riwayat pengajuan subsidi.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->pengajuan->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->pengajuan->links() }}</div>
            @endif
            @endif

        </div>
    </div>
</div>
