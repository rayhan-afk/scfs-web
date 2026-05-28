<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\SupplyOrder;
use App\Models\Withdrawal;
use App\Models\ProdukPemasok;

new
#[Layout('layouts.lkbb')]
class extends Component {
    use WithPagination;

    public int $pemasokId;
    public string $activeTab = 'profil'; // profil | po | withdraw

    public function mount(int $id): void
    {
        $user = User::where('id', $id)->where('role', 'pemasok')->firstOrFail();
        $this->pemasokId = $user->id;
    }

    public function setTab(string $t): void
    {
        $this->activeTab = $t;
        $this->resetPage();
    }

    #[Computed]
    public function user()
    {
        return User::with(['pemasokProfile', 'wallet'])->findOrFail($this->pemasokId);
    }

    #[Computed]
    public function katalog()
    {
        if ($this->activeTab !== 'profil') return collect();
        return ProdukPemasok::where('user_id', $this->pemasokId)->latest()->limit(20)->get();
    }

    #[Computed]
    public function mutasiPo()
    {
        return SupplyOrder::with(['merchant.merchantProfile'])
            ->where('pemasok_id', $this->pemasokId)
            ->where('status_pembiayaan', 'didanai')
            ->latest()
            ->paginate(15, ['*'], 'poPage');
    }

    #[Computed]
    public function mutasiWithdraw()
    {
        return Withdrawal::where('merchant_id', $this->pemasokId)
            ->latest()
            ->paginate(15, ['*'], 'wdPage');
    }

    #[Computed]
    public function metrik()
    {
        return [
            'total_po_didanai' => SupplyOrder::where('pemasok_id', $this->pemasokId)->where('status_pembiayaan', 'didanai')->count(),
            'total_nilai_po'   => SupplyOrder::where('pemasok_id', $this->pemasokId)->where('status_pembiayaan', 'didanai')->sum('total_estimasi'),
            'total_withdraw'   => Withdrawal::where('merchant_id', $this->pemasokId)->where('status', 'disetujui')->sum('nominal_bersih'),
        ];
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">

    {{-- BREADCRUMB --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('lkbb.entitas.pemasok-index') }}" wire:navigate class="hover:text-blue-600 transition">Buku Besar Pemasok</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-gray-900">Detail Pemasok</span>
        </div>
    </div>

    @php $user = $this->user; $profile = $user->pemasokProfile; @endphp

    {{-- HEADER PROFIL --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-5">
            <div class="h-20 w-20 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold shadow-inner flex-shrink-0">
                {{ strtoupper(substr($profile->nama_perusahaan ?? $user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1 flex-wrap">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $profile->nama_perusahaan ?? $user->name }}</h2>
                    @php $sv = $profile?->status_verifikasi ?? 'belum_melengkapi'; @endphp
                    @if($sv === 'terverifikasi')
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-emerald-100 uppercase tracking-wide">Verified</span>
                    @elseif($sv === 'pending')
                        <span class="bg-blue-50 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-blue-100 uppercase tracking-wide">Pending</span>
                    @elseif($sv === 'ditolak')
                        <span class="bg-red-50 text-red-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-red-100 uppercase tracking-wide">Ditolak</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">{{ str_replace('_', ' ', $sv) }}</span>
                    @endif
                    @if($profile?->status_kemitraan === 'aktif')
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-emerald-100 uppercase tracking-wide">Mitra Aktif</span>
                    @endif
                </div>
                <p class="text-gray-500 font-medium text-sm">PIC: <span class="font-bold text-gray-700">{{ $profile->nama_pic ?? '-' }}</span> • {{ $profile->kategori_barang ?? 'Lainnya' }}</p>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400 flex-wrap">
                    <span>✉ {{ $user->email }}</span>
                    <span>•</span>
                    <span>📞 {{ $profile->no_hp ?? '-' }}</span>
                    <span>•</span>
                    <span>Terdaftar: {{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        <a href="{{ route('lkbb.entitas.pemasok-index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium text-sm transition text-center w-full md:w-auto focus:ring-4 focus:ring-gray-100">
            Kembali
        </a>
    </div>

    {{-- 3-KOLOM STATS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

        {{-- Info Vendor --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Info Vendor
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">NIK PIC</p>
                    <p class="text-gray-900 font-mono font-bold text-sm">{{ $profile?->nik ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Alamat Gudang</p>
                    <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $profile?->alamat ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Status Operasional</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $profile?->status_operasional === 'buka' ? '🟢 Buka' : '⚫ Tutup' }}</p>
                </div>
            </div>
        </div>

        {{-- Info Rekening --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 7h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                Rekening Pencairan
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Bank</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $profile?->nama_bank ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No Rekening</p>
                    <p class="text-gray-900 font-mono font-bold text-sm">{{ $profile?->no_rekening ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Atas Nama</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $profile?->atas_nama_rekening ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- E-Wallet Card --}}
        <div class="bg-gradient-to-br from-indigo-600 to-purple-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 pointer-events-none transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase">E-WALLET</span>
                </div>
                <div>
                    <p class="text-indigo-200 text-[10px] font-bold tracking-wider mb-1 uppercase">Saldo Vendor</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        Rp {{ number_format($user->wallet->balance ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            <div class="flex justify-between items-end pt-5 border-t border-indigo-400/30 relative z-10 mt-auto">
                <div class="min-w-0 pr-2">
                    <p class="text-[9px] text-indigo-200 mb-0.5 font-bold tracking-wider truncate uppercase">Nilai PO Disalurkan</p>
                    <p class="font-bold text-sm truncate">Rp {{ number_format($this->metrik['total_nilai_po'], 0, ',', '.') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[9px] text-indigo-200 mb-0.5 font-bold tracking-wider uppercase">Withdraw Sukses</p>
                    <p class="font-bold text-sm">Rp {{ number_format($this->metrik['total_withdraw'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB DATA --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4 overflow-hidden">

        <div class="flex border-b border-gray-100 px-2 sm:px-6 gap-2 sm:gap-6 overflow-x-auto bg-gray-50/50">
            <button wire:click="setTab('profil')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'profil' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Profil & Katalog
            </button>
            <button wire:click="setTab('po')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'po' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Mutasi Pendanaan PO
            </button>
            <button wire:click="setTab('withdraw')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'withdraw' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Pencairan / Withdrawal
            </button>
        </div>

        <div class="overflow-x-auto">

            {{-- TAB 1: KATALOG --}}
            @if($activeTab === 'profil')
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-bold text-gray-900 text-sm">Katalog Produk ({{ $this->katalog->count() }})</h4>
                </div>
                @if($this->katalog->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">📦</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada produk di katalog.</p>
                    </div>
                @else
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3 text-center">Stok</th>
                            <th class="px-4 py-3 text-right">Harga Modal</th>
                            <th class="px-4 py-3 text-right">Margin</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($this->katalog as $produk)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-xs text-gray-700">{{ $produk->sku }}</td>
                            <td class="px-4 py-3 font-bold text-sm text-gray-900">{{ $produk->nama_produk }}</td>
                            <td class="px-4 py-3 text-center font-bold text-sm text-gray-700">{{ number_format($produk->stok_sekarang) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-sm text-gray-900">Rp {{ number_format($produk->harga_modal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-sm text-emerald-600">+{{ number_format($produk->margin_persen ?? 0, 1) }}%</td>
                            <td class="px-4 py-3 text-center">
                                @if($produk->status === 'aktif')
                                    <span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-green-200">Aktif</span>
                                @else
                                    <span class="bg-gray-100 text-gray-500 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-gray-200">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            @endif

            {{-- TAB 2: MUTASI PO --}}
            @if($activeTab === 'po')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Nomor PO</th>
                        <th class="px-6 py-4">Kantin Pemohon</th>
                        <th class="px-6 py-4 text-center">Status Barang</th>
                        <th class="px-6 py-4 text-right">Nilai Pendanaan</th>
                        <th class="px-6 py-4 text-right">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->mutasiPo as $po)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono">{{ $po->nomor_order }}</div>
                            <div class="text-[10px] text-gray-500 mt-0.5">Butuh: {{ \Carbon\Carbon::parse($po->tanggal_kebutuhan)->format('d M Y') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($po->merchant)
                                <a href="{{ route('lkbb.entitas.merchant-detail', $po->merchant->id) }}" wire:navigate
                                   class="text-sm font-bold text-gray-900 hover:text-emerald-600 hover:underline transition">
                                    {{ $po->merchant->merchantProfile->nama_kantin ?? $po->merchant->name }}
                                </a>
                            @else
                                <div class="text-sm font-bold text-gray-400">Kantin Terhapus</div>
                            @endif
                            <div class="text-[10px] text-gray-500 mt-0.5">Blok {{ $po->merchant->merchantProfile->lokasi_blok ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statStyle = match($po->status) {
                                    'diproses_pemasok' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'dikirim'          => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'selesai'          => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'ditolak'          => 'bg-red-50 text-red-700 border-red-200',
                                    default            => 'bg-gray-50 text-gray-700 border-gray-200',
                                };
                            @endphp
                            <span class="px-2.5 py-1 text-[10px] rounded-md font-bold uppercase tracking-wider border {{ $statStyle }}">{{ str_replace('_', ' ', $po->status) }}</span>
                        </td>
                        <td class="px-6 py-4 font-extrabold text-sm text-right text-emerald-600">+ Rp {{ number_format($po->total_estimasi, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-right text-gray-500">{{ $po->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">💰</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada PO didanai untuk pemasok ini.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->mutasiPo->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->mutasiPo->links() }}</div>
            @endif
            @endif

            {{-- TAB 3: WITHDRAW --}}
            @if($activeTab === 'withdraw')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Nomor Pencairan</th>
                        <th class="px-6 py-4">Tujuan Rekening</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Kotor</th>
                        <th class="px-6 py-4 text-right">Potongan</th>
                        <th class="px-6 py-4 text-right">Diterima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->mutasiWithdraw as $wd)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono">{{ $wd->nomor_pencairan }}</div>
                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $wd->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs font-medium text-gray-700 truncate max-w-[220px]" title="{{ $wd->info_pencairan }}">{{ $wd->info_pencairan }}</td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $wdStyle = match($wd->status) {
                                    'disetujui' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'ditolak'   => 'bg-red-50 text-red-700 border-red-200',
                                    default     => 'bg-gray-50 text-gray-700 border-gray-200',
                                };
                            @endphp
                            <span class="px-2.5 py-1 text-[10px] rounded-md font-bold uppercase tracking-wider border {{ $wdStyle }}">{{ $wd->status }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-right text-gray-600 font-bold">Rp {{ number_format($wd->nominal_kotor, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-right text-red-500 font-bold">- Rp {{ number_format($wd->potongan_lkbb, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 font-extrabold text-sm text-right text-emerald-600">Rp {{ number_format($wd->nominal_bersih, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">🏦</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada pencairan untuk pemasok ini.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->mutasiWithdraw->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->mutasiWithdraw->links() }}</div>
            @endif
            @endif

        </div>
    </div>
</div>
