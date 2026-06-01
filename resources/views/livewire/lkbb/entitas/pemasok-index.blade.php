<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;

new
#[Layout('layouts.lkbb')]
class extends Component {
    use WithPagination;

    public $search = '';
    public $activeTab = 'Semua'; // Semua | Terverifikasi | Pending | Ditolak

    public function updatedSearch()    { $this->resetPage(); }
    public function updatedActiveTab() { $this->resetPage(); }

    #[Computed]
    public function pemasoks()
    {
        $query = User::query()
            ->where('role', 'pemasok')
            ->with([
                'pemasokProfile:id,user_id,nama_perusahaan,kategori_barang,no_hp,status_verifikasi,status_kemitraan,status_operasional,saldo_pendapatan,tagihan_berjalan',
            ])
            ->withCount(['supplyOrdersAsPemasok as total_po_didanai' => function ($q) {
                $q->where('status_pembiayaan', 'didanai');
            }])
            ->withSum(['withdrawals as total_pencairan_sukses' => function ($q) {
                $q->where('status', 'disetujui');
            }], 'nominal_bersih');

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhereHas('pemasokProfile', function ($p) use ($term) {
                      $p->where('nama_perusahaan', 'like', "%{$term}%")
                        ->orWhere('no_hp', 'like', "%{$term}%");
                  });
            });
        }

        if ($this->activeTab === 'Terverifikasi') {
            $query->whereHas('pemasokProfile', fn($p) => $p->where('status_verifikasi', 'terverifikasi'));
        } elseif ($this->activeTab === 'Pending') {
            $query->whereHas('pemasokProfile', fn($p) => $p->where('status_verifikasi', 'pending'));
        } elseif ($this->activeTab === 'Ditolak') {
            $query->whereHas('pemasokProfile', fn($p) => $p->where('status_verifikasi', 'ditolak'));
        }

        return $query->latest()->paginate(15);
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Buku Besar Pemasok</h2>
            <p class="text-gray-500 text-sm mt-1">Audit performa vendor: jumlah PO didanai, total pencairan, dan saldo e-wallet tiap pemasok.</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-96">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama PIC, perusahaan, email, atau no HP..."
                class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition">
        </div>

        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            @foreach(['Semua', 'Terverifikasi', 'Pending', 'Ditolak'] as $tab)
                <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-2 text-sm rounded-xl transition-all whitespace-nowrap
                    {{ $activeTab === $tab ? 'bg-blue-600 text-white shadow-md font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100 font-medium' }}">
                    {{ $tab }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-gray-900 text-sm">Daftar Pemasok Vendor</h3>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">Total: {{ $this->pemasoks->total() }} Vendor</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Vendor / Kategori</th>
                        <th class="px-6 py-4">Verifikasi</th>
                        <th class="px-6 py-4 text-center">PO Didanai</th>
                        <th class="px-6 py-4 text-right">Total Pencairan</th>
                        <th class="px-6 py-4 text-right">Saldo E-Wallet</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->pemasoks as $pemasok)
                        @php $profile = $pemasok->pemasokProfile; @endphp
                        <tr class="hover:bg-gray-50/80 transition group">
                            {{-- Vendor --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-indigo-100 text-indigo-600 flex-shrink-0">
                                        {{ strtoupper(substr($profile->nama_perusahaan ?? $pemasok->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm">{{ $profile->nama_perusahaan ?? $pemasok->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $pemasok->name }} · {{ $profile->no_hp ?? '-' }}</div>
                                        @if($profile?->kategori_barang)
                                            <div class="text-[10px] font-bold text-gray-500 mt-0.5">{{ $profile->kategori_barang }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Verifikasi --}}
                            <td class="px-6 py-4">
                                @php $sv = $profile?->status_verifikasi ?? 'belum_melengkapi'; @endphp
                                @if($sv === 'terverifikasi')
                                    <span class="bg-green-100 text-green-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-green-200">Verified</span>
                                @elseif($sv === 'pending')
                                    <span class="bg-yellow-100 text-yellow-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-yellow-200">Pending</span>
                                @elseif($sv === 'ditolak')
                                    <span class="bg-red-100 text-red-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-red-200">Ditolak</span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-gray-200">{{ str_replace('_', ' ', $sv) }}</span>
                                @endif
                                @if($profile?->status_kemitraan === 'aktif')
                                    <div class="text-[10px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Mitra Aktif
                                    </div>
                                @endif
                            </td>

                            {{-- PO Didanai --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex h-8 min-w-[36px] items-center justify-center rounded-lg bg-indigo-50 px-2 text-sm font-bold text-indigo-700">
                                    {{ number_format($pemasok->total_po_didanai ?? 0, 0, ',', '.') }}
                                </span>
                                <div class="text-[10px] text-gray-400 font-medium mt-1">PO Talangan</div>
                            </td>

                            {{-- Total Pencairan --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-emerald-600">Rp {{ number_format($pemasok->total_pencairan_sukses ?? 0, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-gray-400 font-medium mt-0.5">Withdraw Sukses</div>
                            </td>

                            {{-- Saldo --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold {{ ($profile?->saldo_pendapatan ?? 0) > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    Rp {{ number_format($profile?->saldo_pendapatan ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-medium mt-0.5">Saldo Pendapatan</div>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('lkbb.entitas.pemasok-detail', $pemasok->id) }}" wire:navigate
                                   class="inline-flex items-center px-3 py-1.5 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors uppercase tracking-wider">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="text-4xl mb-3">🏭</div>
                        Belum ada pemasok terdaftar.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->pemasoks->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->pemasoks->links() }}</div>
        @endif
    </div>
</div>
