<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\SupplyChain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

new #[Layout('layouts.lkbb')] class extends Component {
    
    // Variabel Form
    public $supplier_id = '';
    public $item_description = '';
    public $capital_amount = '';
    public $due_date = '';

    // Mengambil daftar user (Selain diri sendiri) untuk dijadikan Pilihan Pemasok
    #[Computed]
    public function suppliers()
    {
        return User::where('id', '!=', Auth::id())->get();
    }

    public function submitRequest()
    {
        // 1. Validasi Input
        $this->validate([
            'supplier_id' => 'required|exists:users,id',
            'item_description' => 'required|string|min:10',
            'capital_amount' => 'required|numeric|min:50000',
            'due_date' => 'required|date|after:today',
        ]);

        // 2. Kalkulasi Bisnis LKBB (Misal: Margin keuntungan LKBB ditetapkan 10%)
        $marginPercentage = 10;
        $marginAmount = ($this->capital_amount * $marginPercentage) / 100;
        $totalAmount = $this->capital_amount + $marginAmount;

        try {
            // 3. Simpan ke Database dengan status PENDING
            SupplyChain::create([
                'merchant_id' => Auth::id(),
                'supplier_id' => $this->supplier_id,
                'item_description' => $this->item_description,
                'capital_amount' => $this->capital_amount,
                'margin_amount' => $marginAmount,
                'total_amount' => $totalAmount,
                'due_date' => Carbon::parse($this->due_date),
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
            ]);

            // 4. Reset form dan tampilkan pesan sukses
            $this->reset(['supplier_id', 'item_description', 'capital_amount', 'due_date']);
            session()->flash('message', 'Pengajuan berhasil dikirim! Menunggu persetujuan Admin LKBB.');

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan sistem saat menyimpan pengajuan.');
        }
    }
}; ?>

<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Pengajuan Rantai Pasok Baru</h1>
        <p class="text-gray-500 text-sm mt-1">Ajukan pembiayaan pembuatan barang ke sistem LKBB.</p>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 shadow-sm">
            <strong class="font-bold">Berhasil!</strong>
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 md:p-8">
            <form wire:submit="submitRequest">
                
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Pemasok / Vendor</label>
                    <select wire:model="supplier_id" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-3 bg-gray-50">
                        <option value="">-- Pilih Pemasok --</option>
                        @foreach($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->email }})</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan Barang Pesanan</label>
                    <textarea wire:model="item_description" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-3 bg-gray-50" placeholder="Contoh: Pembuatan 1000 pcs Seragam Kerja PT. Maju Mundur ukuran variatif."></textarea>
                    <p class="text-xs text-gray-400 mt-1">Sertakan detail jumlah, jenis, dan spesifikasi barang secara lengkap.</p>
                    @error('item_description') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Modal Pencairan ke Pemasok (Rp)</label>
                        <input type="number" wire:model.live.debounce.500ms="capital_amount" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-3 bg-gray-50" placeholder="Contoh: 15000000">
                        @error('capital_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Target Tanggal Pelunasan (Jatuh Tempo)</label>
                        <input type="date" wire:model="due_date" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-3 bg-gray-50">
                        @error('due_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($capital_amount && is_numeric($capital_amount))
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-8">
                    <h3 class="text-sm font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Simulasi Perhitungan LKBB (Margin 10%)
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Dana cair ke Pemasok:</span>
                            <span class="font-medium">Rp {{ number_format($capital_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Margin / Fee LKBB:</span>
                            <span class="font-medium">+ Rp {{ number_format($capital_amount * 0.10, 0, ',', '.') }}</span>
                        </div>
                        <div class="pt-2 mt-2 border-t border-blue-200 flex justify-between font-bold text-blue-900 text-base">
                            <span>Total Tagihan Anda Nanti:</span>
                            <span>Rp {{ number_format($capital_amount + ($capital_amount * 0.10), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-bold flex items-center gap-2 transition-colors shadow-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitRequest">Kirim Pengajuan</span>
                        <span wire:loading wire:target="submitRequest">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>