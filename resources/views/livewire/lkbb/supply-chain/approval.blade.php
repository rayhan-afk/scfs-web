<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\SupplyChain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

new #[Layout('layouts.lkbb')] class extends Component {
    
    public $showApproveModal = false;
    public $showRejectModal = false;
    public $selectedId = null;
    public $rejectReason = '';

    #[Computed]
    public function pendingRequests()
    {
        return SupplyChain::with(['merchant', 'supplier'])
            ->where('status', 'PENDING') 
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openApproveModal($id)
    {
        $this->selectedId = $id;
        $this->showApproveModal = true;
    }

    public function approve()
    {
        try {
            DB::transaction(function () {
                $request = SupplyChain::where('id', $this->selectedId)
                    ->where('status', 'PENDING')
                    ->lockForUpdate()
                    ->firstOrFail();

                $request->update([
                    'status' => 'FUNDED',
                    'updated_at' => now(),
                ]);

                \App\Models\SupplyOrder::where('id_pengajuan', $request->invoice_number)
                    ->update([
                        'status_pembiayaan' => 'siap_diproduksi'
                    ]);
            });

            $this->showApproveModal = false; // TUTUP MODAL SETELAH SUKSES
            session()->flash('message', "Pengajuan PO berhasil disetujui.");
        } catch (\Exception $e) {
            \Log::error("Approval PO Failed: " . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyetujui data.');
        }
    }

    public function openRejectModal($id)
    {
        $this->selectedId = $id;
        $this->rejectReason = ''; 
        $this->showRejectModal = true;
    }

    public function confirmReject()
    {
        $this->validate([
            'rejectReason' => 'required|string|min:5|max:255',
        ]);

        try {
            DB::transaction(function () {
                $request = SupplyChain::where('id', $this->selectedId)
                    ->where('status', 'PENDING')
                    ->lockForUpdate()
                    ->firstOrFail();

                $request->update([
                    'status' => 'REJECTED',
                    'updated_at' => now(),
                ]);

                \App\Models\SupplyOrder::where('id_pengajuan', $request->invoice_number)
                    ->update([
                        'status_pembiayaan' => 'siap_diajukan',
                        'id_pengajuan' => null
                    ]);
            });

            $this->showRejectModal = false; // TUTUP MODAL SETELAH SUKSES
            session()->flash('message', 'Pengajuan berhasil ditolak.');
        } catch (\Exception $e) {
            \Log::error("Reject PO Failed: " . $e->getMessage());
            session()->flash('error', 'Gagal menolak pengajuan.');
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Pembiayaan Rantai Pasok</h1>
            <p class="text-gray-500 text-sm mt-1">Daftar pengajuan PO yang membutuhkan persetujuan pendanaan.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            {{ session('error') }}
        </div>
    @endif
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID PO</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Merchant</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pembiayaan</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->pendingRequests as $req)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">#PO-{{ str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $req->merchant->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-sm font-bold">Rp {{ number_format($req->capital_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="openApproveModal({{ $req->id }})" class="text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-md text-xs font-bold mr-2">Approve</button>
                            <button wire:click="openRejectModal({{ $req->id }})" class="text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded-md text-xs font-bold">Reject</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MODAL REJECT --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-50">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900">Tolak Pengajuan PO #{{ $selectedId }}</h3>
                        <p class="text-xs text-red-500 mt-1 italic">*Tindakan ini akan membatalkan pengajuan secara permanen.</p>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Penolakan</label>
                            <textarea wire:model="rejectReason" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"></textarea>
                            @error('rejectReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
                        <button wire:click="confirmReject" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-white font-medium hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Konfirmasi Penolakan</button>
                        <button wire:click="$set('showRejectModal', false)" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL APPROVE --}}
    @if($showApproveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-50">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Konfirmasi Persetujuan</h3>
                        <p class="text-sm text-gray-500 mt-2">Setujui pendanaan untuk #PO-{{ str_pad($selectedId, 5, '0', STR_PAD_LEFT) }}?</p>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
                        <button wire:click="approve" type="button" class="w-full inline-flex justify-center rounded-md bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Ya, Setujui</button>
                        <button wire:click="$set('showApproveModal', false)" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>