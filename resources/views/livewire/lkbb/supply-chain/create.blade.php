<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Str;

// ⚠️ PENTING: Jika sidebar masih hilang, ubah 'layouts.app' menjadi 'components.layouts.app' atau nama file layout utama Anda!
new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    // Properti Form
    public $merchant_id = '';
    public $pemasok_id = '';
    public $total_amount = '';
    public $description = '';

    // Properti Modal Detail Database
    public $showDetailModal = false;
    public $selectedTrx = null;

    public function with()
    {
        return [
            // Ambil daftar user untuk dropdown
            'merchants' => User::whereIn('role', ['merchant', 'Merchant'])->get(),
            'pemasoks' => User::whereIn('role', ['pemasok', 'Pemasok', 'supplier', 'Supplier'])->get(),
            
            // Ambil riwayat pengajuan pembiayaan
            'riwayatPengajuan' => Transaction::with('user')
                ->where('type', 'pembiayaan_rantai_pasok')
                ->latest()
                ->paginate(10)
        ];
    }

    public function simpanPengajuan()
    {
        $this->validate([
            'merchant_id' => 'required',
            'pemasok_id' => 'required',
            'total_amount' => 'required|numeric|min:1000',
            'description' => 'required|string',
        ], [
            'merchant_id.required' => 'Pilih Merchant terlebih dahulu.',
            'pemasok_id.required' => 'Pilih Pemasok tujuan.',
            'total_amount.required' => 'Nominal wajib diisi.',
            'description.required' => 'Deskripsi barang/invoice wajib diisi.',
        ]);

        $pemasok = User::find($this->pemasok_id);

        Transaction::create([
            'user_id' => $this->merchant_id, // Merchant yang mengajukan/berhutang
            'order_id' => 'SCF-' . date('Ymd') . '-' . Str::upper(Str::random(6)),
            'total_amount' => $this->total_amount,
            'type' => 'pembiayaan_rantai_pasok',
            'status' => 'PENDING', // Menunggu Approval Admin LKBB
            'description' => "Pengajuan SCF ke Pemasok: {$pemasok->name} | Catatan: {$this->description}",
        ]);

        $this->reset(['merchant_id', 'pemasok_id', 'total_amount', 'description']);
        session()->flash('message', 'Pengajuan Rantai Pasok berhasil dibuat! Menunggu proses Approval.');
    }

    // Fungsi Buka Tutup Modal Detail Database
    public function lihatDetail($id)
    {
        $this->selectedTrx = Transaction::with('user')->find($id);
        $this->showDetailModal = true;
    }

    public function tutupDetail()
    {
        $this->showDetailModal = false;
        $this->selectedTrx = null;
    }
}; ?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Buat Pengajuan Rantai Pasok (SCF)</h1>
        <p class="text-gray-500 text-sm mt-1">Fasilitas pembiayaan tagihan Merchant kepada Pemasok oleh LKBB.</p>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-sm">
            <strong class="font-bold">Berhasil!</strong> {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Formulir Pengajuan Baru</h2>
                <form wire:submit="simpanPengajuan">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pihak Pemohon (Merchant)</label>
                        <select wire:model="merchant_id" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 px-3 py-2 text-sm bg-gray-50">
                            <option value="">-- Pilih Merchant --</option>
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                            @endforeach
                        </select>
                        @error('merchant_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pihak Penerima Dana (Pemasok)</label>
                        <select wire:model="pemasok_id" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 px-3 py-2 text-sm bg-gray-50">
                            <option value="">-- Pilih Pemasok --</option>
                            @foreach($pemasoks as $pemasok)
                                <option value="{{ $pemasok->id }}">{{ $pemasok->name }}</option>
                            @endforeach
                        </select>
                        @error('pemasok_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Total Tagihan (Rp)</label>
                        <input type="number" wire:model="total_amount" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 px-3 py-2 text-sm" placeholder="Contoh: 5000000">
                        @error('total_amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi / No. Invoice</label>
                        <textarea wire:model="description" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 px-3 py-2 text-sm" placeholder="Rincian barang dagangan..."></textarea>
                        @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-sm">
                        Submit Pengajuan
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h2 class="text-md font-bold text-gray-800">Daftar Pengajuan (Menunggu Approval)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                                <th class="px-4 py-3">Order ID / Waktu</th>
                                <th class="px-4 py-3">Merchant</th>
                                <th class="px-4 py-3">Nominal</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($riwayatPengajuan as $trx)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-800 text-sm">{{ $trx->order_id }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $trx->created_at->format('d M Y, H:i') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-blue-600">{{ $trx->user->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm font-extrabold text-gray-900">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3">
                                        @if($trx->status === 'pending')
                                            <span class="bg-orange-100 text-orange-800 px-2.5 py-1 rounded-full text-xs font-bold">Pending Approval</span>
                                        @elseif($trx->status === 'success')
                                            <span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-full text-xs font-bold">Disetujui</span>
                                        @else
                                            <span class="bg-red-100 text-red-800 px-2.5 py-1 rounded-full text-xs font-bold">Ditolak</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="lihatDetail({{ $trx->id }})" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition border border-gray-300">
                                            Lihat Detail Database
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 text-sm">Belum ada data pengajuan rantai pasok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($riwayatPengajuan->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">{{ $riwayatPengajuan->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @if($showDetailModal && $selectedTrx)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h3 class="text-lg font-bold text-gray-900">Detail Lengkap Database (Transaksi)</h3>
                <button wire:click="tutupDetail" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">DB ID</span> <span class="font-mono font-bold">{{ $selectedTrx->id }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">Order ID</span> <span class="font-mono font-bold">{{ $selectedTrx->order_id }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">User ID (Merchant)</span> <span class="font-bold">{{ $selectedTrx->user_id }} - {{ $selectedTrx->user->name ?? 'User Dihapus' }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">Tipe Transaksi</span> <span class="font-mono font-bold">{{ $selectedTrx->type }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">Status Database</span> <span class="font-bold {{ $selectedTrx->status == 'pending' ? 'text-orange-600' : 'text-gray-800' }}">{{ $selectedTrx->status }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">Total Amount</span> <span class="font-extrabold text-blue-600">Rp {{ number_format($selectedTrx->total_amount, 0, ',', '.') }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">Dibuat Pada (Created At)</span> <span>{{ $selectedTrx->created_at }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg"><span class="block text-xs text-gray-500 font-semibold uppercase">Terakhir Update (Updated At)</span> <span>{{ $selectedTrx->updated_at }}</span></div>
                <div class="bg-gray-50 p-3 rounded-lg col-span-1 md:col-span-2"><span class="block text-xs text-gray-500 font-semibold uppercase">Keterangan (Description)</span> <span class="italic">{{ $selectedTrx->description ?: '-' }}</span></div>
            </div>

            <div class="mt-6 flex justify-end pt-4 border-t">
                <button wire:click="tutupDetail" class="px-5 py-2.5 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 font-bold transition">Tutup Detail</button>
            </div>
        </div>
    </div>
    @endif
</div>