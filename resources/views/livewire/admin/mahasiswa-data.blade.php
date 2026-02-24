<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\PengajuanBantuan; // Pastikan model ini dipanggil

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public $activeTab = 'Semua'; 

    public function getStudentsProperty()
    {
        $query = User::where('role', 'mahasiswa')
            ->whereHas('mahasiswaProfile', function($q) {
                $q->where('status_verifikasi', 'disetujui');
            })->with('mahasiswaProfile');

        if ($this->activeTab !== 'Semua') {
            $status = strtolower(str_replace(' ', '_', $this->activeTab));
            $query->whereHas('mahasiswaProfile', function($q) use ($status) {
                if ($status == 'belum_diajukan') {
                    $q->whereNull('status_bantuan')->orWhere('status_bantuan', 'belum_diajukan');
                } else {
                    $q->where('status_bantuan', $status);
                }
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('mahasiswaProfile', function($q2) {
                      $q2->where('nim', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->latest()->get();
    }

    // Fungsi baru untuk mengeksekusi pengajuan langsung dari tabel
    public function ajukanKeLKBB($userId)
    {
        $user = User::with('mahasiswaProfile')->find($userId);
        
        if ($user && $user->mahasiswaProfile) {
            $profil = $user->mahasiswaProfile;
            
            // 1. Buat record di tabel pengajuan_bantuans
            PengajuanBantuan::create([
                'mahasiswa_profile_id' => $profil->id,
                'nominal' => 500000, // Default nominal
                'status' => 'diajukan',
                'nomor_pengajuan' => 'SC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)
            ]);
            
            // 2. Update status_bantuan di tabel mahasiswa_profiles
            $profil->update(['status_bantuan' => 'diajukan']);
        }
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">
    
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Buku Induk Mahasiswa</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola data mahasiswa aktif, pantau saldo bantuan, dan ajukan penerima donasi ke LKBB.</p>
    </div>

    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-96">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Cari nama atau NIM..." 
                class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition">
        </div>

        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            @foreach(['Semua', 'Belum Diajukan', 'Diajukan', 'Disetujui'] as $tab)
                <button 
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap
                    {{ $activeTab === $tab ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    {{ $tab }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">Daftar Mahasiswa Aktif</h3>
            <span class="text-sm text-gray-500">Total: {{ $this->students->count() }} Mahasiswa</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama / NIM</th>
                        <th class="px-6 py-4">Jurusan</th>
                        <th class="px-6 py-4">Sisa Saldo</th>
                        <th class="px-6 py-4">Status Bantuan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->students as $student)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-indigo-100 text-indigo-600">
                                    {{ strtoupper(substr($student->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $student->name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $student->mahasiswaProfile->nim ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ $student->mahasiswaProfile->jurusan ?? '-' }}</td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-bold {{ $student->mahasiswaProfile->saldo > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                Rp {{ number_format($student->mahasiswaProfile->saldo, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            @if($student->mahasiswaProfile->status_bantuan == 'disetujui')
                                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-bold border border-green-200">ACC LKBB</span>
                            @elseif($student->mahasiswaProfile->status_bantuan == 'diajukan')
                                <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-bold border border-blue-200 flex items-center inline-flex gap-1 w-max">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Menunggu LKBB
                                </span>
                            @elseif($student->mahasiswaProfile->status_bantuan == 'ditolak')
                                <span class="bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full font-bold border border-red-200">Ditolak LKBB</span>
                            @else
                                <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full font-bold border border-gray-200">Belum Diajukan</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                
                                @if(empty($student->mahasiswaProfile->status_bantuan) || $student->mahasiswaProfile->status_bantuan == 'belum_diajukan' || $student->mahasiswaProfile->status_bantuan == 'ditolak')
                                    <button wire:click="ajukanKeLKBB({{ $student->id }})" class="inline-flex items-center px-3 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm focus:ring-2 focus:ring-blue-200">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Ajukan LKBB
                                    </button>
                                @endif

                                <a href="{{ route('admin.mahasiswa.detail', $student->id) }}" 
                                   class="inline-flex items-center px-3 py-2 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                    Detail
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                                
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada data mahasiswa yang diverifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>