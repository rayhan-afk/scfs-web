<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\SupplyChain;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    
    // Mengambil semua data pengajuan dari database
    #[Computed]
    public function requests()
    {
        return SupplyChain::with(['merchant', 'supplier'])
            ->orderByRaw("FIELD(status, 'PENDING') DESC") // PENDING ditaruh paling atas
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Fungsi Inti: Persetujuan dan Pencairan Dana
    public function approveAndFund($id)
    {
        $supplyChain = SupplyChain::with(['merchant', 'supplier'])->find($id);
        
        if (!$supplyChain || $supplyChain->status !== 'PENDING') {
            session()->flash('error', 'Data tidak valid atau sudah diproses sebelumnya.');
            return;
        }

        // 1. Cari Dompet Modal Utama
        $masterWallet = Wallet::where('type', 'LKBB_MASTER')->first();
        
        if (!$masterWallet) {
            session()->flash('error', 'Sistem Error: Dompet Modal Utama (LKBB_MASTER) tidak ditemukan!');
            return;
        }

        // 2. Validasi Ketersediaan Saldo
        if ($masterWallet->balance < $supplyChain->capital_amount) {
            session()->flash('error', 'Gagal! Saldo LKBB_MASTER tidak mencukupi. Sisa saldo: Rp ' . number_format($masterWallet->balance, 0, ',', '.'));
            return;
        }

        // 3. Cari dompet Pemasok (Jika belum punya, otomatis dibuatkan)
        $supplierWallet = Wallet::firstOrCreate(
            ['user_id' => $supplyChain->supplier_id, 'type' => 'SUPPLIER_WALLET'], // PASTIKAN TULISAN INI BENAR
            [
                'account_number' => 'SPL-' . strtoupper(Str::random(6)), 
                'balance' => 0,
                'is_active' => true,
            ]
        );

        try {
            // Gunakan DB::transaction agar jika gagal di tengah jalan, uang tidak hilang
            DB::transaction(function () use ($supplyChain, $masterWallet, $supplierWallet) {
                
                // --- A. PROSES UANG KELUAR DARI LKBB_MASTER ---
                $txOut = Transaction::create([
                    'user_id' => $masterWallet->user_id,
                    'total_amount' => $supplyChain->capital_amount,
                    'type' => 'disbursement',
                    'status' => 'success',
                    'description' => "Pencairan Rantai Pasok (INV: {$supplyChain->invoice_number}) ke {$supplyChain->supplier->name}",
                ]);

                $masterWallet->decrement('balance', $supplyChain->capital_amount);
                
                LedgerEntry::create([
                    'transaction_id' => $txOut->id,
                    'wallet_id' => $masterWallet->id,
                    'entry_type' => 'DEBIT', // Uang Keluar
                    'amount' => $supplyChain->capital_amount,
                    'balance_after' => $masterWallet->fresh()->balance,
                ]);


                // --- B. PROSES UANG MASUK KE DOMPET PEMASOK ---
                $txIn = Transaction::create([
                    'user_id' => $supplyChain->supplier_id,
                    'total_amount' => $supplyChain->capital_amount,
                    'type' => 'funding_received',
                    'status' => 'success',
                    'description' => "Penerimaan Modal Rantai Pasok (INV: {$supplyChain->invoice_number})",
                ]);

                $supplierWallet->increment('balance', $supplyChain->capital_amount);

                LedgerEntry::create([
                    'transaction_id' => $txIn->id,
                    'wallet_id' => $supplierWallet->id,
                    'entry_type' => 'CREDIT', // Uang Masuk
                    'amount' => $supplyChain->capital_amount,
                    'balance_after' => $supplierWallet->fresh()->balance,
                ]);

                // --- C. UPDATE STATUS SUPPLY CHAIN ---
                $supplyChain->update([
                    'status' => 'FUNDED'
                ]);
            });

            session()->flash('message', 'Sukses! Pengajuan disetujui & Dana Rp ' . number_format($supplyChain->capital_amount, 0, ',', '.') . ' telah ditransfer ke Pemasok.');
            return redirect()->route('keuangan.pemasok');
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan teknis saat memproses pencairan dana.');
        }
    }
}; ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Pemasok</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola persetujuan dan pencairan dana rantai pasok.</p>
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
                        <th class="px-6 py-4 font-semibold">Invoice & Tanggal</th>
                        <th class="px-6 py-4 font-semibold">Detail Relasi</th>
                        <th class="px-6 py-4 font-semibold">Nominal Pencairan</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->requests as $req)
                        <tr class="hover:bg-gray-50 transition text-sm text-gray-700">
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-blue-600">{{ $req->invoice_number }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $req->created_at->format('d M Y, H:i') }}</div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="mb-1"><span class="text-xs font-bold text-gray-400">Merchant:</span> {{ $req->merchant->name ?? 'N/A' }}</div>
                                <div><span class="text-xs font-bold text-gray-400">Pemasok:</span> {{ $req->supplier->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500 mt-1 truncate max-w-xs" title="{{ $req->item_description }}">
                                    "{{ Str::limit($req->item_description, 30) }}"
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">Rp {{ number_format($req->capital_amount, 0, ',', '.') }}</div>
                                <div class="text-xs text-green-600 mt-1">+ Margin Rp {{ number_format($req->margin_amount, 0, ',', '.') }}</div>
                            </td>

                            <td class="px-6 py-4">
                                @if($req->status === 'PENDING')
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold">Menunggu Persetujuan</span>
                                @elseif($req->status === 'FUNDED')
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">Dana Telah Cair</span>
                                @else
                                    <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-bold">{{ $req->status }}</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                @if($req->status === 'PENDING')
                                    <button 
                                        wire:click="approveAndFund({{ $req->id }})" 
                                        wire:confirm="Anda yakin ingin menyetujui dan mentransfer modal sebesar Rp {{ number_format($req->capital_amount, 0, ',', '.') }} ke Pemasok?"
                                        class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-xs font-bold transition-colors shadow-sm inline-flex items-center gap-1">
                                        
                                        <span wire:loading.remove wire:target="approveAndFund({{ $req->id }})">Setujui & Cairkan</span>
                                        <span wire:loading wire:target="approveAndFund({{ $req->id }})">Memproses...</span>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sudah Diproses</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Belum ada data pengajuan rantai pasok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>