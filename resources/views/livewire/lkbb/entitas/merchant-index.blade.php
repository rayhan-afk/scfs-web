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
    public $activeTab = 'Semua'; // Semua | Terverifikasi | Pending | Hutang Tinggi

    public function updatedSearch()    { $this->resetPage(); }
    public function updatedActiveTab() { $this->resetPage(); }

    #[Computed]
    public function merchants()
    {
        $query = User::query()
            ->where('role', 'merchant')
            ->with([
                'merchantProfile:id,user_id,nama_kantin,nama_pemilik,no_hp,lokasi_blok,status_verifikasi,status_toko,tagihan_setoran_tunai,saldo_token,persentase_fee_merchant',
            ])
            ->withSum(['wallets as saldo_wallet' => function ($q) {
                $q->where('type', 'MERCHANT');
            }], 'balance')
            ->withSum(['transactionsAsMerchant as total_gmv' => function ($q) {
                $q->whereIn('status', ['success', 'sukses', 'lunas'])
                  ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']);
            }], 'total_amount')
            ->withSum(['transactionsAsMerchant as total_fee_lkbb' => function ($q) {
                $q->whereIn('status', ['success', 'sukses', 'lunas'])
                  ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']);
            }], 'fee_lkbb');

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhereHas('merchantProfile', function ($p) use ($term) {
                      $p->where('nama_kantin', 'like', "%{$term}%")
                        ->orWhere('nama_pemilik', 'like', "%{$term}%")
                        ->orWhere('lokasi_blok', 'like', "%{$term}%");
                  });
            });
        }

        if ($this->activeTab === 'Terverifikasi') {
            $query->whereHas('merchantProfile', fn($p) => $p->where('status_verifikasi', 'terverifikasi'));
        } elseif ($this->activeTab === 'Pending') {
            $query->whereHas('merchantProfile', fn($p) => $p->where('status_verifikasi', 'pending'));
        } elseif ($this->activeTab === 'Hutang Tinggi') {
            $query->whereHas('merchantProfile', fn($p) => $p->where('tagihan_setoran_tunai', '>=', 500000));
        }

        return $query->latest()->paginate(15);
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Buku Besar Merchant / Kantin</h2>
            <p class="text-gray-500 text-sm mt-1">Audit kontribusi tiap kantin: GMV penjualan, fee LKBB, hutang setoran, dan saldo e-wallet.</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-96">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama kantin, pemilik, blok, atau email..."
                class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition">
        </div>

        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            @foreach(['Semua', 'Terverifikasi', 'Pending', 'Hutang Tinggi'] as $tab)
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
            <h3 class="font-bold text-gray-900 text-sm">Daftar Kantin Terdaftar</h3>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">Total: {{ $this->merchants->total() }} Kantin</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Kantin / Pemilik</th>
                        <th class="px-6 py-4">Lokasi</th>
                        <th class="px-6 py-4 text-right">Saldo Wallet</th>
                        <th class="px-6 py-4 text-right">Hutang Setoran</th>
                        <th class="px-6 py-4 text-right">GMV Penjualan</th>
                        <th class="px-6 py-4 text-right">Fee LKBB</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->merchants as $m)
                        @php
                            $profile = $m->merchantProfile;
                            $hutang = (float) ($profile?->tagihan_setoran_tunai ?? 0);
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition group">
                            {{-- Kantin --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-emerald-100 text-emerald-600 flex-shrink-0">
                                        {{ strtoupper(substr($profile->nama_kantin ?? $m->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm">{{ $profile->nama_kantin ?? $m->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $profile->nama_pemilik ?? '-' }} · {{ $profile->no_hp ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Lokasi --}}
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-700">Blok {{ $profile->lokasi_blok ?? '-' }}</div>
                                @php $sv = $profile?->status_verifikasi ?? 'belum_melengkapi'; @endphp
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($sv === 'terverifikasi')
                                        <span class="bg-green-100 text-green-700 text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-green-200">Verified</span>
                                    @elseif($sv === 'pending')
                                        <span class="bg-yellow-100 text-yellow-700 text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-yellow-200">Pending</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-gray-200">{{ str_replace('_', ' ', $sv) }}</span>
                                    @endif
                                    @if($profile?->status_toko === 'buka')
                                        <span class="bg-blue-100 text-blue-700 text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-blue-200">Buka</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Wallet --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold {{ ($m->saldo_wallet ?? 0) > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    Rp {{ number_format($m->saldo_wallet ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-medium mt-0.5">Token: Rp {{ number_format($profile?->saldo_token ?? 0, 0, ',', '.') }}</div>
                            </td>

                            {{-- Hutang --}}
                            <td class="px-6 py-4 text-right">
                                @if($hutang >= 500000)
                                    <div class="text-sm font-bold text-red-600">Rp {{ number_format($hutang, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-red-500 font-bold mt-0.5">⚠ Tinggi</div>
                                @elseif($hutang > 0)
                                    <div class="text-sm font-bold text-amber-600">Rp {{ number_format($hutang, 0, ',', '.') }}</div>
                                @else
                                    <div class="text-sm font-bold text-gray-400">Rp 0</div>
                                @endif
                            </td>

                            {{-- GMV --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-purple-600">Rp {{ number_format($m->total_gmv ?? 0, 0, ',', '.') }}</div>
                            </td>

                            {{-- Fee --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-emerald-600">Rp {{ number_format($m->total_fee_lkbb ?? 0, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $profile?->persentase_fee_merchant ?? 10 }}%</div>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('lkbb.entitas.merchant-detail', $m->id) }}" wire:navigate
                                   class="inline-flex items-center px-3 py-1.5 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors uppercase tracking-wider">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="text-4xl mb-3">🏪</div>
                        Belum ada kantin terdaftar.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->merchants->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->merchants->links() }}</div>
        @endif
    </div>
</div>
