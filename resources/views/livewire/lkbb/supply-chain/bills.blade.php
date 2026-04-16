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
    
    public $showPayModal = false;
    public $selectedId = null;

    // Fungsi untuk memicu modal muncul
    public function openPayModal($id)
    {
        $this->selectedId = $id;
        $this->showPayModal = true;
    }

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

    public function payBill()
    {
        $id = $this->selectedId;

        // Pastikan kita juga memanggil 'merchant.merchantProfile' agar tidak error
        $supplyChain = SupplyChain::with(['merchant.merchantProfile'])->find($id);
        
        if (!$supplyChain) return;

        // 1. Ambil dompet Merchant (Toko yang berhutang)
        $merchantWallet = Wallet::firstOrCreate(
            ['user_id' => $supplyChain->merchant_id, 'type' => 'USER_WALLET'],
            ['account_number' => 'USR-' . strtoupper(Str::random(6)), 'balance' => 0, 'is_active' => true]
        );

        // --- SINKRONISASI SALDO (BARU) ---
        // Samakan isi "Kantong Rahasia" dengan "Kantong Tampilan" agar tidak error gagal bayar
        if ($supplyChain->merchant && $supplyChain->merchant->merchantProfile) {
            $merchantWallet->balance = $supplyChain->merchant->merchantProfile->saldo_token;
            $merchantWallet->save();
        }

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

                // Kurangi di "Kantong Rahasia" (Wallet)
                $merchantWallet->decrement('balance', $supplyChain->total_amount);
                
                // Kurangi di "Kantong Tampilan" (Biar nominal 600.000 nya ikut berkurang!) 👇
                if ($supplyChain->merchant && $supplyChain->merchant->merchantProfile) {
                    $supplyChain->merchant->merchantProfile->decrement('saldo_token', $supplyChain->total_amount);
                }

                // --- B. KEMBALIKAN MODAL POKOK KE LKBB_MASTER ---
                $txMaster = Transaction::create([
                    'user_id' => $masterWallet->user_id,
                    'total_amount' => $supplyChain->capital_amount,
                    'type' => 'repayment_principal',
                    'status' => 'success',
                    'description' => "Pengembalian Modal Pokok dari INV: {$supplyChain->invoice_number}",
                ]);

                $masterWallet->increment('balance', $supplyChain->capital_amount);

                // --- C. MASUKKAN KEUNTUNGAN KE LKBB_PROFIT ---
                $txProfit = Transaction::create([
                    'user_id' => $profitWallet->user_id,
                    'total_amount' => $supplyChain->margin_amount,
                    'type' => 'repayment_margin',
                    'status' => 'success',
                    'description' => "Profit / Margin dari INV: {$supplyChain->invoice_number}",
                ]);

                $profitWallet->increment('balance', $supplyChain->margin_amount);

                // --- D. UPDATE STATUS SELESAI ---
                $supplyChain->update([
                    'status' => 'COMPLETED',
                    'payment_status' => 'PAID'
                ]);
            });

            $this->showPayModal = false; // Tutup modal setelah sukses
            session()->flash('message', 'Tagihan berhasil dilunasi! Modal kembali dan Keuntungan telah masuk ke Dompet Profit.');

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Sistem Error: ' . $e->getMessage() . ' di baris ' . $e->getLine());
        }
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tagihan Merchant Ke Pemasok</h1>
            <p class="text-gray-500 text-sm mt-1">Daftar tagihan rantai pasok yang harus dilunasi oleh Merchant, Saldo Token Masuk Ke Pemasok.</p>
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
                                    wire:click="openPayModal({{ $bill->id }})" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-bold transition-colors shadow-sm">
                                    Bayar Tagihan
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

    {{-- MODAL KONFIRMASI PEMBAYARAN --}}
    @if($showPayModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        
                        <h3 class="text-xl leading-6 font-bold text-gray-900">Konfirmasi Pelunasan</h3>
                        
                        @php
                            $selectedBill = \App\Models\SupplyChain::find($selectedId);
                        @endphp

                        <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-100 text-left">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-500">Invoice:</span>
                                <span class="font-bold text-blue-600">{{ $selectedBill->invoice_number ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-500">Merchant:</span>
                                <span class="font-medium text-gray-800">{{ $selectedBill->merchant->name ?? '-' }}</span>
                            </div>
                            <div class="border-t border-dashed border-gray-300 my-2"></div>
                            <div class="flex justify-between text-base">
                                <span class="font-bold text-gray-700">Total Potong Saldo:</span>
                                <span class="font-black text-red-600">Rp {{ number_format($selectedBill->total_amount ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <p class="mt-4 text-sm text-gray-500 italic">
                            *Saldo Dompet Merchant akan otomatis dipotong untuk pengembalian Modal & Profit LKBB.
                        </p>
                    </div>

                    <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button wire:click="payBill" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-green-600 text-white font-bold hover:bg-green-700 transition-all sm:w-auto text-sm">
                            <span wire:loading.remove wire:target="payBill">Ya, Bayar Sekarang</span>
                            <span wire:loading wire:target="payBill">Memproses...</span>
                        </button>
                        <button wire:click="$set('showPayModal', false)" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 px-6 py-2.5 bg-white text-gray-700 font-bold hover:bg-gray-50 sm:mt-0 sm:w-auto text-sm transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>