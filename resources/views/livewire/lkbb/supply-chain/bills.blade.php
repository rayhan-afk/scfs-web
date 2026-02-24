<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\SupplyChain;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    
    // Menampilkan daftar tagihan yang sudah cair (FUNDED) dan belum lunas
    #[Computed]
    public function bills()
    {
        return SupplyChain::with(['merchant', 'supplier'])
            ->whereIn('status', ['FUNDED', 'DELIVERING', 'RECEIVED'])
            ->where('payment_status', '!=', 'PAID')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function payBill($id)
    {
        $supplyChain = SupplyChain::with(['merchant'])->find($id);
        
        if (!$supplyChain) return;

        // 1. Ambil dompet Merchant (Toko yang berhutang)
        $merchantWallet = Wallet::firstOrCreate(
            ['user_id' => $supplyChain->merchant_id, 'type' => 'USER_WALLET'],
            ['account_number' => 'USR-' . strtoupper(Str::random(6)), 'balance' => 0, 'is_active' => true]
        );

        // 2. Cek apakah saldo dompet Merchant cukup untuk bayar tagihan?
        if ($merchantWallet->balance < $supplyChain->total_amount) {
            session()->flash('error', 'Gagal! Saldo Dompet Merchant (' . $supplyChain->merchant->name . ') tidak mencukupi untuk membayar tagihan ini. Silakan top up dompet Merchant terlebih dahulu.');
            return;
        }

        // 3. Ambil dompet LKBB (Master & Profit)
        $masterWallet = Wallet::where('type', 'LKBB_MASTER')->first();
        $profitWallet = Wallet::where('type', 'LKBB_PROFIT')->first();

        if (!$masterWallet || !$profitWallet) {
            session()->flash('error', 'Sistem Error: Dompet LKBB tidak ditemukan.');
            return;
        }

        try {
            DB::transaction(function () use ($supplyChain, $merchantWallet, $masterWallet, $profitWallet) {
                
                // --- A. TARIK UANG DARI DOMPET MERCHANT ---
                $txPay = Transaction::create([
                    'user_id' => $merchantWallet->user_id,
                    'total_amount' => $supplyChain->total_amount,
                    'type' => 'payment',
                    'status' => 'success',
                    'description' => "Pelunasan Tagihan Rantai Pasok (INV: {$supplyChain->invoice_number})",
                ]);

                $merchantWallet->decrement('balance', $supplyChain->total_amount);
                
                LedgerEntry::create([
                    'transaction_id' => $txPay->id,
                    'wallet_id' => $merchantWallet->id,
                    'entry_type' => 'DEBIT',
                    'amount' => $supplyChain->total_amount,
                    'balance_after' => $merchantWallet->fresh()->balance,
                ]);


                // --- B. KEMBALIKAN MODAL POKOK KE LKBB_MASTER ---
                $txMaster = Transaction::create([
                    'user_id' => $masterWallet->user_id,
                    'total_amount' => $supplyChain->capital_amount,
                    'type' => 'repayment_principal',
                    'status' => 'success',
                    'description' => "Pengembalian Modal Pokok dari INV: {$supplyChain->invoice_number}",
                ]);

                $masterWallet->increment('balance', $supplyChain->capital_amount);

                LedgerEntry::create([
                    'transaction_id' => $txMaster->id,
                    'wallet_id' => $masterWallet->id,
                    'entry_type' => 'CREDIT',
                    'amount' => $supplyChain->capital_amount,
                    'balance_after' => $masterWallet->fresh()->balance,
                ]);


                // --- C. MASUKKAN KEUNTUNGAN KE LKBB_PROFIT ---
                $txProfit = Transaction::create([
                    'user_id' => $profitWallet->user_id,
                    'total_amount' => $supplyChain->margin_amount,
                    'type' => 'repayment_margin',
                    'status' => 'success',
                    'description' => "Profit / Margin dari INV: {$supplyChain->invoice_number}",
                ]);

                $profitWallet->increment('balance', $supplyChain->margin_amount);

                LedgerEntry::create([
                    'transaction_id' => $txProfit->id,
                    'wallet_id' => $profitWallet->id,
                    'entry_type' => 'CREDIT',
                    'amount' => $supplyChain->margin_amount,
                    'balance_after' => $profitWallet->fresh()->balance,
                ]);

                // --- D. UPDATE STATUS SELESAI ---
                $supplyChain->update([
                    'status' => 'COMPLETED',
                    'payment_status' => 'PAID'
                ]);
            });

            session()->flash('message', 'Tagihan berhasil dilunasi! Modal kembali dan Keuntungan telah masuk ke Dompet Profit.');

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan sistem saat memproses pelunasan.');
        }
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tagihan Merchant</h1>
            <p class="text-gray-500 text-sm mt-1">Daftar tagihan rantai pasok yang harus dilunasi oleh Merchant.</p>
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

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                        <th class="px-6 py-4 font-semibold">Invoice</th>
                        <th class="px-6 py-4 font-semibold">Merchant / Toko</th>
                        <th class="px-6 py-4 font-semibold text-right">Tagihan Total</th>
                        <th class="px-6 py-4 font-semibold">Jatuh Tempo</th>
                        <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->bills as $bill)
                        <tr class="hover:bg-gray-50 transition text-sm text-gray-700">
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-blue-600">{{ $bill->invoice_number }}</div>
                                <div class="text-xs text-gray-500 mt-1">Status: <span class="uppercase font-bold">{{ $bill->status }}</span></div>
                            </td>

                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $bill->merchant->name ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-red-600 text-lg">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    (Pokok: Rp {{ number_format($bill->capital_amount, 0, ',', '.') }} | Margin: Rp {{ number_format($bill->margin_amount, 0, ',', '.') }})
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @php
                                    $isLate = \Carbon\Carbon::parse($bill->due_date)->isPast();
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $isLate ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ \Carbon\Carbon::parse($bill->due_date)->format('d M Y') }}
                                    @if($isLate) (Terlewat) @endif
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <button 
                                    wire:click="payBill({{ $bill->id }})" 
                                    wire:confirm="Lunasi tagihan ini? Saldo Dompet Merchant akan dipotong sebesar Rp {{ number_format($bill->total_amount, 0, ',', '.') }}."
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-bold transition-colors shadow-sm inline-flex items-center gap-1">
                                    
                                    <span wire:loading.remove wire:target="payBill({{ $bill->id }})">Bayar Tagihan</span>
                                    <span wire:loading wire:target="payBill({{ $bill->id }})">Memproses...</span>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Saat ini tidak ada tagihan rantai pasok yang belum dibayar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>