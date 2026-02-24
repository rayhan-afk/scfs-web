<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\PengajuanBantuan;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public User $user;
    
    // Tab Aktif (Default: Transaksi)
    public $activeTab = 'transaksi'; 

    public $isEditModalOpen = false;
    public $edit_name, $edit_email, $edit_nim, $edit_jurusan, $edit_semester, $edit_ipk, $edit_no_hp, $edit_alamat;

    public function mount($id)
    {
        $this->user = User::with(['mahasiswaProfile.pengajuans'])->findOrFail($id);
    }

    // Dummy Data Transaksi (Hanya muncul kalau sudah diverifikasi/disetujui)
    public function getDummyTransactionsProperty()
    {
        if ($this->user->mahasiswaProfile->status_bantuan !== 'disetujui') {
            return [];
        }

        return [
            ['id' => 'TRX-99821', 'tanggal' => '23 Feb 2026, 12:30', 'merchant' => 'Kantin Bu Ani (Blok A)', 'jenis' => 'Pengeluaran', 'nominal' => 15000, 'status' => 'Berhasil'],
            ['id' => 'TRX-99750', 'tanggal' => '22 Feb 2026, 09:15', 'merchant' => 'Kopi Pojok Teknik', 'jenis' => 'Pengeluaran', 'nominal' => 12000, 'status' => 'Berhasil'],
            ['id' => 'TRX-99102', 'tanggal' => '15 Feb 2026, 10:00', 'merchant' => 'Pencairan LKBB', 'jenis' => 'Pemasukan', 'nominal' => 500000, 'status' => 'Berhasil'],
            ['id' => 'TRX-98001', 'tanggal' => '14 Feb 2026, 13:20', 'merchant' => 'Ayam Geprek Ganesha', 'jenis' => 'Pengeluaran', 'nominal' => 20000, 'status' => 'Berhasil'],
        ];
    }

    public function openEditModal()
    {
        $this->edit_name = $this->user->name;
        $this->edit_email = $this->user->email;
        
        $profil = $this->user->mahasiswaProfile;
        $this->edit_nim = $profil->nim ?? '';
        $this->edit_jurusan = $profil->jurusan ?? '';
        $this->edit_semester = $profil->semester ?? '';
        $this->edit_ipk = $profil->ipk ?? '';
        $this->edit_no_hp = $profil->no_hp ?? '';
        $this->edit_alamat = $profil->alamat ?? '';
        
        $this->isEditModalOpen = true;
    }

    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    public function updateData()
    {
        $this->user->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
        ]);

        if ($this->user->mahasiswaProfile) {
            $this->user->mahasiswaProfile->update([
                'nim' => $this->edit_nim,
                'jurusan' => $this->edit_jurusan,
                'semester' => $this->edit_semester,
                'ipk' => $this->edit_ipk,
                'no_hp' => $this->edit_no_hp,
                'alamat' => $this->edit_alamat,
            ]);
        }

        $this->user->refresh();
        $this->closeEditModal();
    }

    public function accPengajuan($pengajuanId)
    {
        $pengajuan = PengajuanBantuan::find($pengajuanId);
        if($pengajuan && $pengajuan->status == 'diajukan') {
            $pengajuan->update(['status' => 'disetujui']);
            
            $profil = $this->user->mahasiswaProfile;
            $profil->update(['saldo' => $profil->saldo + $pengajuan->nominal]);
            
            $this->user->refresh();
        }
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.mahasiswa.index') }}" class="hover:text-blue-600 transition">Data Mahasiswa</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Detail Mahasiswa</span>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-5">
            <div class="h-20 w-20 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold shadow-inner">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                    
                    @if($user->mahasiswaProfile->status_bantuan == 'disetujui')
                        <span class="bg-green-100 text-green-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-green-200 uppercase tracking-wide">Terverifikasi</span>
                    @elseif($user->mahasiswaProfile->status_bantuan == 'diajukan')
                        <span class="bg-blue-100 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-blue-200 uppercase tracking-wide flex items-center gap-1">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Menunggu Verifikasi
                        </span>
                    @elseif($user->mahasiswaProfile->status_bantuan == 'ditolak')
                        <span class="bg-red-100 text-red-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-red-200 uppercase tracking-wide">Ditolak</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">Belum Terverifikasi</span>
                    @endif
                    
                </div>
                <p class="text-gray-500 font-medium text-sm">NIM: {{ $user->mahasiswaProfile->nim ?? '-' }} • {{ $user->mahasiswaProfile->jurusan ?? '-' }}</p>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> Terdaftar: {{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('admin.mahasiswa.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium text-sm transition text-center w-full md:w-auto">
                Kembali
            </a>
            <button wire:click="openEditModal" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium text-sm shadow-sm transition flex items-center justify-center w-full md:w-auto gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Edit Data
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-blue-700 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                Informasi Pribadi
            </div>
            
            <div class="space-y-5 flex-1">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Email</p>
                        <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">No HP</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->mahasiswaProfile->no_hp ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Alamat</p>
                        <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $user->mahasiswaProfile->alamat ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-blue-700 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6" /></svg>
                Informasi Akademik
            </div>
            
            <div class="space-y-5 flex-1">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Universitas</p>
                        <p class="text-gray-900 font-medium text-sm">Institut Teknologi Bandung</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Jurusan</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->mahasiswaProfile->jurusan ?: '-' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Semester</p>
                            <p class="text-gray-900 font-medium text-sm">{{ $user->mahasiswaProfile->semester ?: '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">IPK</p>
                            <p class="text-blue-700 font-bold text-sm">{{ $user->mahasiswaProfile->ipk ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-blue-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full">
            
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black opacity-10 rounded-full -ml-8 -mb-8 pointer-events-none"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-6">
                    <svg class="w-7 h-7 opacity-90 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <span class="bg-blue-800/60 px-3 py-1 rounded-md text-[10px] font-bold tracking-wider border border-blue-500/50 whitespace-nowrap">PLATINUM</span>
                </div>
                
                <div>
                    <p class="text-blue-200 text-xs font-bold tracking-wider mb-1">SISA SALDO</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->mahasiswaProfile->saldo, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            
            <div class="flex justify-between items-end pt-5 border-t border-blue-500/50 relative z-10 mt-auto">
                <div class="min-w-0 pr-2">
                    <p class="text-[10px] text-blue-200 mb-0.5 font-bold tracking-wider truncate">TOTAL CAIR</p>
                    <p class="font-bold text-sm truncate">Rp {{ number_format($user->mahasiswaProfile->pengajuans->where('status', 'disetujui')->sum('nominal'), 0, ',', '.') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[10px] text-blue-200 mb-0.5 font-bold tracking-wider">STATUS</p>
                    <p class="font-bold text-sm text-green-300 tracking-wide flex items-center gap-1 justify-end">
                        <span class="w-2 h-2 rounded-full bg-green-400 {{ $user->mahasiswaProfile->status_bantuan == 'disetujui' ? 'animate-pulse' : 'bg-gray-400' }}"></span> 
                        {{ $user->mahasiswaProfile->status_bantuan == 'disetujui' ? 'ACTIVE' : 'INACTIVE' }}
                    </p>
                </div>
            </div>
        </div>
        
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4">
        
        <div class="flex border-b border-gray-100 px-6 gap-6 overflow-x-auto">
            <button wire:click="$set('activeTab', 'transaksi')" 
                class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'transaksi' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                Riwayat Transaksi Kantin
            </button>
            <button wire:click="$set('activeTab', 'pengajuan')" 
                class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'pengajuan' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                Pengajuan Saldo LKBB
            </button>
            <button class="py-4 font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">
                Dokumen
            </button>
            <button class="py-4 font-medium text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">
                Timeline
            </button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            
            @if($activeTab == 'transaksi')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">ID Transaksi</th>
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Merchant Kantin</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->dummyTransactions as $trx)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-500">{{ $trx['id'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $trx['tanggal'] }}</td>
                        <td class="px-6 py-4 font-bold text-sm text-gray-900">{{ $trx['merchant'] }}</td>
                        <td class="px-6 py-4 font-bold text-sm text-right {{ $trx['jenis'] == 'Pemasukan' ? 'text-green-600' : 'text-red-500' }}">
                            {{ $trx['jenis'] == 'Pemasukan' ? '+' : '-' }} Rp {{ number_format($trx['nominal'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-green-100 text-green-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Sukses</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <p class="text-gray-500 text-sm font-medium">Belum ada riwayat transaksi kantin.</p>
                            <p class="text-gray-400 text-xs mt-1">Data akan muncul setelah mahasiswa mendapat saldo dan mulai bertransaksi.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            @if($activeTab == 'pengajuan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">ID Pengajuan</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($user->mahasiswaProfile->pengajuans->sortByDesc('created_at') as $pengajuan)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-sm text-gray-900">{{ $pengajuan->nomor_pengajuan }}</td>
                        <td class="px-6 py-4 font-bold text-sm text-gray-900">Rp {{ number_format($pengajuan->nominal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $pengajuan->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            @if($pengajuan->status == 'disetujui')
                                <span class="bg-green-100 text-green-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Disetujui</span>
                            @elseif($pengajuan->status == 'diajukan')
                                <span class="bg-blue-100 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Proses</span>
                            @else
                                <span class="bg-red-100 text-red-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Ditolak</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($pengajuan->status == 'diajukan')
                                <button wire:click="accPengajuan({{ $pengajuan->id }})" class="text-xs text-blue-600 font-bold hover:text-blue-800 transition bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100">
                                    Simulasi ACC
                                </button>
                            @else
                                <span class="text-xs text-blue-600 font-bold cursor-pointer hover:text-blue-800">Detail</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-gray-500 text-sm font-medium">Belum ada riwayat pengajuan saldo.</p>
                            <p class="text-gray-400 text-xs mt-1">Gunakan tombol "Ajukan Saldo" (atau Edit Data) untuk memproses bantuan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
            
        </div>
    </div>

    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Data Mahasiswa
                </h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <input wire:model="edit_name" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Aktif</label>
                        <input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nomor HP</label>
                        <input wire:model="edit_no_hp" type="text" placeholder="Contoh: 0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">NIM</label>
                        <input wire:model="edit_nim" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Universitas</label>
                        <input type="text" value="ITB" disabled class="w-full text-sm rounded-xl border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed font-medium py-2">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Jurusan</label>
                        <input wire:model="edit_jurusan" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                    </div>
                    <div class="flex gap-2">
                        <div class="w-1/2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Smt</label>
                            <input wire:model="edit_semester" type="text" placeholder="Ke-" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                        </div>
                        <div class="w-1/2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">IPK</label>
                            <input wire:model="edit_ipk" type="number" step="0.01" max="4.00" placeholder="4.00" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Lengkap</label>
                    <textarea wire:model="edit_alamat" rows="2" placeholder="Masukkan alamat lengkap..." class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"></textarea>
                </div>
            </div>
            
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">
                    Batal
                </button>
                <button wire:click="updateData" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-sm focus:ring-4 focus:ring-blue-100">
                    Simpan Data
                </button>
            </div>

        </div>
    </div>
    @endif

</div>