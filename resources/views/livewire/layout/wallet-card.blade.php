<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On; // 👈 Import ini penting buat event listener
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $saldo = 0;
    public $accountNumber = '';

    public function mount()
    {
        $this->refreshBalance();
    }

    // 👇 Fungsi sakti: Bakal jalan otomatis kalau ada sinyal 'transaction-success'
    #[On('transaction-success')] 
    public function refreshBalance()
    {
        $wallet = Auth::user()->wallet()->first(); // Ambil ulang data terbaru dari DB
        
        if ($wallet) {
            $this->saldo = $wallet->grant_balance;
            $this->accountNumber = $wallet->account_number;
        }
    }
}; ?>

<div class="p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Dompet Bantuan</h2>
            <p class="text-sm text-gray-500">No. Akun: {{ $accountNumber ?: '-' }}</p>
        </div>
        
        <div class="text-right">
            <p class="text-sm text-gray-500">Saldo Tersedia</p>
            <h1 class="text-3xl font-bold text-indigo-600">
                Rp {{ number_format($saldo, 0, ',', '.') }}
            </h1>
        </div>
    </div>
</div>