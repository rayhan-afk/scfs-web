<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\SupplierProfile;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    // Cukup simpan ID-nya saja agar aman dari bug fungsi re-render Livewire
    public $selectedSupplierId = null; 
    public bool $showModal = false;
    public string $catatan_penolakan = ''; 

    #[Computed]
    public function pendingSuppliers()
    {
        return SupplierProfile::with('user')
            ->where('status_verifikasi', 'menunggu_review')
            ->latest()
            ->paginate(10);
    }

    // Ambil data secara dinamis berdasarkan ID yang aktif
    #[Computed]
    public function selectedSupplier()
    {
        if (!$this->selectedSupplierId) return null;
        return SupplierProfile::with('user')->find($this->selectedSupplierId);
    }

    public function openModal($id)
    {
        $this->selectedSupplierId = $id;
        $this->catatan_penolakan = ''; 
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedSupplierId = null;
        $this->resetValidation();
    }

    public function approveSupplier()
    {
        $supplier = $this->selectedSupplier; // Panggil lewat computed
        if (!$supplier || $supplier->status_verifikasi !== 'menunggu_review') {
            $this->closeModal();
            $this->dispatch('show-toast', type: 'error', message: 'Aksi tidak valid atau status pemasok sudah berubah.');
            return;
        }

        $namaUsaha = $supplier->nama_usaha;
        $supplier->update(['status_verifikasi' => 'disetujui']);
        
        $this->closeModal();
        $this->dispatch('show-toast', type: 'success', message: "Pemasok {$namaUsaha} berhasil disetujui dan diaktifkan!");
    }

    public function rejectSupplier()
    {
        $this->validate(['catatan_penolakan' => 'required|string|min:5']);

        $supplier = $this->selectedSupplier; // Panggil lewat computed
        if (!$supplier || $supplier->status_verifikasi !== 'menunggu_review') {
            $this->closeModal();
            $this->dispatch('show-toast', type: 'error', message: 'Aksi tidak valid.');
            return;
        }

        $namaUsaha = $supplier->nama_usaha;
        $supplier->update([
            'status_verifikasi' => 'ditolak',
            'catatan_penolakan' => $this->catatan_penolakan
        ]);
        
        $this->closeModal();
        $this->dispatch('show-toast', type: 'error', message: "Pendaftaran {$namaUsaha} berhasil ditolak.");
    }
}; ?>

<div
    x-data="{
        showToast: false,
        toastType: '',
        toastMessage: '',
        toastTimer: null,

        showConfirm: false,
        confirmType: '',
        confirmTitle: '',
        confirmMessage: '',
        confirmAction: null,

        openConfirm(type, title, message, action) {
            this.confirmType = type;
            this.confirmTitle = title;
            this.confirmMessage = message;
            this.confirmAction = action;
            this.showConfirm = true;
        },

        doConfirm() {
            if (this.confirmAction) this.confirmAction();
            this.showConfirm = false;
        },

        triggerToast(type, message) {
            clearTimeout(this.toastTimer);
            this.toastType = type;
            this.toastMessage = message;
            this.showToast = true;
            this.toastTimer = setTimeout(() => { this.showToast = false; }, 4500);
        }
    }"
    x-on:show-toast.window="triggerToast($event.detail.type, $event.detail.message)"
    class="p-6 max-w-7xl mx-auto"
>

    {{-- ===== TOAST NOTIFICATION ===== --}}
    <div
        x-show="showToast"
        x-cloak
        x-transition:enter="transition ease-out duration-400"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="fixed bottom-6 right-6 z-[9999] w-full max-w-sm"
    >
        {{-- SUCCESS --}}
        <div x-show="toastType === 'success'" class="flex items-start gap-4 bg-white border border-green-100 rounded-2xl shadow-2xl shadow-green-100/60 p-5 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-green-400 to-emerald-500 rounded-l-2xl"></div>
            <div class="flex-shrink-0 w-11 h-11 bg-green-100 rounded-xl flex items-center justify-center ml-2">
                <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 pt-0.5 min-w-0">
                <p class="text-sm font-black text-gray-900">Berhasil!</p>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed" x-text="toastMessage"></p>
                <div class="mt-3 h-1 bg-green-100 rounded-full overflow-hidden">
                    <div x-show="showToast && toastType === 'success'"
                         x-transition:enter="transition-none"
                         x-transition:enter-start="width: 100%"
                         x-init="$watch('showToast', v => { if(v && toastType==='success') { $el.style.width='100%'; setTimeout(()=>{ $el.style.transition='width 4.2s linear'; $el.style.width='0%'; },50); } })"
                         style="height:100%; background: linear-gradient(to right, #4ade80, #10b981); border-radius:9999px; width:100%;"></div>
                </div>
            </div>
            <button @click="showToast = false" class="flex-shrink-0 text-gray-300 hover:text-gray-500 transition mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- ERROR / TOLAK --}}
        <div x-show="toastType === 'error'" class="flex items-start gap-4 bg-white border border-red-100 rounded-2xl shadow-2xl shadow-red-100/60 p-5 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-red-400 to-rose-500 rounded-l-2xl"></div>
            <div class="flex-shrink-0 w-11 h-11 bg-red-100 rounded-xl flex items-center justify-center ml-2">
                <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 pt-0.5 min-w-0">
                <p class="text-sm font-black text-gray-900">Pemberitahuan</p>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed" x-text="toastMessage"></p>
                <div class="mt-3 h-1 bg-red-100 rounded-full overflow-hidden">
                    <div x-show="showToast && toastType === 'error'"
                         x-init="$watch('showToast', v => { if(v && toastType==='error') { $el.style.width='100%'; setTimeout(()=>{ $el.style.transition='width 4.2s linear'; $el.style.width='0%'; },50); } })"
                         style="height:100%; background: linear-gradient(to right, #f87171, #f43f5e); border-radius:9999px; width:100%;"></div>
                </div>
            </div>
            <button @click="showToast = false" class="flex-shrink-0 text-gray-300 hover:text-gray-500 transition mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
    {{-- ===== END TOAST ===== --}}

    {{-- ===== CUSTOM CONFIRM DIALOG ===== --}}
    <div
        x-show="showConfirm"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    >
        <div
            x-show="showConfirm"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 scale-90 -translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 relative"
            @click.outside="showConfirm = false"
        >
            {{-- Icon --}}
            <div class="flex justify-center mb-5">
                <div x-show="confirmType === 'approve'" class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center shadow-sm shadow-blue-100">
                    <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div x-show="confirmType === 'reject'" class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center shadow-sm shadow-red-100">
                    <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
            </div>

            {{-- Text --}}
            <div class="text-center mb-8">
                <h3 class="text-lg font-black text-gray-900 mb-2" x-text="confirmTitle"></h3>
                <p class="text-sm text-gray-500 leading-relaxed" x-text="confirmMessage"></p>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3">
                <button
                    @click="showConfirm = false"
                    class="flex-1 px-6 py-3 text-sm font-bold text-gray-600 bg-gray-100 rounded-2xl hover:bg-gray-200 transition-all duration-200"
                >
                    Batal
                </button>
                <button
                    @click="doConfirm()"
                    :class="confirmType === 'approve'
                        ? 'flex-1 px-6 py-3 text-sm font-bold text-white bg-[#2463EB] rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all duration-200'
                        : 'flex-1 px-6 py-3 text-sm font-bold text-white bg-red-500 rounded-2xl shadow-lg shadow-red-200 hover:bg-red-600 transition-all duration-200'"
                    x-text="confirmType === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"
                >
                </button>
            </div>
        </div>
    </div>
    {{-- ===== END CONFIRM DIALOG ===== --}}

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Pemasok / Grosir</h1>
            <p class="text-gray-500 text-sm mt-1">Verifikasi legalitas gudang dan data pemilik pemasok rantai pasok.</p>
        </div>
    </div>

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
@if($showModal && $this->selectedSupplier)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-auto flex flex-col max-h-[90vh] overflow-hidden">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Verifikasi Dokumen Pemasok / Grosir</h3>
                    <p class="text-[11px] text-gray-500 uppercase tracking-wider font-bold mt-1">ID PENGAJUAN: SC-SUPP-{{ str_pad($this->selectedSupplier->id, 4, '0', STR_PAD_LEFT) }}</p>
                </div>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

           {{-- Modal Body --}}
<div class="px-6 py-4 overflow-y-auto flex-1 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- Kolom Kiri: Data Teks --}}
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Usaha / Grosir</span>
                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedSupplier->nama_usaha ?? '-' }}</span>
                </div>
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Pemilik</span>
                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedSupplier->nama_pemilik ?? '-' }}</span>
                </div>
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">NIK</span>
                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedSupplier->nik ?? '-' }}</span>
                </div>
                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">No WhatsApp</span>
                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedSupplier->no_hp ?? '-' }}</span>
                </div>
                
                {{-- Alamat --}}
                <div class="col-span-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Alamat Gudang</span>
                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedSupplier->alamat_gudang ?? '-' }}</span>
                </div>
                
                {{-- Info Rekening --}}
                <div class="col-span-2 bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                    <span class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Info Rekening</span>
                    <div class="flex flex-col">
                        @if($this->selectedSupplier->info_rekening)
                            @php
                                // Bongkar string JSON dari database menjadi array PHP
                                  $rekening = is_array($this->selectedSupplier->info_rekening)
                                    ? $this->selectedSupplier->info_rekening
                                    : json_decode($this->selectedSupplier->info_rekening, true);
                            @endphp
                            
                            <span class="text-sm font-bold text-blue-900">
                                {{ $rekening['nama_bank'] ?? 'Bank Tidak Diisi' }}
                            </span>
                            <span class="text-[11px] text-blue-600 font-mono mt-0.5">
                                {{ $rekening['nomor_rekening'] ?? '-' }} 
                                @if(isset($rekening['nama_rekening'])) (a.n. {{ $rekening['nama_rekening'] }}) @endif
                            </span>
                        @else
                            <span class="text-sm font-medium text-gray-400">Belum mengisi info rekening</span>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            {{-- Form Tolak --}}
            <div class="bg-red-50 p-4 rounded-xl border border-red-100">
                <label class="block text-[10px] font-bold text-red-700 uppercase tracking-wider mb-2">Alasan Penolakan (Jika Ingin Ditolak)</label>
                <textarea wire:model="catatan_penolakan" class="w-full text-sm rounded-lg border-red-200 focus:border-red-500 focus:ring-red-500 bg-white" rows="3" placeholder="Cth: Foto KTP terpotong/buram..."></textarea>
                @error('catatan_penolakan') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Kolom Kanan: Foto Viewer --}}
        <div class="space-y-4">
            <div class="border border-gray-100 rounded-xl p-3 bg-white shadow-sm">
                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 text-center">Dokumen KTP</span>
                @if($this->selectedSupplier->foto_ktp)
                    <a href="{{ asset('storage/' . $this->selectedSupplier->foto_ktp) }}" target="_blank" class="block bg-gray-50 border border-gray-200 rounded-lg overflow-hidden hover:opacity-90 transition">
                        <img src="{{ asset('storage/' . $this->selectedSupplier->foto_ktp) }}" class="w-full h-40 object-cover object-center">
                    </a>
                    <p class="text-[10px] text-center text-gray-400 mt-2">Klik gambar untuk memperbesar</p>
                @else
                    <div class="w-full h-40 bg-gray-50 border border-gray-200 border-dashed rounded-lg flex flex-col items-center justify-center text-gray-400">
                        <svg class="w-8 h-8 mb-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span class="text-xs">File KTP tidak ditemukan</span>
                    </div>
                @endif
            </div>

            <div class="border border-gray-100 rounded-xl p-3 bg-white shadow-sm">
                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 text-center">Foto Depan Gudang/Usaha</span>
                @if($this->selectedSupplier->foto_usaha)
                    <a href="{{ asset('storage/' . $this->selectedSupplier->foto_usaha) }}" target="_blank" class="block bg-gray-50 border border-gray-200 rounded-lg overflow-hidden hover:opacity-90 transition">
                        <img src="{{ asset('storage/' . $this->selectedSupplier->foto_usaha) }}" class="w-full h-40 object-cover object-center">
                    </a>
                    <p class="text-[10px] text-center text-gray-400 mt-2">Klik gambar untuk memperbesar</p>
                @else
                    <div class="w-full h-40 bg-gray-50 border border-gray-200 border-dashed rounded-lg flex flex-col items-center justify-center text-gray-400">
                        <svg class="w-8 h-8 mb-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span class="text-xs">File Foto tidak ditemukan</span>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center bg-gray-50">
                <button wire:click="closeModal" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition focus:ring-4 focus:ring-gray-100">
                    Kembali
                </button>
                <div class="flex gap-3">
                    <button 
                        @click="openConfirm(
                            'reject',
                            'Tolak Pendaftaran Pemasok?',
                            'Tindakan ini akan menolak pendaftaran pemasok. Pastikan catatan penolakan sudah diisi.',
                            () => $wire.rejectSupplier()
                        )"
                        class="px-5 py-2.5 text-sm font-bold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition focus:ring-4 focus:ring-red-100">
                        Tolak Pengajuan
                    </button>
                    <button 
                        @click="openConfirm(
                            'approve',
                            'Setujui Pendaftaran Pemasok?',
                            'Pemasok akan langsung aktif dan dapat menggunakan sistem setelah disetujui.',
                            () => $wire.approveSupplier()
                        )"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-sm transition focus:ring-4 focus:ring-green-100 flex items-center gap-2">
                        
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>

                        Setujui & Aktifkan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>

