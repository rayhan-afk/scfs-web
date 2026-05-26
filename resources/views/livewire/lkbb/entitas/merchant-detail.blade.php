<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Transaction;
use App\Models\SupplyOrder;
use App\Models\SetoranTunai;
use App\Models\Withdrawal;

new
#[Layout('layouts.lkbb')]
class extends Component {
    use WithPagination;

    public int $merchantId;
    public string $activeTab = 'penjualan'; // penjualan | po | setoran | withdraw

    public function mount(int $id): void
    {
        $user = User::where('id', $id)->where('role', 'merchant')->firstOrFail();
        $this->merchantId = $user->id;
    }

    public function setTab(string $t): void
    {
        $this->activeTab = $t;
        $this->resetPage();
    }

    #[Computed]
    public function user()
    {
        return User::with(['merchantProfile', 'wallet'])->findOrFail($this->merchantId);
    }

    #[Computed]
    public function penjualan()
    {
        return Transaction::where('merchant_id', $this->merchantId)
            ->whereIn('status', ['success', 'sukses', 'lunas'])
            ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai'])
            ->with('user')
            ->latest()
            ->paginate(15, ['*'], 'salesPage');
    }

    #[Computed]
    public function hutangPo()
    {
        return SupplyOrder::with(['pemasok.pemasokProfile'])
            ->where('merchant_id', $this->merchantId)
            ->where('status_pembiayaan', 'didanai')
            ->latest()
            ->paginate(15, ['*'], 'poPage');
    }

    #[Computed]
    public function setoran()
    {
        return SetoranTunai::where('merchant_id', $this->merchantId)
            ->latest()
            ->paginate(15, ['*'], 'setoranPage');
    }

    #[Computed]
    public function pencairan()
    {
        return Withdrawal::where('merchant_id', $this->merchantId)
            ->latest()
            ->paginate(15, ['*'], 'wdPage');
    }

    #[Computed]
    public function metrik()
    {
        $base = Transaction::where('merchant_id', $this->merchantId)
            ->whereIn('status', ['success', 'sukses', 'lunas'])
            ->whereIn('type', ['pembayaran_makanan', 'pembayaran_makanan_tunai']);

        return [
            'total_gmv'      => (clone $base)->sum('total_amount'),
            'total_fee'      => (clone $base)->sum('fee_lkbb'),
            'total_setoran'  => SetoranTunai::where('merchant_id', $this->merchantId)->where('status', 'selesai')->sum('nominal'),
            'total_withdraw' => Withdrawal::where('merchant_id', $this->merchantId)->where('status', 'disetujui')->sum('nominal_bersih'),
        ];
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">

    {{-- BREADCRUMB --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('lkbb.entitas.merchant-index') }}" wire:navigate class="hover:text-blue-600 transition">Buku Besar Merchant</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium text-gray-900">Detail Kantin</span>
        </div>
    </div>

    @php
        $user = $this->user;
        $profile = $user->merchantProfile;
        $hutang = (float) ($profile?->tagihan_setoran_tunai ?? 0);
        $hutangCritical = $hutang >= 500000;
    @endphp

    {{-- HEADER PROFIL --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-5">
            <div class="h-20 w-20 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl font-bold shadow-inner flex-shrink-0">
                {{ strtoupper(substr($profile->nama_kantin ?? $user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1 flex-wrap">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $profile->nama_kantin ?? $user->name }}</h2>
                    @php $sv = $profile?->status_verifikasi ?? 'belum_melengkapi'; @endphp
                    @if($sv === 'terverifikasi')
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-emerald-100 uppercase tracking-wide">Verified</span>
                    @elseif($sv === 'pending')
                        <span class="bg-blue-50 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-blue-100 uppercase tracking-wide">Pending</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">{{ str_replace('_', ' ', $sv) }}</span>
                    @endif
                    @if($profile?->status_toko === 'buka')
                        <span class="bg-green-50 text-green-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-green-100 uppercase tracking-wide">🟢 Buka</span>
                    @endif
                </div>
                <p class="text-gray-500 font-medium text-sm">{{ $profile->nama_pemilik ?? '-' }} • <span class="font-mono text-gray-700 font-bold">Blok {{ $profile->lokasi_blok ?? '-' }}</span></p>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400 flex-wrap">
                    <span>📞 {{ $profile->no_hp ?? '-' }}</span>
                    <span>•</span>
                    <span>Fee LKBB: <span class="font-bold text-gray-600">{{ $profile?->persentase_fee_merchant ?? 10 }}%</span></span>
                    <span>•</span>
                    <span>Terdaftar: {{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        <a href="{{ route('lkbb.entitas.merchant-index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium text-sm transition text-center w-full md:w-auto focus:ring-4 focus:ring-gray-100">
            Kembali
        </a>
    </div>

    {{-- ALERT HUTANG TINGGI --}}
    @if($hutangCritical)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <p class="text-sm font-medium text-red-800">⚠ Hutang setoran fisik tinggi: Rp {{ number_format($hutang, 0, ',', '.') }}. Segera ditindaklanjuti ke petugas pengambilan.</p>
        </div>
    @endif

    {{-- 3-KOLOM STATS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

        {{-- Info Operasional --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M5 9h14M5 13h14M5 17h14"/></svg>
                Info Operasional
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email Akses</p>
                    <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Persentase Fee LKBB</p>
                    <p class="text-gray-900 font-extrabold text-sm">{{ $profile?->persentase_fee_merchant ?? 10 }}%</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Status Toko</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $profile?->status_toko === 'buka' ? '🟢 Buka' : '⚫ Tutup' }}</p>
                </div>
            </div>
        </div>

        {{-- Info Rekening --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1M5 7h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
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
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Info Pencairan</p>
                    <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $profile?->info_pencairan ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- E-Wallet Card --}}
        <div class="bg-gradient-to-br from-emerald-600 to-teal-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 pointer-events-none transition-transform group-hover:scale-110"></div>
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase">E-WALLET</span>
                </div>
                <div>
                    <p class="text-emerald-200 text-[10px] font-bold tracking-wider mb-1 uppercase">Saldo Kantin</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        Rp {{ number_format($user->wallet->balance ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            <div class="flex justify-between items-end pt-5 border-t border-emerald-400/30 relative z-10 mt-auto">
                <div class="min-w-0 pr-2">
                    <p class="text-[9px] text-emerald-200 mb-0.5 font-bold tracking-wider truncate uppercase">Hutang Setoran</p>
                    <p class="font-bold text-sm truncate {{ $hutangCritical ? 'text-red-300' : '' }}">Rp {{ number_format($hutang, 0, ',', '.') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[9px] text-emerald-200 mb-0.5 font-bold tracking-wider uppercase">Total GMV</p>
                    <p class="font-bold text-sm">Rp {{ number_format($this->metrik['total_gmv'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB DATA --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4 overflow-hidden">

        <div class="flex border-b border-gray-100 px-2 sm:px-6 gap-2 sm:gap-6 overflow-x-auto bg-gray-50/50">
            <button wire:click="setTab('penjualan')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'penjualan' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Penjualan GMV
            </button>
            <button wire:click="setTab('po')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'po' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Hutang PO
            </button>
            <button wire:click="setTab('setoran')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'setoran' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Tagihan & Setoran
            </button>
            <button wire:click="setTab('withdraw')"
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab === 'withdraw' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Pencairan / Withdrawal
            </button>
        </div>

        <div class="p-0 overflow-x-auto">

            {{-- TAB 1: PENJUALAN --}}
            @if($activeTab === 'penjualan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Order ID</th>
                        <th class="px-6 py-4">Channel</th>
                        <th class="px-6 py-4">Pembeli / Keterangan</th>
                        <th class="px-6 py-4 text-right">HPP</th>
                        <th class="px-6 py-4 text-right">Fee LKBB</th>
                        <th class="px-6 py-4 text-right">Harga Jual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->penjualan as $tx)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono mb-0.5">{{ $tx->order_id }}</div>
                            <div class="text-[10px] text-gray-500">{{ $tx->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($tx->type === 'pembayaran_makanan_tunai')
                                <span class="bg-amber-50 text-amber-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-amber-100">💵 Tunai</span>
                            @else
                                <span class="bg-blue-50 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-blue-100">💳 QR</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($tx->user && $tx->type === 'pembayaran_makanan')
                                <a href="{{ route('lkbb.entitas.mahasiswa-detail', $tx->user->id) }}" wire:navigate
                                   class="text-sm font-bold text-gray-900 hover:text-amber-600 hover:underline transition">
                                    {{ $tx->user->name }}
                                </a>
                            @else
                                <div class="text-sm font-bold text-gray-600">Pembeli Tunai</div>
                            @endif
                            <div class="text-[10px] text-gray-500 mt-0.5 truncate max-w-[280px]" title="{{ $tx->description }}">{{ str_replace(['[QR] ', '[TUNAI] '], '', $tx->description ?? '—') }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-xs font-bold text-gray-600">Rp {{ number_format($tx->total_pokok, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-xs font-bold text-emerald-600">+ Rp {{ number_format($tx->fee_lkbb, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 font-extrabold text-sm text-right text-purple-600">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">💳</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada transaksi penjualan.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->penjualan->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->penjualan->links() }}</div>
            @endif
            @endif

            {{-- TAB 2: HUTANG PO --}}
            @if($activeTab === 'po')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Nomor PO</th>
                        <th class="px-6 py-4">Pemasok</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Nilai Ditalangi</th>
                        <th class="px-6 py-4 text-right">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->hutangPo as $po)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-900 font-mono">{{ $po->nomor_order }}</td>
                        <td class="px-6 py-4">
                            @if($po->pemasok)
                                <a href="{{ route('lkbb.entitas.pemasok-detail', $po->pemasok->id) }}" wire:navigate
                                   class="text-sm font-bold text-gray-900 hover:text-indigo-600 hover:underline transition">
                                    {{ $po->pemasok->pemasokProfile->nama_perusahaan ?? $po->pemasok->name }}
                                </a>
                            @else
                                <div class="text-sm font-bold text-gray-400">Pemasok Terhapus</div>
                            @endif
                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $po->pemasok->pemasokProfile->kategori_barang ?? '-' }}</div>
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
                        <td class="px-6 py-4 font-extrabold text-sm text-right text-red-600">- Rp {{ number_format($po->total_estimasi, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-right text-gray-500">{{ $po->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">📦</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada PO ditalangi untuk kantin ini.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->hutangPo->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->hutangPo->links() }}</div>
            @endif
            @endif

            {{-- TAB 3: SETORAN --}}
            @if($activeTab === 'setoran')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Nomor Setoran</th>
                        <th class="px-6 py-4">Petugas Penjemput</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                        <th class="px-6 py-4 text-right">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->setoran as $s)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-900 font-mono">{{ $s->nomor_setoran }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ $s->nama_petugas ?? '— belum dijemput —' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($s->status === 'selesai')
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-emerald-100">Selesai</span>
                            @else
                                <span class="bg-amber-50 text-amber-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-amber-100">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-extrabold text-sm text-right text-amber-600">Rp {{ number_format($s->nominal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-right text-gray-500">{{ $s->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center">
                        <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">💵</div>
                        <p class="text-gray-500 text-sm font-medium">Belum ada riwayat setoran tunai.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->setoran->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->setoran->links() }}</div>
            @endif
            @endif

            {{-- TAB 4: WITHDRAW --}}
            @if($activeTab === 'withdraw')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Nomor Pencairan</th>
                        <th class="px-6 py-4">Tujuan</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Kotor</th>
                        <th class="px-6 py-4 text-right">Potongan</th>
                        <th class="px-6 py-4 text-right">Diterima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->pencairan as $wd)
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
                        <p class="text-gray-500 text-sm font-medium">Belum ada riwayat pencairan.</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->pencairan->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->pencairan->links() }}</div>
            @endif
            @endif

        </div>
    </div>
</div>
