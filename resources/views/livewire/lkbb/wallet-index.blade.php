<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    
    // Variabel Form
    public $nama = '';
    public $instansi = '';
    public $amount = '';
    
    public $selectedWalletId = null;
    public $showModal = false;
    
    // Variabel penyimpan riwayat yang aman dari error cache
    public $daftarRiwayat = [];

    public function mount()
    {
        $userId = Auth::id() ?? 1;

        $defaultWallets = ['LKBB_MASTER', 'DONATION_POOL', 'LKBB_PROFIT'];
        
        foreach ($defaultWallets as $type) {
            Wallet::firstOrCreate(
                ['type' => $type],
                [
                    'user_id' => $userId,
                    'account_number' => substr($type, 0, 3) . '-' . Str::upper(Str::random(6)),
                    'balance' => 0,
                    'is_active' => true,
                ]
            );
        }
        
        // Load riwayat pertama kali halaman dibuka
        $this->loadRiwayat();
    }

    // Fungsi untuk menarik data dari database
    public function loadRiwayat()
{
    // Sekarang mengambil SEMUA riwayat transaksi dompet tanpa terkecuali
    $this->daftarRiwayat = LedgerEntry::with(['wallet', 'transaction'])
        ->orderBy('created_at', 'desc')
        ->limit(20)
        ->get();
}

    #[Computed]
    public function wallets()
    {
        // FILTER: Jangan ambil dompet milik user dengan role 'supplier' atau 'pemasok'
        $wallets = Wallet::with('user')
            ->where('type', '!=', 'SUPPLIER_WALLET')
            ->orderByRaw("FIELD(type, 'LKBB_MASTER', 'DONATION_POOL', 'LKBB_PROFIT') DESC")
            ->get();

        return $wallets->map(function ($wallet) {
            $wallet->theme = match($wallet->type) {
                // ... (biarkan bagian mapping theme/warna ini sama persis seperti kode Anda sebelumnya) ...
                'LKBB_MASTER' => ['bg' => 'bg-blue-600', 'text' => 'text-white', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m3-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Modal Kerja Utama'],
                'DONATION_POOL' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'label' => 'Penampung Donasi'],
                'LKBB_PROFIT' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Profit / Margin'],
                default => ['bg' => 'bg-white', 'text' => 'text-gray-800', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => 'Dompet User: ' . ($wallet->user->name ?? 'Unknown')]
            };
            
            $wallet->isSystem = in_array($wallet->type, ['LKBB_MASTER', 'DONATION_POOL', 'LKBB_PROFIT']);
            return $wallet;
        });
    }

    public function openTopUp($walletId)
    {
        $this->selectedWalletId = $walletId;
        $this->reset(['nama', 'instansi', 'amount']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function submitTopUp()
    {
        $this->validate([
            'nama' => 'required|string|min:3|max:255',
            'instansi' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1000',
        ]);

        $wallet = Wallet::find($this->selectedWalletId);
        if (!$wallet) return;

        try {
            DB::transaction(function () use ($wallet) {
                $transaction = Transaction::create([
                    'user_id' => Auth::id(),
                    'order_id' => null,
                    'total_amount' => $this->amount,
                    'type' => 'topup',
                    'status' => 'success',
                    'description' => "Top Up oleh: {$this->nama} ({$this->instansi})", 
                ]);

                $wallet->increment('balance', $this->amount);

                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'entry_type' => 'CREDIT',
                    'amount' => $this->amount,
                    'balance_after' => $wallet->fresh()->balance,
                ]);
            });

            // Refresh data riwayat setelah saldo bertambah
            $this->loadRiwayat();

            session()->flash('message', 'Saldo berhasil ditambahkan sebesar Rp ' . number_format($this->amount));
            $this->showModal = false;

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan sistem saat menyimpan saldo.');
        }
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Keuangan & Saldo</h1>
            <p class="text-gray-500 text-sm mt-1">Manajemen rekening sistem (Modal, Donasi, Profit).</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
       @foreach($this->wallets as $wallet)
           <x-wallet-card 
               :wallet="$wallet" 
               :theme="$wallet->theme" 
               :isSystem="$wallet->isSystem" 
           />
       @endforeach
    </div>

    <div class="mt-10">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Riwayat Suntik Saldo & Transaksi</h2>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                            <th class="px-6 py-4 font-semibold">Waktu</th>
                            <th class="px-6 py-4 font-semibold">Tujuan Dompet</th>
                            <th class="px-6 py-4 font-semibold">Keterangan / Penyetor</th>
                            <th class="px-6 py-4 font-semibold text-right">Nominal Masuk</th>
                            <th class="px-6 py-4 font-semibold text-right">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($daftarRiwayat as $history)
                            @php
                                $walletName = match($history->wallet?->type) {
                                    'LKBB_MASTER' => 'Modal Kerja Utama',
                                    'DONATION_POOL' => 'Penampung Donasi',
                                    'LKBB_PROFIT' => 'Profit / Margin',
                                    default => 'Dompet User'
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 transition text-sm text-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $history->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold">
                                        {{ $walletName }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    {{ $history->transaction?->description ?? 'Top Up Saldo' }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-green-600">
                                    + Rp {{ number_format($history->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-800">
                                    Rp {{ number_format($history->balance_after, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada riwayat transaksi atau suntik saldo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl transform transition-all">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Suntik Saldo Wallet</h3>
            <p class="text-sm text-gray-500 mb-6">Masukkan data dan nominal yang ingin ditambahkan ke sistem.</p>

            <form wire:submit="submitTopUp">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penyetor</label>
                    <input type="text" wire:model="nama" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-sm" placeholder="Contoh: Budi Santoso">
                    @error('nama') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instansi / Lembaga</label>
                    <input type="text" wire:model="instansi" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-sm" placeholder="Contoh: PT. Bank Mandiri / Pribadi">
                    @error('instansi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                    <input type="number" wire:model="amount" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-sm" placeholder="Contoh: 10000000">
                    @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold flex items-center gap-2" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitTopUp">Simpan Saldo</span>
                        <span wire:loading wire:target="submitTopUp">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>