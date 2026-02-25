<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\PengajuanBantuan;
use App\Models\MahasiswaProfile;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.lkbb')] class extends Component {
    
    // Properti Modal Detail
    public $showDetailModal = false;
    public $selectedPengajuan = null;

    // Properti Modal Reject
    public $showRejectModal = false;
    public $selectedId = null;

    // Mengambil data dengan status 'diajukan' sesuai Migration
    #[Computed]
    public function pendingRequests()
    {
        // Asumsi relasi MahasiswaProfile punya relasi 'user' untuk mengambil nama
        return PengajuanBantuan::with(['mahasiswaProfile.user']) 
            ->where('status', 'diajukan') 
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function lihatDetail($id)
    {
        $this->selectedPengajuan = PengajuanBantuan::with(['mahasiswaProfile.user'])->find($id);
        $this->showDetailModal = true;
    }

    public function tutupDetail()
    {
        $this->showDetailModal = false;
        $this->selectedPengajuan = null;
    }

    // Fungsi Approve
    public function approve($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $pengajuan = PengajuanBantuan::where('id', $id)
                    ->where('status', 'diajukan')
                    ->lockForUpdate()
                    ->firstOrFail();

                // Ubah status menjadi 'disetujui' sesuai ENUM di Migration
                $pengajuan->update([
                    'status' => 'disetujui',
                    'updated_at' => now(),
                ]);
            });

            session()->flash('message', "Pengajuan Bantuan {$id} berhasil disetujui!");
            $this->tutupDetail();
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat memproses persetujuan.');
        }
    }

    // Fungsi Buka Modal Reject
    public function openRejectModal($id)
    {
        $this->selectedId = $id;
        $this->showRejectModal = true;
        $this->showDetailModal = false; 
    }

    // Fungsi Konfirmasi Reject
    public function confirmReject()
    {
        try {
            DB::transaction(function () {
                $pengajuan = PengajuanBantuan::where('id', $this->selectedId)
                    ->where('status', 'diajukan')
                    ->lockForUpdate()
                    ->firstOrFail();

                // Ubah status menjadi 'ditolak' sesuai ENUM di Migration
                $pengajuan->update([
                    'status' => 'ditolak',
                    'updated_at' => now(),
                ]);
            });

            $this->showRejectModal = false;
            session()->flash('message', 'Pengajuan bantuan berhasil ditolak.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menolak pengajuan.');
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Verifikasi Bantuan Mahasiswa</h1>
        <p class="text-gray-500 text-sm mt-1">Daftar mahasiswa yang diajukan untuk menerima bantuan dana pendidikan.</p>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 shadow-sm">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Pengajuan</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mahasiswa</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($this->pendingRequests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $req->nomor_pengajuan }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $req->mahasiswaProfile->user->name ?? 'Nama Kosong' }}</div>
                            <div class="text-xs text-gray-500">NIM: {{ $req->mahasiswaProfile->nim ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-blue-600">
                            Rp {{ number_format($req->nominal ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="lihatDetail({{ $req->id }})" class="text-blue-600 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-md text-xs font-bold border border-blue-200 transition-colors">
                                Lihat & Verifikasi
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm">Tidak ada pengajuan bantuan mahasiswa yang berstatus 'diajukan'.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showDetailModal && $selectedPengajuan)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 transition-opacity">
            <div class="bg-white rounded-xl p-6 w-full max-w-2xl shadow-2xl transform transition-all">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Detail Bantuan Mahasiswa</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $selectedPengajuan->nomor_pengajuan }}</p>
                    </div>
                    <button wire:click="tutupDetail" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="block text-xs text-gray-500 font-semibold mb-1">Nama Lengkap</span>
                        <span class="font-bold text-gray-800">{{ $selectedPengajuan->mahasiswaProfile->user->name ?? '-' }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="block text-xs text-gray-500 font-semibold mb-1">NIM</span>
                        <span class="font-bold text-gray-800">{{ $selectedPengajuan->mahasiswaProfile->nim ?? '-' }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="block text-xs text-gray-500 font-semibold mb-1">Program Studi / Jurusan</span>
                        <span class="font-bold text-gray-800">{{ $selectedPengajuan->mahasiswaProfile->jurusan ?? '-' }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <span class="block text-xs text-gray-500 font-semibold mb-1">IPK Terakhir</span>
                        <span class="font-bold text-gray-800">{{ $selectedPengajuan->mahasiswaProfile->ipk ?? 'Belum diinput' }}</span>
                    </div>
                    <div class="col-span-2 bg-blue-50 p-4 rounded-lg border border-blue-100 flex justify-between items-center">
                        <span class="block text-sm text-blue-600 font-bold">Nominal yang Diajukan</span>
                        <span class="font-bold text-blue-700 text-xl">Rp {{ number_format($selectedPengajuan->nominal ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 border-t pt-4">
                    <button wire:click="openRejectModal({{ $selectedPengajuan->id }})" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 font-bold transition-colors">
                        Tolak
                    </button>
                    <button wire:click="approve({{ $selectedPengajuan->id }})" wire:confirm="Yakin ingin menyetujui pencairan dana sebesar Rp {{ number_format($selectedPengajuan->nominal ?? 0, 0, ',', '.') }} untuk mahasiswa ini?" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold transition-colors shadow-sm">
                        Setujui Bantuan
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 transition-opacity">
            <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl transform transition-all text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Tolak Pengajuan?</h3>
                <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menolak pengajuan bantuan ini? Status akan diubah menjadi 'ditolak' secara permanen.</p>
                
                <div class="flex justify-center space-x-3">
                    <button wire:click="$set('showRejectModal', false)" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-bold transition-colors">
                        Batal
                    </button>
                    <button wire:click="confirmReject" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold transition-colors shadow-sm">
                        Ya, Tolak Pengajuan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>