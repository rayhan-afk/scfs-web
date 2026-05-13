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

    // Toast Notification States
    public bool $showToast = false;
    public string $toastType = '';
    public string $toastMessage = '';

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

    public function dismissToast()
    {
        $this->showToast = false;
        $this->toastType = '';
        $this->toastMessage = '';
    }

    public function approveSupplier()
    {
        if (!$this->selectedSupplier || $this->selectedSupplier->status_verifikasi !== 'menunggu_review') {
            $this->toastType = 'error';
            $this->toastMessage = 'Aksi tidak valid atau status pemasok sudah berubah.';
            $this->showToast = true;
            return $this->closeModal();
        }

        $namaUsaha = $this->selectedSupplier->nama_usaha;

        $this->selectedSupplier->update([
            'status_verifikasi' => 'disetujui',
        ]);
        
        $this->closeModal();

        $this->toastType = 'success';
        $this->toastMessage = "Pemasok {$namaUsaha} berhasil disetujui dan diaktifkan!";
        $this->showToast = true;
    }

    public function rejectSupplier()
    {
        $this->validate([
            'catatan_penolakan' => 'required|string|min:5'
        ]);

        if (!$this->selectedSupplier || $this->selectedSupplier->status_verifikasi !== 'menunggu_review') {
            $this->toastType = 'error';
            $this->toastMessage = 'Aksi tidak valid.';
            $this->showToast = true;
            return $this->closeModal();
        }

        $namaUsaha = $this->selectedSupplier->nama_usaha;

        $this->selectedSupplier->update([
            'status_verifikasi' => 'ditolak',
            'catatan_penolakan' => $this->catatan_penolakan
        ]);
        
        $this->closeModal();

        $this->toastType = 'error';
        $this->toastMessage = "Pendaftaran {$namaUsaha} berhasil ditolak.";
        $this->showToast = true;
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
        x-transition:enter="transition ease-out duration-400"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="fixed bottom-6 right-6 z-[9999] w-full max-w-sm"
        style="display: none;"
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
                <p class="text-sm font-black text-gray-900">Pemasok Disetujui!</p>
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
                <p class="text-sm font-black text-gray-900">Pendaftaran Ditolak</p>
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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
        style="display: none;"
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

                {{-- Footer dengan tombol yang memicu custom confirm --}}
                <div class="px-8 py-6 border-t border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <button wire:click="closeModal" class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition">Tutup</button>
                    <div class="flex gap-4">
                        {{-- Tombol Tolak → custom confirm merah --}}
                        <button
                            @click="openConfirm(
                                'reject',
                                'Tolak Pendaftaran Pemasok?',
                                'Tindakan ini akan menolak pendaftaran pemasok. Pastikan catatan penolakan sudah diisi.',
                                () => $wire.rejectSupplier()
                            )"
                            class="px-6 py-3 text-sm font-bold text-red-600 bg-white border border-red-200 rounded-2xl hover:bg-red-50 transition"
                        >
                            Tolak Pendaftaran
                        </button>

                        {{-- Tombol Setujui → custom confirm biru --}}
                        <button
                            @click="openConfirm(
                                'approve',
                                'Setujui Pendaftaran Pemasok?',
                                'Pemasok akan langsung aktif dan dapat menggunakan sistem setelah disetujui.',
                                () => $wire.approveSupplier()
                            )"
                            class="px-8 py-3 text-sm font-bold text-white bg-[#2463EB] rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Setujui Pemasok
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Script untuk trigger toast setelah Livewire selesai --}}
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('show-toast', (event) => {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: event }));
                });
            });
        </script>
    @endif

    {{-- Livewire hook untuk toast setelah approveSupplier/rejectSupplier --}}
    @if($showToast)
        <script>
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: {
                    type: @js($toastType),
                    message: @js($toastMessage)
                }
            }));
        </script>
        {{ $this->dismissToast() }}
    @endif

</div>