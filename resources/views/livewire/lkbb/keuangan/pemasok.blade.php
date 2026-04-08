<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $search = '';
    
    // State Modal
    public $isTopupModalOpen = false;
    public $isHistoryModalOpen = false;
    
    public $selectedUser = null;
    public $topupAmount = 0;
    public $historyData = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with()
    {
       $suppliers = User::where('name', 'like', '%'.$this->search.'%')->paginate(10);

        // KUNCI PERBAIKAN: Kita paksa sistem hanya mengambil SUPPLIER_WALLET
        foreach ($suppliers as $supplier) {
            $supplier->dompet_pemasok = Wallet::where('user_id', $supplier->id)
                                        ->where('type', 'SUPPLIER_WALLET')
                                        ->first();
        }

        return [
            'suppliers' => $suppliers
        ];
    }

    // Modal Topup
    public function openTopup($id) {
        $this->selectedUser = User::find($id);
        $this->topupAmount = null;
        $this->isTopupModalOpen = true;
    }
    public function closeTopup() {
        $this->isTopupModalOpen = false;
        $this->selectedUser = null;
    }

    // Modal Riwayat 
    public function openHistory($id) {
        $this->selectedUser = User::find($id);
        
        // Cari dompet pemasok secara spesifik
        $dompet = Wallet::where('user_id', $id)->where('type', 'SUPPLIER_WALLET')->first();
        
        if ($dompet) {
            $this->historyData = LedgerEntry::with('transaction')
                                ->where('wallet_id', $dompet->id)
                                ->latest()
                                ->get();
        } else {
            $this->historyData = [];
        }
                                
        $this->isHistoryModalOpen = true;
    }
    public function closeHistory() {
        $this->isHistoryModalOpen = false;
        $this->selectedUser = null;
        $this->historyData = [];
    }

    // Submit Saldo 
    public function submitTopup()
    {
        $this->validate([
            'topupAmount' => 'required|numeric|min:1000'
        ]);

        if($this->selectedUser) {
            try {
                DB::transaction(function () {
                    $supplierWallet = Wallet::firstOrCreate(
                        ['user_id' => $this->selectedUser->id, 'type' => 'SUPPLIER_WALLET'],
                        [
                            'account_number' => 'SPL-' . strtoupper(Str::random(6)),
                            'balance' => 0,
                            'is_active' => true,
                        ]
                    );

                    $txIn = Transaction::create([
                        'user_id' => $this->selectedUser->id,
                        'total_amount' => $this->topupAmount,
                        'type' => 'topup',
                        'status' => 'success',
                        'description' => "Suntik Saldo Manual Pemasok",
                    ]);

                    $supplierWallet->increment('balance', $this->topupAmount);

                    LedgerEntry::create([
                        'transaction_id' => $txIn->id,
                        'wallet_id' => $supplierWallet->id,
                        'entry_type' => 'CREDIT',
                        'amount' => $this->topupAmount,
                        'balance_after' => $supplierWallet->fresh()->balance,
                    ]);
                });

                session()->flash('message', 'Suntik saldo berhasil!');
                $this->closeTopup();

            } catch (\Exception $e) {
                session()->flash('error', 'Gagal memproses suntik saldo.');
                report($e);
            }
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Keuangan Pemasok (Supplier)</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola dompet dan riwayat transaksi khusus pemasok.</p>
        </div>
        <div class="w-72 relative">
            <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama pemasok..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-4 shadow-sm">
            <strong class="font-bold">Sukses!</strong> {{ session('message') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-500 border-b border-gray-200 uppercase tracking-wider">
                    <th class="py-4 px-6 font-bold">Data Pemasok</th>
                    <th class="py-4 px-6 font-bold">Saldo Dompet</th>
                    <th class="py-4 px-6 font-bold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-600 divide-y divide-gray-100">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-blue-50/50 transition">
                    <td class="py-4 px-6">
                        <div class="font-bold text-gray-800 text-base">{{ $supplier->name }}</div>
                        <div class="text-xs text-gray-400 mt-1">
                            No. Rek: {{ $supplier->dompet_pemasok->account_number ?? 'Belum ada dompet' }}
                        </div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-extrabold text-blue-600 text-lg">
                            Rp {{ number_format($supplier->dompet_pemasok->balance ?? 0, 0, ',', '.') }}
                        </div>
                    </td>
                    <td class="py-4 px-6 text-right">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openHistory({{ $supplier->id }})" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition">
                                Riwayat Transaksi
                            </button>
                            <button wire:click="openTopup({{ $supplier->id }})" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition shadow-sm">
                                + Suntik Saldo
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-10 text-gray-500">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($suppliers->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $suppliers->links() }}</div>
        @endif
    </div>

    @if($isTopupModalOpen && $selectedUser)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-800">Suntik Saldo Pemasok</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $selectedUser->name }}</p>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nominal Suntik (Rp)</label>
                <input wire:model="topupAmount" type="number" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: 500000">
            </div>
            <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button wire:click="closeTopup" class="px-4 py-2 text-gray-600 bg-gray-200 hover:bg-gray-300 rounded-xl text-sm font-bold transition">Batal</button>
                <button wire:click="submitTopup" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-sm font-bold transition">Suntik Saldo</button>
            </div>
        </div>
    </div>
    @endif

    @if($isHistoryModalOpen && $selectedUser)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-3xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div>
                    <h3 class="font-bold text-lg">Riwayat Dompet</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $selectedUser->name }}</p>
                </div>
                <button wire:click="closeHistory" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-0 overflow-y-auto flex-1">
                <table class="w-full text-left">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr class="text-xs text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3 px-6 font-bold">Waktu</th>
                            <th class="py-3 px-6 font-bold">Keterangan</th>
                            <th class="py-3 px-6 font-bold text-right">Nominal</th>
                            <th class="py-3 px-6 font-bold text-right">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-100">
                        @forelse($historyData as $ledger)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-6 text-gray-500">{{ $ledger->created_at->format('d M Y, H:i') }}</td>
                            <td class="py-3 px-6 text-gray-800">{{ $ledger->transaction->description ?? 'Transaksi Sistem' }}</td>
                            <td class="py-3 px-6 text-right font-bold {{ $ledger->entry_type == 'CREDIT' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $ledger->entry_type == 'CREDIT' ? '+' : '-' }} Rp {{ number_format($ledger->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-6 text-right text-gray-600">Rp {{ number_format($ledger->balance_after, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">Belum ada riwayat transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>