<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public $activeTab = 'Semua'; 

    public function getStudentsProperty()
    {
        // Ambil user mahasiswa beserta relasi profilnya
        $query = User::where('role', 'mahasiswa')->with('mahasiswaProfile');

        // Filter dari tabel relasi
        if ($this->activeTab !== 'Semua') {
            $status = strtolower($this->activeTab);
            $query->whereHas('mahasiswaProfile', function($q) use ($status) {
                $q->where('status_verifikasi', $status);
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('mahasiswaProfile', function($q2) {
                      $q2->where('nim', 'like', '%' . $this->search . '%')
                         ->orWhere('jurusan', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->latest()->get();
    }

    public function approve($id)
    {
        $user = User::find($id);
        if ($user && $user->mahasiswaProfile) {
            $user->mahasiswaProfile->update(['status_verifikasi' => 'disetujui']);
        }
    }

    public function reject($id)
    {
        $user = User::find($id);
        if ($user && $user->mahasiswaProfile) {
            $user->mahasiswaProfile->update(['status_verifikasi' => 'ditolak']);
        }
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6">
    
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Verifikasi Mahasiswa</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola dan tinjau permintaan verifikasi akun mahasiswa untuk layanan SCFS.</p>
    </div>

    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        
        <div class="relative w-full md:w-96">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Cari nama mahasiswa atau jurusan..." 
                class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 focus:bg-white transition">
        </div>

        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            @foreach(['Semua', 'Menunggu', 'Disetujui', 'Ditolak'] as $tab)
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
            <h3 class="font-bold text-gray-900">Status Verifikasi</h3>
            <span class="text-sm text-gray-500">Total: {{ $this->students->count() }} Data</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama / NIM</th>
                        <th class="px-6 py-4">Jurusan</th>
                        <th class="px-6 py-4">Dokumen</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->students as $student)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-blue-100 text-blue-600">
                                    {{ strtoupper(substr($student->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $student->name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $student->mahasiswaProfile->nim ?? 'Belum isi NIM' }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-sm font-medium text-gray-700">
                            {{ $student->mahasiswaProfile->jurusan ?? '-' }}
                        </td>

                        <td class="px-6 py-4">
                            @if($student->mahasiswaProfile->ktm_image)
                                <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline flex items-center gap-1">
                                    Lihat KTM
                                </a>
                            @else
                                <span class="text-gray-400 text-sm">Belum Upload</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            @if($student->mahasiswaProfile->status_verifikasi== 'disetujui')
                                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-bold border border-green-200">Disetujui</span>
                            @elseif($student->status_verifikasi == 'ditolak')
                                <span class="bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full font-bold border border-red-200">Ditolak</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-3 py-1 rounded-full font-bold border border-yellow-200">Menunggu</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if($student->mahasiswaProfile->status_verifikasi == 'menunggu')
                                <div class="flex justify-end gap-2">
                                    <button wire:click="approve({{ $student->id }})" class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm transition">
                                        Setuju
                                    </button>
                                    <button wire:click="reject({{ $student->id }})" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded shadow-sm transition">
                                        Tolak
                                    </button>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs italic">Selesai</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada data mahasiswa ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>