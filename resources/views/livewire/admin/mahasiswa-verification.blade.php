<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public $activeTab = 'Semua'; 

    public function getStudentsProperty()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'Budi Santoso',
                'nim' => 'NIM-1234',
                'jurusan' => 'S1 Informatika',
                'ktm' => 'KTM.jpg',
                'status' => 'Disetujui',
                'avatar_color' => 'bg-green-100 text-green-600'
            ],
            [
                'id' => 2,
                'name' => 'Siti Aminah',
                'nim' => 'NIM-5678',
                'jurusan' => 'S1 Teknik Fisika',
                'ktm' => 'KTM.jpg',
                'status' => 'Ditolak',
                'avatar_color' => 'bg-pink-100 text-pink-600'
            ],
            [
                'id' => 3,
                'name' => 'Ahmad Dani',
                'nim' => 'NIM-9012',
                'jurusan' => 'S1 Teknik Elektro',
                'ktm' => 'KTM.jpg',
                'status' => 'Menunggu',
                'avatar_color' => 'bg-blue-100 text-blue-600'
            ],
            [
                'id' => 4,
                'name' => 'Rina Wati',
                'nim' => 'NIM-3344',
                'jurusan' => 'S1 Manajemen',
                'ktm' => 'KTM.jpg',
                'status' => 'Menunggu',
                'avatar_color' => 'bg-purple-100 text-purple-600'
            ],
        ];

        return collect($data)->filter(function ($item) {
            if ($this->activeTab !== 'Semua' && $item['status'] !== $this->activeTab) {
                return false;
            }
            if ($this->search && stripos($item['name'], $this->search) === false && stripos($item['jurusan'], $this->search) === false) {
                return false;
            }
            return true;
        });
    }

    public function approve($id) { }
    public function reject($id) { }
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
        
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Status Verifikasi</h3>
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
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold {{ $student['avatar_color'] }}">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $student['name'] }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $student['nim'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ $student['jurusan'] }}</td>
                        <td class="px-6 py-4">
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline flex items-center gap-1">
                                {{ $student['ktm'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            @if($student['status'] == 'Disetujui')
                                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-bold border border-green-200">Disetujui</span>
                            @elseif($student['status'] == 'Ditolak')
                                <span class="bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full font-bold border border-red-200">Ditolak</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-3 py-1 rounded-full font-bold border border-yellow-200">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($student['status'] == 'Menunggu')
                                <div class="flex justify-end gap-2">
                                    <button class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm transition">Setuju</button>
                                    <button class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded shadow-sm transition">Tolak</button>
                                </div>
                            @else
                                <button class="text-gray-400 p-2"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>