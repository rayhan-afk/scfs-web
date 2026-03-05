<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Transaction;

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
        // Eager Load relasi profil dan pengajuan agar query lebih ringan
        $this->user = User::with(['mahasiswaProfile.pengajuans'])->findOrFail($id);
    }

    /**
     * Mengambil riwayat transaksi ASLI dari database untuk mahasiswa ini.
     */
    #[Computed]
    public function riwayatTransaksi()
    {
        // Jika belum disetujui (belum dapat dompet), pasti belum ada transaksi.
        if ($this->user->mahasiswaProfile->status_bantuan !== 'disetujui') {
            return collect(); 
        }

        return Transaction::with(['merchant.merchantProfile'])
            ->where('user_id', $this->user->id)
            ->where('type', 'pembayaran_makanan') // 🟢 TAMBAHKAN BARIS INI (Filter Khusus Jajan)
            ->latest()
            ->get();
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
        session()->flash('message', 'Data profil mahasiswa berhasil diperbarui.');
    }

    // CATATAN ENGINEER:
    // Fungsi accPengajuan() saya HAPUS secara paksa. 
    // Persetujuan dana HARUS dilakukan di panel LKBB yang memiliki Pessimistic Locking 
    // dan pemotongan saldo Donasi. Jangan pernah mem-bypass alur keuangan dari sini!

}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.mahasiswa.index') }}" wire:navigate class="hover:text-blue-600 transition">Data Mahasiswa</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            <span class="font-medium text-gray-900">Detail Mahasiswa</span>
        </div>

        {{-- Flash Notification --}}
        @if (session()->has('message'))
            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 flex items-center gap-1.5 animate-pulse">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                {{ session('message') }}
            </span>
        @endif
    </div>

    {{-- KARTU HEADER PROFIL --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-5">
            <div class="h-20 w-20 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold shadow-inner flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                    
                    @if($user->mahasiswaProfile->status_bantuan == 'disetujui')
                        <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-emerald-100 uppercase tracking-wide">Dana Aktif</span>
                    @elseif($user->mahasiswaProfile->status_bantuan == 'diajukan')
                        <span class="bg-blue-50 text-blue-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-blue-100 uppercase tracking-wide flex items-center gap-1">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Diproses LKBB
                        </span>
                    @elseif($user->mahasiswaProfile->status_bantuan == 'ditolak')
                        <span class="bg-red-50 text-red-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-red-100 uppercase tracking-wide">Ditolak LKBB</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">Belum Diajukan</span>
                    @endif
                    
                </div>
                <p class="text-gray-500 font-medium text-sm">NIM: <span class="font-mono text-gray-700 font-bold">{{ $user->mahasiswaProfile->nim ?? '-' }}</span> • {{ $user->mahasiswaProfile->jurusan ?? '-' }}</p>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> Terdaftar: {{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('admin.mahasiswa.index') }}" wire:navigate class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium text-sm transition text-center w-full md:w-auto focus:ring-4 focus:ring-gray-100">
                Kembali
            </a>
            <button wire:click="openEditModal" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium text-sm shadow-sm transition flex items-center justify-center w-full md:w-auto gap-2 focus:ring-4 focus:ring-blue-100">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Edit Data
            </button>
        </div>
    </div>

    {{-- KARTU INFORMASI (3 KOLOM) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        
        {{-- Info Pribadi --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                Informasi Pribadi
            </div>
            
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email</p>
                    <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No HP Aktif</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $user->mahasiswaProfile->no_hp ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Alamat Tempat Tinggal</p>
                    <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $user->mahasiswaProfile->alamat ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Info Akademik --}}
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full">
            <div class="flex items-center gap-2 mb-6 text-gray-400 font-bold text-xs uppercase tracking-wider">
                <svg class="w-5 h-5 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6" /></svg>
                Data Akademik
            </div>
            
            <div class="space-y-4 flex-1">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Institusi</p>
                    <p class="text-gray-900 font-medium text-sm">Institut Teknologi Bandung</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Program Studi</p>
                    <p class="text-gray-900 font-medium text-sm">{{ $user->mahasiswaProfile->jurusan ?: '-' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Semester</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->mahasiswaProfile->semester ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">IPK Terakhir</p>
                        <p class="text-gray-900 font-extrabold text-sm">{{ $user->mahasiswaProfile->ipk ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- E-Wallet Card --}}
        <div class="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 pointer-events-none transition-transform group-hover:scale-110"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    </div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase">E-WALLET</span>
                </div>
                
                <div>
                    <p class="text-blue-200 text-[10px] font-bold tracking-wider mb-1 uppercase">Sisa Saldo Bantuan</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        Rp {{ number_format($user->mahasiswaProfile->saldo, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            
            <div class="flex justify-between items-end pt-5 border-t border-blue-400/30 relative z-10 mt-auto">
                <div class="min-w-0 pr-2">
                    <p class="text-[9px] text-blue-200 mb-0.5 font-bold tracking-wider truncate uppercase">Total Dana Turun</p>
                    <p class="font-bold text-sm truncate">Rp {{ number_format($user->mahasiswaProfile->pengajuans->where('status', 'disetujui')->sum('nominal'), 0, ',', '.') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[9px] text-blue-200 mb-0.5 font-bold tracking-wider uppercase">Status</p>
                    <p class="font-bold text-xs text-emerald-300 tracking-wide flex items-center gap-1.5 justify-end uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 {{ $user->mahasiswaProfile->status_bantuan == 'disetujui' ? 'animate-pulse' : 'opacity-50' }}"></span> 
                        {{ $user->mahasiswaProfile->status_bantuan == 'disetujui' ? 'Active' : 'Locked' }}
                    </p>
                </div>
            </div>
        </div>
        
    </div>

    {{-- TAB DATA BAWAH --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4 overflow-hidden">
        
        {{-- Navigasi Tab --}}
        <div class="flex border-b border-gray-100 px-2 sm:px-6 gap-2 sm:gap-6 overflow-x-auto bg-gray-50/50">
            <button wire:click="$set('activeTab', 'transaksi')" 
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab == 'transaksi' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Riwayat Jajan (Transaksi)
            </button>
            <button wire:click="$set('activeTab', 'pengajuan')" 
                class="py-4 px-2 font-bold text-sm whitespace-nowrap transition-colors border-b-2 {{ $activeTab == 'pengajuan' ? 'text-blue-700 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700' }}">
                Riwayat Pencairan Subsidi
            </button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            
            {{-- KONTEN TAB: TRANSAKSI (REAL DATA) --}}
            @if($activeTab == 'transaksi')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Waktu & Resi</th>
                        <th class="px-6 py-4">Keterangan / Merchant</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->riwayatTransaksi as $trx)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="px-6 py-4">
                            <div class="font-bold text-xs text-gray-900 font-mono mb-0.5">{{ $trx->order_id }}</div>
                            <div class="text-[10px] text-gray-500">{{ $trx->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($trx->type === 'pembayaran_makanan')
                                <div class="font-bold text-sm text-gray-900">
                                    {{ $trx->merchant->merchantProfile->nama_kantin ?? 'Kantin Tidak Diketahui' }}
                                </div>
                                <div class="text-[10px] text-gray-500 uppercase tracking-wider flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $trx->description ?? 'Pengeluaran (Jajan)' }}
                                </div>
                            @elseif($trx->type === 'penerimaan_bantuan')
                                <div class="font-bold text-sm text-gray-900">Pencairan Dana LKBB</div>
                                <div class="text-[10px] text-gray-500 uppercase tracking-wider flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Pemasukan (Subsidi)
                                </div>
                            @else
                                <div class="font-bold text-sm text-gray-900">{{ ucwords(str_replace('_', ' ', $trx->type)) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(in_array($trx->status, ['sukses', 'lunas']))
                                <span class="bg-emerald-50 text-emerald-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Sukses</span>
                            @else
                                <span class="bg-red-50 text-red-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Gagal</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-extrabold text-sm text-right {{ $trx->type === 'penerimaan_bantuan' ? 'text-emerald-600' : 'text-gray-900' }}">
                            {{ $trx->type === 'penerimaan_bantuan' ? '+' : '-' }} Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">💸</div>
                            <p class="text-gray-500 text-sm font-medium">Belum ada riwayat transaksi.</p>
                            <p class="text-gray-400 text-xs mt-1">Transaksi akan muncul otomatis ketika mahasiswa berbelanja.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            {{-- KONTEN TAB: PENGAJUAN --}}
            @if($activeTab == 'pengajuan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Pengajuan</th>
                        <th class="px-6 py-4">Nominal Subsidi</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4 text-right">Status Verifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($user->mahasiswaProfile->pengajuans->sortByDesc('created_at') as $pengajuan)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-900 font-mono">{{ $pengajuan->nomor_pengajuan }}</td>
                        <td class="px-6 py-4 font-extrabold text-sm text-blue-600">Rp {{ number_format($pengajuan->nominal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $pengajuan->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            @if($pengajuan->status == 'disetujui')
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-emerald-100">Telah Cair</span>
                            @elseif($pengajuan->status == 'diajukan')
                                <span class="bg-yellow-50 text-yellow-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-yellow-100">Menunggu LKBB</span>
                            @else
                                <span class="bg-red-50 text-red-700 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider border border-red-100">Ditolak</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="text-4xl text-gray-300 mx-auto mb-3 opacity-50">📬</div>
                            <p class="text-gray-500 text-sm font-medium">Belum ada riwayat pengajuan subsidi.</p>
                            <p class="text-gray-400 text-xs mt-1">Gunakan tombol "Ajukan Saldo" di halaman utama untuk memproses bantuan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
            
        </div>
    </div>

    {{-- Modal Edit (Tetap Sama Seperti Sebelumnya) --}}
    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Profil Mahasiswa
                </h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-xl hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap Sesuai KTP</label>
                    <input wire:model="edit_name" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                </div>
                
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Akses</label>
                        <input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nomor WhatsApp</label>
                        <input wire:model="edit_no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">NIM Resmi</label>
                        <input wire:model="edit_nim" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5 font-mono font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Universitas</label>
                        <input type="text" value="Institut Teknologi Bandung" disabled class="w-full text-sm rounded-xl border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed font-medium py-2.5">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Program Studi</label>
                        <input wire:model="edit_jurusan" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Semester</label>
                        <input wire:model="edit_semester" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">IPK (0.00-4.00)</label>
                        <input wire:model="edit_ipk" type="number" step="0.01" max="4.00" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Tempat Tinggal</label>
                    <textarea wire:model="edit_alamat" rows="2" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"></textarea>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition-colors focus:ring-4 focus:ring-gray-100">
                    Batal
                </button>
                <button wire:click="updateData" class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-sm focus:ring-4 focus:ring-blue-100">
                    Simpan Perubahan
                </button>
            </div>

        </div>
    </div>
    @endif

</div>