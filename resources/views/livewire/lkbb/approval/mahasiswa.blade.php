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

    // Properti Modal Approve (Baru)
    public $showApproveModal = false;

    // Properti Modal Profil
    public $showProfileModal = false;
    public $selectedProfile = null;

    // Default status
    public $statusFilter = 'diajukan'; 

    #[Computed]
    public function filteredRequests()
    {
        return PengajuanBantuan::with(['mahasiswaProfile.user']) 
            ->where('status', $this->statusFilter)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Fungsi Ganti Filter
    public function setFilter($status)
    {
        $this->statusFilter = $status;
    }

    // Fungsi Buka/Tutup Modal Detail
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

    // Fungsi Buka/Tutup Modal Profil
    public function lihatProfil($id)
    {
        $this->selectedProfile = MahasiswaProfile::with('user')->find($id);
        $this->showProfileModal = true;
    }

    public function tutupProfil()
    {
        $this->showProfileModal = false;
        $this->selectedProfile = null;
    }

    // Fungsi Buka Modal Approve (Baru)
    public function openApproveModal($id)
    {
        $this->selectedId = $id;
        $this->showApproveModal = true;
        $this->showDetailModal = false; // Tutup detail sementara
    }

    // Fungsi Konfirmasi Approve (Baru)
    public function confirmApprove()
    {
        try {
            DB::transaction(function () {
                $pengajuan = PengajuanBantuan::where('id', $this->selectedId)
                    ->where('status', 'diajukan')
                    ->lockForUpdate()
                    ->firstOrFail();

                // Ubah status menjadi 'disetujui'
                $pengajuan->update([
                    'status' => 'disetujui',
                    'updated_at' => now(),
                ]);
                
                MahasiswaProfile::where('id', $pengajuan->mahasiswa_profile_id)
                    ->update(['status_bantuan' => 'disetujui']);
            });

            session()->flash('message', "Pengajuan Bantuan berhasil disetujui!");
            $this->showApproveModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat memproses persetujuan.');
            $this->showApproveModal = false;
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

                $pengajuan->update([
                    'status' => 'ditolak',
                    'updated_at' => now(),
                ]);
                
                MahasiswaProfile::where('id', $pengajuan->mahasiswa_profile_id)
                    ->update(['status_bantuan' => 'ditolak']);
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

    <div class="flex gap-2 mb-6">
        <button wire:click="setFilter('diajukan')" class="px-4 py-2 rounded-lg text-sm font-bold {{ $statusFilter == 'diajukan' ? 'bg-yellow-100 text-yellow-700' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50' }}">⏳ Pending</button>
        <button wire:click="setFilter('disetujui')" class="px-4 py-2 rounded-lg text-sm font-bold {{ $statusFilter == 'disetujui' ? 'bg-green-100 text-green-700' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50' }}">✅ Diterima</button>
        <button wire:click="setFilter('ditolak')" class="px-4 py-2 rounded-lg text-sm font-bold {{ $statusFilter == 'ditolak' ? 'bg-red-100 text-red-700' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50' }}">❌ Ditolak</button>
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
                @forelse($this->filteredRequests as $req)
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
                        <td class="px-6 py-4 text-right space-x-2">
                            
                            <button wire:click="lihatProfil({{ $req->mahasiswaProfile->id }})" 
                               class="text-gray-700 bg-gray-50 hover:bg-gray-100 px-3 py-1.5 rounded-md text-xs font-bold border border-gray-200 transition-colors inline-block">
                                Lihat Riwayat
                            </button>

                            <button wire:click="lihatDetail({{ $req->id }})" 
                               class="text-blue-600 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-md text-xs font-bold border border-blue-200 transition-colors inline-block">
                                {{ $statusFilter == 'diajukan' ? 'Cek & Verifikasi' : 'Lihat Detail' }}
                            </button>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm">Tidak ada pengajuan bantuan mahasiswa di status ini.</td>
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
                    @if($selectedPengajuan->status == 'diajukan')
                        <button wire:click="openRejectModal({{ $selectedPengajuan->id }})" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 font-bold transition-colors">
                            Tolak
                        </button>
                        <button wire:click="openApproveModal({{ $selectedPengajuan->id }})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold transition-colors shadow-sm">
                            Setujui Bantuan
                        </button>
                    @else
                        <button wire:click="tutupDetail" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-bold transition-colors shadow-sm">
                            Tutup Detail
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($showApproveModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4 transition-opacity">
            <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl transform transition-all text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Setujui Pencairan Dana?</h3>
                <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menyetujui pencairan dana untuk mahasiswa ini? Proses ini tidak dapat dibatalkan.</p>
                
                <div class="flex justify-center space-x-3">
                    <button wire:click="$set('showApproveModal', false)" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-bold transition-colors">
                        Batal
                    </button>
                    <button wire:click="confirmApprove" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold transition-colors shadow-sm">
                        Ya, Setujui Bantuan
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showRejectModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4 transition-opacity">
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

    @if($showProfileModal && $selectedProfile)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4 overflow-y-auto">
            <div class="bg-gray-50 rounded-xl w-full max-w-5xl shadow-2xl relative my-8 max-h-[90vh] overflow-y-auto">
                <div class="p-6 space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex justify-between items-center sticky top-0 z-10">
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-bold">
                                {{ strtoupper(substr($selectedProfile->user->name ?? 'M', 0, 2)) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-bold text-gray-900">{{ $selectedProfile->user->name ?? '-' }}</h2>
                                    <span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold border border-green-200 uppercase">Terverifikasi</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">NIM: {{ $selectedProfile->nim ?? '-' }} • {{ $selectedProfile->jurusan ?? '-' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    Terdaftar: {{ $selectedProfile->created_at ? $selectedProfile->created_at->format('d M Y') : '-' }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <button wire:click="tutupProfil" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-50 shadow-sm transition-colors">
                                Tutup Profil
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h3 class="text-sm font-bold text-blue-600 flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                Informasi Pribadi
                            </h3>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <span class="block text-xs text-gray-500 font-bold mb-0.5">EMAIL</span>
                                    <span class="text-gray-900 font-medium">{{ $selectedProfile->user->email ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 font-bold mb-0.5">NO HP</span>
                                    <span class="text-gray-900 font-medium">{{ $selectedProfile->no_hp ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 font-bold mb-0.5">ALAMAT</span>
                                    <span class="text-gray-900 font-medium">{{ $selectedProfile->alamat ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h3 class="text-sm font-bold text-blue-600 flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>
                                Informasi Akademik
                            </h3>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <span class="block text-xs text-gray-500 font-bold mb-0.5">UNIVERSITAS</span>
                                    <span class="text-gray-900 font-medium">Institut Teknologi Bandung</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 font-bold mb-0.5">JURUSAN</span>
                                    <span class="text-gray-900 font-medium">{{ $selectedProfile->jurusan ?? '-' }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-xs text-gray-500 font-bold mb-0.5">SEMESTER</span>
                                        <span class="text-gray-900 font-medium">{{ $selectedProfile->semester ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs text-gray-500 font-bold mb-0.5">IPK</span>
                                        <span class="text-gray-900 font-medium">{{ $selectedProfile->ipk ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-6 rounded-xl shadow-md text-white flex flex-col justify-between relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-4">
                                <span class="bg-white/20 text-white text-[10px] px-3 py-1 rounded-full font-bold tracking-wider">PLATINUM</span>
                            </div>
                            <div>
                                <svg class="w-8 h-8 text-white/80 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                <span class="block text-xs text-blue-100 font-bold mb-1">SISA SALDO</span>
                                <span class="text-3xl font-bold">Rp {{ number_format($selectedProfile->saldo ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-end mt-6 pt-4 border-t border-blue-500/50">
                                <div>
                                    <span class="block text-[10px] text-blue-200 font-bold uppercase mb-0.5">Total Cair</span>
                                    <span class="font-bold text-sm">Rp 500.000</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] text-blue-200 font-bold uppercase mb-0.5">Status</span>
                                    <span class="font-bold text-sm text-green-300 flex items-center gap-1 justify-end">
                                        <span class="w-2 h-2 rounded-full bg-green-400"></span> ACTIVE
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>