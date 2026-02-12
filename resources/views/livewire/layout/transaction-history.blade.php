<?php

use Livewire\Volt\Component;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On; // Biar tabel auto-update pas habis beli

new class extends Component {
    public $transactions = [];

    public function mount()
    {
        $this->loadTransactions();
    }

    #[On('transaction-success')] 
    public function loadTransactions()
    {
        // Ambil transaksi milik user login, urutkan dari yang terbaru
        $this->transactions = Transaction::where('user_id', Auth::id())
            ->latest()
            ->limit(10) // Tampilkan 10 terakhir aja biar gak kepanjang
            ->get();
    }
}; ?>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Riwayat Transaksi Terakhir</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $trx)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $trx->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $trx->order_id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                            - Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($trx->status == 'success')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Berhasil
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    {{ ucfirst($trx->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                            Belum ada transaksi. Ayo jajan!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>