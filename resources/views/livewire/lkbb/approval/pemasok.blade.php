<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\SupplierProfile;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $selectedSupplier = null;
    public bool $showModal = false;
    
    // Form States
    public string $catatan_penolakan = ''; 

    #[Computed]
    public function pendingSuppliers()
    {
        return SupplierProfile::with('user')
            ->where('status_verifikasi', 'menunggu_review')
            ->latest()
            ->paginate(10);
    }

    public function openModal($id)
    {
        $this->selectedSupplier = SupplierProfile::with('user')->findOrFail($id);
        $this->catatan_penolakan = ''; 
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedSupplier = null;
        $this->resetValidation();
    }

    public function approveSupplier()
    {
        if (!$this->selectedSupplier || $this->selectedSupplier->status_verifikasi !== 'menunggu_review') {
            session()->flash('error', 'Aksi tidak valid atau status pemasok sudah berubah.');
            return $this->closeModal();
        }

        // Update Database ke 'disetujui'
        $this->selectedSupplier->update([
            'status_verifikasi' => 'disetujui',
        ]);
        
        session()->flash('message', "Pemasok {$this->selectedSupplier->nama_usaha} berhasil diaktifkan!");
        $this->closeModal();
    }

    public function rejectSupplier()
    {
        $this->validate([
            'catatan_penolakan' => 'required|string|min:5'
        ]);

        if (!$this->selectedSupplier || $this->selectedSupplier->status_verifikasi !== 'menunggu_review') {
            session()->flash('error', 'Aksi tidak valid.');
            return $this->closeModal();
        }

        $this->selectedSupplier->update([
            'status_verifikasi' => 'ditolak',
            'catatan_penolakan' => $this->catatan_penolakan
        ]);
        
        session()->flash('error', "Pendaftaran {$this->selectedSupplier->nama_usaha} ditolak.");
        $this->closeModal();
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Pemasok / Grosir</h1>
            <p class="text-gray-500 text-sm mt-1">Verifikasi legalitas gudang dan data pemilik pemasok rantai pasok.</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] text-gray-400 border-b border-gray-100 uppercase tracking-wider bg-gray-50/50">
                        <th class="py-4 px-6 font-bold">Data Usaha / Grosir</th>
                        <th class="py-4 px-6 font-bold">Data Pemilik</th>
                        <th class="py-4 px-6 font-bold">Alamat Gudang</th>
                        <th class="py-4 px-6 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                    @forelse($this->pendingSuppliers as $profile)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-900 text-sm">{{ $profile->nama_usaha }}</div>
                            <div class="text-[11px] text-gray-500 mt-1">WA: <span class="font-medium text-gray-700">{{ $profile->no_hp ?? '-' }}</span></div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-gray-900 font-medium text-sm">{{ $profile->nama_pemilik }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5 font-mono">{{ $profile->nik ?? '-' }}</div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-gray-800 text-xs line-clamp-1">{{ $profile->alamat_gudang ?? '-' }}</div>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <button wire:click="openModal({{ $profile->id }})" class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold hover:bg-blue-100 transition shadow-sm">
                                Review Pemasok
                                <svg class="w-3.5 h-3.5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-16">
                            <div class="text-4xl mb-3">🚚</div>
                            <div class="text-gray-900 font-bold">Belum ada pengajuan pemasok</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $this->pendingSuppliers->links() }}</div>
    </div>

    {{-- Modal Review --}}
    @if($showModal && $selectedSupplier)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh] overflow-hidden">
                
                <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-black text-gray-900">Verifikasi Data Pemasok</h3>
                        <p class="text-[10px] text-blue-600 uppercase tracking-widest font-black mt-1">ID REG: SC-SUPP-{{ str_pad($selectedSupplier->id, 4, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto flex-1 bg-white">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                        
                        {{-- Sisi Kiri: Informasi --}}
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Alamat Gudang / Kantor</span>
                                    <span class="text-sm font-bold text-gray-800">{{ $selectedSupplier->alamat_gudang }}</span>
                                </div>
                                <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest block mb-1">Info Rekening</span>
                                    <span class="text-sm font-black text-blue-800">{{ $selectedSupplier->info_rekening ?? '-' }}</span>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">NIK Pemilik</span>
                                    <span class="text-sm font-bold text-gray-800">{{ $selectedSupplier->nik }}</span>
                                </div>
                            </div>

                            <div class="bg-red-50 p-5 rounded-2xl border border-red-100">
                                <label class="block text-[10px] font-bold text-red-700 uppercase tracking-widest mb-3">Catatan Penolakan (Wajib jika ditolak)</label>
                                <textarea wire:model="catatan_penolakan" class="w-full text-sm rounded-xl border-red-200 focus:ring-red-500 bg-white" rows="3" placeholder="Sebutkan kekurangan data pemasok..."></textarea>
                                @error('catatan_penolakan') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Sisi Kanan: Foto --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div class="group relative">
                                <span class="text-[10px] font-bold text-gray-400 uppercase mb-2 block">Foto KTP Pemilik</span>
                                <div class="rounded-2xl overflow-hidden border-2 border-gray-100">
                                    @if($selectedSupplier->foto_ktp)
                                        <img src="{{ asset('storage/' . $selectedSupplier->foto_ktp) }}" class="w-full h-48 object-cover">
                                    @else
                                        <div class="h-48 bg-gray-50 flex items-center justify-center text-xs text-gray-400 italic">Tidak ada foto KTP</div>
                                    @endif
                                </div>
                            </div>
                            <div class="group relative">
                                <span class="text-[10px] font-bold text-gray-400 uppercase mb-2 block">Foto Depan Gudang/Usaha</span>
                                <div class="rounded-2xl overflow-hidden border-2 border-gray-100">
                                    @if($selectedSupplier->foto_usaha)
                                        <img src="{{ asset('storage/' . $selectedSupplier->foto_usaha) }}" class="w-full h-48 object-cover">
                                    @else
                                        <div class="h-48 bg-gray-50 flex items-center justify-center text-xs text-gray-400 italic">Tidak ada foto usaha</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="px-8 py-6 border-t border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <button wire:click="closeModal" class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition">Tutup</button>
                    <div class="flex gap-4">
                        <button wire:click="rejectSupplier" wire:confirm="Tolak pengajuan pemasok ini?" class="px-6 py-3 text-sm font-bold text-red-600 bg-white border border-red-200 rounded-2xl hover:bg-red-50 transition">
                            Tolak Pendaftaran
                        </button>
                        <button wire:click="approveSupplier" wire:confirm="Setujui pendaftaran pemasok ini?" class="px-8 py-3 text-sm font-bold text-white bg-[#2463EB] rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Setujui Pemasok
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>