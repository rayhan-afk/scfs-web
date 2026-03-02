<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $search = '';
    public $tab = 'pending'; // 'pending', 'success', 'failed'

    // Form Simulasi Request Withdrawal (Untuk Testing)
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
        $withdrawals = Transaction::with('user')
            ->where('type', 'withdrawal')
            ->where('status', $this->tab)
            ->when($this->search, function($query) {
                $query->whereHas('user', function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                })->orWhere('order_id', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return [
            'withdrawals' => $withdrawals,
            // Mengambil user yang punya saldo > 0 untuk simulasi
            'usersForSimulasi' => User::whereHas('wallets', function($q) {
                $q->where('balance', '>', 0);
            })->get()
        ];
    }

    // --- FUNGSI INTI: PERSETUJUAN PENCAIRAN DANA ---
    public function approveWithdrawal($transactionId)
    {
        $trx = Transaction::with('user')->find($transactionId);
        if (!$trx || $trx->status !== 'pending') return;

        // 1. Deteksi otomatis tipe dompet berdasarkan Role User
        $role = strtolower($trx->user->role);
        $walletType = match(true) {
            in_array($role, ['supplier', 'pemasok']) => 'SUPPLIER_WALLET',
            in_array($role, ['mahasiswa', 'student']) => 'STUDENT_WALLET',
            default => 'USER_WALLET' // Default untuk Merchant
        };

        // 2. Kunci Dompet User
        $wallet = Wallet::where('user_id', $trx->user_id)->where('type', $walletType)->first();

        if (!$wallet || $wallet->balance < $trx->total_amount) {
            session()->flash('error', 'Gagal! Saldo dompet ' . $trx->user->name . ' tidak mencukupi untuk penarikan ini.');
            return;
        }

        try {
            DB::transaction(function () use ($trx, $wallet) {
                // A. Ubah status transaksi
                $trx->update(['status' => 'success']);

                // B. Potong Saldo Digital User (Karena uangnya ditransfer ke Bank Asli)
                $wallet->decrement('balance', $trx->total_amount);

                // C. Catat di Buku Besar sebagai DEBIT (Uang Keluar)
                LedgerEntry::create([
                    'transaction_id' => $trx->id,
                    'wallet_id' => $wallet->id,
                    'entry_type' => 'DEBIT',
                    'amount' => $trx->total_amount,
                    'balance_after' => $wallet->fresh()->balance,
                ]);
            });

            session()->flash('message', 'Pencairan Rp ' . number_format($trx->total_amount, 0, ',', '.') . ' untuk ' . $trx->user->name . ' berhasil disetujui!');
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan sistem saat memotong saldo.');
        }
    }

    // --- FUNGSI INTI: TOLAK PENCAIRAN ---
    public function rejectWithdrawal($transactionId)
    {
        $trx = Transaction::find($transactionId);
        if ($trx && $trx->status === 'pending') {
            $trx->update([
                'status' => 'failed',
                'description' => $trx->description . ' (Ditolak oleh Admin)'
            ]);
            session()->flash('message', 'Permintaan pencairan dana telah ditolak.');
        }
    }

    // --- FUNGSI TESTING: Membuat Request Penarikan Dummy ---
    public function submitSimulasi()
    {
        $this->validate([
            'simulasiUserId' => 'required',
            'simulasiAmount' => 'required|numeric|min:10000',
        ]);

        Transaction::create([
            'user_id' => $this->simulasiUserId,
            'order_id' => 'WD-' . Str::upper(Str::random(8)),
            'total_amount' => $this->simulasiAmount,
            'type' => 'withdrawal',
            'status' => 'pending',
            'description' => 'Request Penarikan Saldo ke Rekening Bank',
        ]);

        $this->showSimulasiModal = false;
        $this->reset(['simulasiUserId', 'simulasiAmount']);
        session()->flash('message', 'Simulasi Request Pencairan berhasil dibuat! Silakan setujui di tabel Pending.');
    }
}; ?>

<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pencairan Dana (Settlement)</h1>
            <p class="text-gray-500 text-sm mt-1">Persetujuan penarikan saldo digital ke rekening bank pengguna.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari ID / Nama..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <button wire:click="$set('showSimulasiModal', true)" class="px-4 py-2 bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-100 text-sm font-bold whitespace-nowrap transition">
                + Simulasi Request
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Berhasil!</strong> {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Perhatian!</strong> {{ session('error') }}
        </div>
    @endif

    <div class="flex gap-4 border-b border-gray-200 mb-6">
        <button wire:click="$set('tab', 'pending')" class="pb-3 px-1 border-b-2 font-semibold text-sm transition-colors {{ $tab === 'pending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Menunggu Persetujuan
        </button>
        <button wire:click="$set('tab', 'success')" class="pb-3 px-1 border-b-2 font-semibold text-sm transition-colors {{ $tab === 'success' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Berhasil Ditransfer
        </button>
        <button wire:click="$set('tab', 'failed')" class="pb-3 px-1 border-b-2 font-semibold text-sm transition-colors {{ $tab === 'failed' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Ditolak
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-6 py-4">ID Transaksi & Waktu</th>
                        <th class="px-6 py-4">Pengguna (Role)</th>
                        <th class="px-6 py-4">Nominal Penarikan</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($withdrawals as $wd)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $wd->order_id }}</div>
                                <div class="text-xs text-gray-400 mt-1">{{ $wd->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-blue-600">{{ $wd->user->name ?? 'User Terhapus' }}</div>
                                <div class="text-xs bg-gray-100 inline-block px-2 py-0.5 rounded text-gray-600 mt-1 font-semibold uppercase">
                                    {{ $wd->user->role ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 font-extrabold text-gray-900">
                                Rp {{ number_format($wd->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($wd->status === 'pending')
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold">Pending</span>
                                @elseif($wd->status === 'success')
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">Selesai</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold">Ditolak</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($wd->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="rejectWithdrawal({{ $wd->id }})" wire:confirm="Tolak penarikan ini?" class="px-3 py-1.5 bg-red-100 text-red-600 rounded-lg text-xs font-bold hover:bg-red-200 transition">Tolak</button>
                                        <button wire:click="approveWithdrawal({{ $wd->id }})" wire:confirm="Setujui dan potong saldo pengguna sebesar Rp {{ number_format($wd->total_amount, 0, ',', '.') }}?" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">Setujui</button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada aksi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                Tidak ada data pencairan untuk tab <strong>{{ $tab }}</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($withdrawals->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">{{ $withdrawals->links() }}</div>
        @endif
    </div>

    @if($showSimulasiModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Simulasi Penarikan (Merchant/Supplier)</h3>
            <p class="text-xs text-gray-500 mb-6">Gunakan form ini untuk membuat contoh request penarikan seolah-olah dilakukan oleh pengguna.</p>

            <form wire:submit="submitSimulasi">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Pengguna</label>
                    <select wire:model="simulasiUserId" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm py-2 px-3">
                        <option value="">-- Pilih Pengguna --</option>
                        @foreach($usersForSimulasi as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                        @endforeach
                    </select>
                    @error('simulasiUserId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                    <input type="number" wire:model="simulasiAmount" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-sm" placeholder="Contoh: 100000">
                    @error('simulasiAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="$set('showSimulasiModal', false)" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold">Buat Request</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>