<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\PemasokProfile;
use App\Notifications\PemasokApproved;
use App\Notifications\PemasokRejected;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $selectedPemasokId = null;
    public bool $showModal = false;
    public string $catatan_penolakan = '';

    #[Computed]
    public function pendingPemasoks()
    {
        return PemasokProfile::with('user')
            ->where('status_verifikasi', 'menunggu_review')
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function selectedPemasok()
    {
        if (!$this->selectedPemasokId) return null;
        return PemasokProfile::with('user')->find($this->selectedPemasokId);
    }

    public function openModal($id)
    {
        $this->selectedPemasokId = $id;
        $this->catatan_penolakan = '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedPemasokId = null;
        $this->resetValidation();
    }

    public function approvePemasok()
    {
        $pemasok = $this->selectedPemasok;
        if (!$pemasok || $pemasok->status_verifikasi !== 'menunggu_review') {
            $this->closeModal();
            session()->flash('error', 'Aksi tidak valid atau status pemasok sudah berubah.');
            return;
        }

        $namaPerusahaan = $pemasok->nama_perusahaan;
        $pemasok->update([
            'status_verifikasi'  => 'disetujui',
            'status_kemitraan'   => 'aktif',
            'status_operasional' => 'tutup', // pemasok activate manual nanti
        ]);

        $pemasok->user->notify(new PemasokApproved());

        $this->closeModal();
        session()->flash('message', "Pemasok {$namaPerusahaan} berhasil disetujui dan diaktifkan!");
    }

    public function rejectPemasok()
    {
        $this->validate(['catatan_penolakan' => 'required|string|min:5']);

        $pemasok = $this->selectedPemasok;
        if (!$pemasok || $pemasok->status_verifikasi !== 'menunggu_review') {
            $this->closeModal();
            session()->flash('error', 'Aksi tidak valid.');
            return;
        }

        $namaPerusahaan = $pemasok->nama_perusahaan;
        $pemasok->update([
            'status_verifikasi' => 'ditolak',
            'catatan_penolakan' => $this->catatan_penolakan,
        ]);

        $pemasok->user->notify(new PemasokRejected($this->catatan_penolakan));

        $this->closeModal();
        session()->flash('error', "Pendaftaran {$namaPerusahaan} berhasil ditolak.");
    }
}; ?>

<div
    x-data="{
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
        }
    }"
    class="p-6 max-w-7xl mx-auto"
>

    {{-- ===== CONFIRM DIALOG ===== --}}
    <div
        x-show="showConfirm"
        x-cloak
        class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    >
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 relative" @click.outside="showConfirm = false">
            <div class="flex justify-center mb-5">
                <div x-show="confirmType === 'approve'" class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center shadow-sm shadow-blue-100">
                    <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div x-show="confirmType === 'reject'" class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center shadow-sm shadow-red-100">
                    <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                </div>
            </div>
            <div class="text-center mb-8">
                <h3 class="text-lg font-black text-gray-900 mb-2" x-text="confirmTitle"></h3>
                <p class="text-sm text-gray-500 leading-relaxed" x-text="confirmMessage"></p>
            </div>
            <div class="flex gap-3">
                <button @click="showConfirm = false" class="flex-1 px-6 py-3 text-sm font-bold text-gray-600 bg-gray-100 rounded-2xl hover:bg-gray-200 transition-all duration-200">Batal</button>
                <button @click="doConfirm()" :class="confirmType === 'approve' ? 'flex-1 px-6 py-3 text-sm font-bold text-white bg-[#28a745] rounded-2xl shadow-lg shadow-green-200 hover:bg-green-700 transition-all duration-200' : 'flex-1 px-6 py-3 text-sm font-bold text-white bg-red-500 rounded-2xl shadow-lg shadow-red-200 hover:bg-red-600 transition-all duration-200'" x-text="confirmType === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"></button>
            </div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Pemasok / Grosir</h1>
            <p class="text-gray-500 text-sm mt-1">Verifikasi legalitas gudang dan data pemilik pemasok rantai pasok.</p>
        </div>
    </div>

    {{-- ===== FLASH NOTIFICATION ===== --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl relative mb-4 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative mb-4 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] text-gray-400 border-b border-gray-100 uppercase tracking-wider bg-gray-50/50">
                        <th class="py-4 px-6 font-bold">Data Usaha / Grosir</th>
                        <th class="py-4 px-6 font-bold">Data PIC</th>
                        <th class="py-4 px-6 font-bold">Alamat Gudang</th>
                        <th class="py-4 px-6 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                    @forelse($this->pendingPemasoks as $profile)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-900 text-sm">{{ $profile->nama_perusahaan ?: '-' }}</div>
                            <div class="text-[11px] text-gray-500 mt-1">WA: <span class="font-medium text-gray-700">{{ $profile->no_hp ?? '-' }}</span></div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-gray-900 font-medium text-sm">{{ $profile->nama_pic ?: '-' }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5 font-mono">{{ $profile->nik ?? '-' }}</div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-gray-800 text-xs line-clamp-1">{{ $profile->alamat ?? '-' }}</div>
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
        <div class="mt-4">{{ $this->pendingPemasoks->links() }}</div>
    </div>

    {{-- Modal Review --}}
    @if($showModal && $this->selectedPemasok)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-auto flex flex-col max-h-[90vh] overflow-hidden">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Verifikasi Dokumen Pemasok / Grosir</h3>
                        <p class="text-[11px] text-gray-500 uppercase tracking-wider font-bold mt-1">ID PENGAJUAN: SC-SUPP-{{ str_pad($this->selectedPemasok->id, 4, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition p-1.5 rounded-lg hover:bg-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 py-4 overflow-y-auto flex-1 bg-white">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Kolom Kiri: Data Teks --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Perusahaan</span>
                                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedPemasok->nama_perusahaan ?? '-' }}</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama PIC</span>
                                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedPemasok->nama_pic ?? '-' }}</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">NIK</span>
                                    <span class="block text-sm font-bold text-gray-900 font-mono">{{ $this->selectedPemasok->nik ?? '-' }}</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">No WhatsApp</span>
                                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedPemasok->no_hp ?? '-' }}</span>
                                </div>

                                <div class="col-span-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Alamat Gudang</span>
                                    <span class="block text-sm font-bold text-gray-900">{{ $this->selectedPemasok->alamat ?? '-' }}</span>
                                </div>

                                <div class="col-span-2 bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                                    <span class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1">Info Rekening</span>
                                    @if($this->selectedPemasok->nama_bank && $this->selectedPemasok->no_rekening)
                                        <span class="block text-sm font-bold text-blue-900">{{ $this->selectedPemasok->nama_bank }}</span>
                                        <span class="block text-[11px] text-blue-600 font-mono mt-0.5">
                                            {{ $this->selectedPemasok->no_rekening }}
                                            @if($this->selectedPemasok->atas_nama_rekening)
                                                <span class="text-blue-500">— a.n. {{ $this->selectedPemasok->atas_nama_rekening }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-sm font-medium text-gray-400">Belum mengisi info rekening</span>
                                    @endif
                                </div>
                            </div>

                            <hr class="border-gray-100">

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
                                @if($this->selectedPemasok->foto_ktp)
                                    <a href="{{ asset('storage/' . $this->selectedPemasok->foto_ktp) }}" target="_blank" class="block bg-gray-50 border border-gray-200 rounded-lg overflow-hidden hover:opacity-90 transition">
                                        <img src="{{ asset('storage/' . $this->selectedPemasok->foto_ktp) }}" class="w-full h-40 object-cover object-center">
                                    </a>
                                    <p class="text-[10px] text-center text-gray-400 mt-2">Klik gambar untuk memperbesar</p>
                                @else
                                    <div class="w-full h-40 bg-gray-50 border border-gray-200 border-dashed rounded-lg flex flex-col items-center justify-center text-gray-400">
                                        <span class="text-xs">File KTP tidak ditemukan</span>
                                    </div>
                                @endif
                            </div>

                            <div class="border border-gray-100 rounded-xl p-3 bg-white shadow-sm">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 text-center">Foto Depan Gudang/Usaha</span>
                                @if($this->selectedPemasok->foto_gudang)
                                    <a href="{{ asset('storage/' . $this->selectedPemasok->foto_gudang) }}" target="_blank" class="block bg-gray-50 border border-gray-200 rounded-lg overflow-hidden hover:opacity-90 transition">
                                        <img src="{{ asset('storage/' . $this->selectedPemasok->foto_gudang) }}" class="w-full h-40 object-cover object-center">
                                    </a>
                                    <p class="text-[10px] text-center text-gray-400 mt-2">Klik gambar untuk memperbesar</p>
                                @else
                                    <div class="w-full h-40 bg-gray-50 border border-gray-200 border-dashed rounded-lg flex flex-col items-center justify-center text-gray-400">
                                        <span class="text-xs">File Foto tidak ditemukan</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

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
                                () => $wire.rejectPemasok()
                            )"
                            class="px-5 py-2.5 text-sm font-bold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition focus:ring-4 focus:ring-red-100">
                            Tolak Pengajuan
                        </button>
                        <button
                            @click="openConfirm(
                                'approve',
                                'Setujui Pendaftaran Pemasok?',
                                'Pemasok akan langsung aktif dan dapat menggunakan sistem setelah disetujui.',
                                () => $wire.approvePemasok()
                            )"
                            class="px-6 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-sm transition focus:ring-4 focus:ring-green-100 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Setujui & Aktifkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>