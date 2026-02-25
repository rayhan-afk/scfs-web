<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $search = '';
    public $tab = 'pending'; // 'pending' (Belum Lunas), 'success' (Lunas)

    // Form Simulasi (Untuk Testing)
    public $showSimulasiModal = false;
    public $simulasiUserId = '';
    public $simulasiAmount = '';

    public function updatingSearch() {
        $this->resetPage();
    }

    public function updatingTab() {
        $this->resetPage();
    }

    public function with()
    {
        // Mengambil transaksi dengan tipe 'tagihan_merchant'
        $tagihan = Transaction::with('user')
            ->where('type', 'tagihan_merchant')
            ->where('status', $this->tab)
            ->when($this->search, function($query) {
                $query->whereHas('user', function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                })->orWhere('order_id', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return [
            'daftarTagihan' => $tagihan,
            // Mengambil user merchant untuk dropdown simulasi
            'merchants' => User::whereIn('role', ['merchant', 'Merchant'])->get()
        ];
    }

    // --- FUNGSI INTI: TERIMA SETORAN TUNAI ---
    public function terimaSetoran($transactionId)
    {
        $trx = Transaction::with('user')->find($transactionId);
        if (!$trx || $trx->status !== 'pending') return;

        try {
            DB::transaction(function () use ($trx) {
                // 1. Ubah status tagihan menjadi lunas ('success')
                $trx->update([
                    'status' => 'success',
                    'description' => $trx->description . ' (Telah disetorkan tunai ke LKBB)'
                ]);

                // 2. Tambahkan saldo ke Brankas Sistem (LKBB_MASTER)
                // Karena uang fisiknya sudah diterima oleh Admin LKBB
                $lkbbWallet = Wallet::firstOrCreate(
                    ['type' => 'LKBB_MASTER'],
                    ['account_number' => 'SYS-LKBB-001', 'balance' => 0, 'is_active' => true]
                );

                $lkbbWallet->increment('balance', $trx->total_amount);

                // 3. Catat di Buku Besar sistem
                LedgerEntry::create([
                    'transaction_id' => $trx->id,
                    'wallet_id' => $lkbbWallet->id,
                    'entry_type' => 'CREDIT',
                    'amount' => $trx->total_amount,
                    'balance_after' => $lkbbWallet->fresh()->balance,
                ]);
            });

            session()->flash('message', 'Setoran tunai sebesar Rp ' . number_format($trx->total_amount, 0, ',', '.') . ' dari ' . $trx->user->name . ' berhasil diterima dan dilunaskan!');
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan sistem saat memproses pelunasan.');
        }
    }

    // --- FUNGSI TESTING: Membuat Tagihan Dummy ---
    public function submitSimulasi()
    {
        $this->validate([
            'simulasiUserId' => 'required',
            'simulasiAmount' => 'required|numeric|min:5000',
        ]);

        Transaction::create([
            'user_id' => $this->simulasiUserId,
            'order_id' => 'TGH-' . Str::upper(Str::random(8)),
            'total_amount' => $this->simulasiAmount,
            'type' => 'tagihan_merchant',
            'status' => 'pending',
            'description' => 'Tagihan setoran tunai dari transaksi Mahasiswa',
        ]);

        $this->showSimulasiModal = false;
        $this->reset(['simulasiUserId', 'simulasiAmount']);
        session()->flash('message', 'Simulasi Tagihan Merchant berhasil dibuat!');
    }
}; ?>

<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Penagihan Merchant (Collection)</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola tagihan Merchant atas penerimaan uang tunai dari Mahasiswa.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari Merchant/ID..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <button wire:click="$set('showSimulasiModal', true)" class="px-4 py-2 bg-orange-50 text-orange-600 border border-orange-200 rounded-lg hover:bg-orange-100 text-sm font-bold whitespace-nowrap transition">
                + Simulasi Tagihan
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Berhasil!</strong> {{ session('message') }}
        </div>
    @endif

    <div class="flex gap-4 border-b border-gray-200 mb-6">
        <button wire:click="$set('tab', 'pending')" class="pb-3 px-1 border-b-2 font-semibold text-sm transition-colors {{ $tab === 'pending' ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Menunggu Setoran (Hutang)
        </button>
        <button wire:click="$set('tab', 'success')" class="pb-3 px-1 border-b-2 font-semibold text-sm transition-colors {{ $tab === 'success' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Sudah Lunas
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-6 py-4">ID Tagihan & Waktu</th>
                        <th class="px-6 py-4">Nama Merchant</th>
                        <th class="px-6 py-4">Nominal Disetorkan</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($daftarTagihan as $trx)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $trx->order_id }}</div>
                                <div class="text-xs text-gray-400 mt-1">{{ $trx->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-blue-600">{{ $trx->user->name ?? 'User Terhapus' }}</div>
                            </td>
                            <td class="px-6 py-4 font-extrabold text-gray-900">
                                Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($trx->status === 'pending')
                                    <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-xs font-bold">Belum Disetor</span>
                                @else
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">Lunas</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($trx->status === 'pending')
                                    <button wire:click="terimaSetoran({{ $trx->id }})" wire:confirm="Terima uang fisik tunai sebesar Rp {{ number_format($trx->total_amount, 0, ',', '.') }} dari Merchant ini?" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition shadow-sm">
                                        Terima Uang & Lunaskan
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada data tagihan untuk tab <strong>{{ $tab === 'pending' ? 'Belum Disetor' : 'Lunas' }}</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($daftarTagihan->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">{{ $daftarTagihan->links() }}</div>
        @endif
    </div>

    @if($showSimulasiModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Simulasi Tagihan Merchant</h3>
            <p class="text-xs text-gray-500 mb-6">Buat tagihan seolah-olah Merchant baru saja menerima pembayaran tunai dari Mahasiswa.</p>

            <form wire:submit="submitSimulasi">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Merchant</label>
                    <select wire:model="simulasiUserId" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm py-2 px-3">
                        <option value="">-- Pilih Merchant --</option>
                        @foreach($merchants as $merchant)
                            <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                        @endforeach
                    </select>
                    @error('simulasiUserId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Tunai (Rp)</label>
                    <input type="number" wire:model="simulasiAmount" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-sm" placeholder="Contoh: 150000">
                    @error('simulasiAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="$set('showSimulasiModal', false)" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-semibold">Buat Tagihan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>