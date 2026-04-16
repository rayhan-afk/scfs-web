<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\DonaturProfile;
use Illuminate\Support\Facades\Hash;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public $filterStatus = 'Semua'; 

    // Form Tambah Donatur
    public $isAddModalOpen = false;
    public $nama_lengkap, $institusi, $no_hp, $rekening_sumber, $alamat, $tipe_donatur = 'insidental';
    public $email, $password; 

    public function getDonatursProperty()
    {
        $query = User::where('role', 'donatur')
                     ->has('donaturProfile') 
                     ->with('donaturProfile');

        if ($this->filterStatus !== 'Semua') {
            $status = strtolower($this->filterStatus);
            $query->whereHas('donaturProfile', function($q) use ($status) {
                $q->where('status_kemitraan', $status);
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('donaturProfile', function($q2) {
                    $q2->where('nama_lengkap', 'like', '%' . $this->search . '%')
                       ->orWhere('institusi', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->latest()->get();
    }

    public function getStatsProperty()
    {
        return [
            'total_donatur' => DonaturProfile::count(),
            'total_donasi' => DonaturProfile::sum('total_donasi'),
        ];
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->isAddModalOpen = true;
    }

    public function closeAddModal()
    {
        $this->isAddModalOpen = false;
    }

    public function resetForm()
    {
        $this->reset(['nama_lengkap', 'institusi', 'no_hp', 'rekening_sumber', 'alamat', 'email', 'password']);
        $this->tipe_donatur = 'insidental';
    }

    public function saveDonatur()
    {
        // 1. Validasi Input
        $validated = $this->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'no_hp' => 'required', // Opsional: tambahkan validasi lain jika perlu
        ]);

        try {
            // Gunakan Database Transaction agar jika satu gagal, semua batal (Rollback)
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 2. Simpan Data User
            $user = User::create([
                'name' => $this->nama_lengkap,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'donatur',
            ]);

            // 3. Simpan Data Profile
            DonaturProfile::create([
                'user_id' => $user->id,
                'nama_lengkap' => $this->nama_lengkap,
                'institusi' => $this->institusi,
                'no_hp' => $this->no_hp,
                'rekening_sumber' => $this->rekening_sumber,
                'alamat' => $this->alamat,
                'tipe_donatur' => $this->tipe_donatur,
                'status_kemitraan' => 'aktif'
            ]);

            \Illuminate\Support\Facades\DB::commit();

            // 4. Tutup Modal & Reset Form
            $this->closeAddModal();
            $this->resetForm();

            // 5. Kirim Notifikasi Berhasil
            $this->dispatch('swal:success', 
                title: 'Berhasil!', 
                text: 'Data donatur baru telah berhasil disimpan.',
                icon: 'success'
            );

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            // 6. Kirim Notifikasi Gagal
            $this->dispatch('swal:error', 
                title: 'Gagal Menyimpan!', 
                text: 'Terjadi kesalahan sistem. Silakan coba lagi.',
                icon: 'error'
            );
        }
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">
    
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Manajemen Donatur</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola data pahlawan donasi dan pantau total dana bantuan yang telah disalurkan.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-0.5">Total Donatur</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ $this->stats['total_donatur'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Total Donasi Terkumpul</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_donasi'], 0, ',', '.') }}</h3>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-rose-500 to-rose-600 p-6 rounded-2xl shadow-md flex items-center gap-4 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-6 -mt-6 pointer-events-none"></div>
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm z-10">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <div class="z-10">
                <p class="text-[10px] text-rose-100 font-bold uppercase tracking-wider mb-0.5">Mahasiswa Terbantu</p>
                <h3 class="text-xl font-extrabold text-white">{{ floor(($this->stats['total_donasi']) / 500000) }} <span class="text-xs font-medium opacity-80">Orang</span></h3>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto flex-1">
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari nama atau institusi..." 
                    class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:border-rose-500 focus:ring-rose-500 transition">
            </div>

            <div class="relative w-full md:w-40">
                <select wire:model.live="filterStatus" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500 cursor-pointer transition">
                    <option value="Semua">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Non-aktif</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <button wire:click="openAddModal" class="w-full md:w-auto px-5 py-2.5 bg-rose-600 text-white rounded-xl hover:bg-rose-700 font-medium text-sm shadow-sm transition flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Donatur
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Nama Donatur</th>
                        <th class="px-6 py-4">Institusi & Tipe</th>
                        <th class="px-6 py-4">Kontak (No HP)</th>
                        <th class="px-6 py-4 text-right">Total Donasi</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->donaturs as $donatur)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                    {{ strtoupper(substr($donatur->donaturProfile?->nama_lengkap ?? $donatur->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $donatur->donaturProfile?->nama_lengkap ?? '-' }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ $donatur->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-700 mb-1">
                                {{ $donatur->donaturProfile?->institusi ?: 'Individu' }}
                            </div>
                            @if(($donatur->donaturProfile?->tipe_donatur ?? 'insidental') == 'rutin')
                                <span class="bg-blue-50 text-blue-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase border border-blue-100">Donatur Rutin</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-[9px] px-2 py-0.5 rounded font-bold uppercase border border-gray-200">Insidental</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $donatur->donaturProfile?->no_hp ?? '-' }}</div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold {{ ($donatur->donaturProfile?->total_donasi ?? 0) > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                Rp {{ number_format($donatur->donaturProfile?->total_donasi ?? 0, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if(($donatur->donaturProfile?->status_kemitraan ?? 'nonaktif') == 'aktif')
                                <span class="bg-rose-50 text-rose-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-rose-200">Aktif</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-gray-200">Nonaktif</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.donatur.detail', $donatur->id) }}" wire:navigate class="inline-flex items-center px-4 py-2 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors">
                                Detail
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            <p class="text-gray-500 text-sm font-medium">Belum ada data donatur yang terdaftar.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isAddModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Daftarkan Donatur Baru
                </h3>
                <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap Donatur</label>
                        <input wire:model="nama_lengkap" type="text" placeholder="Cth: Bapak Ahmad" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Institusi / Yayasan (Opsional)</label>
                        <input wire:model="institusi" type="text" placeholder="Kosongkan jika individu" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA</label>
                        <input wire:model="no_hp" type="text" placeholder="Cth: 0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Tipe Donatur</label>
                        <select wire:model="tipe_donatur" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5 cursor-pointer">
                            <option value="insidental">Insidental (Sekali Waktu)</option>
                            <option value="rutin">Rutin (Bulanan/Tahunan)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Rekening Sumber (Utk Lacak Mutasi Masuk)</label>
                        <input wire:model="rekening_sumber" type="text" placeholder="Cth: BCA 12345 a.n Ahmad" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat (Utk Kirim Sertifikat Laporan)</label>
                        <textarea wire:model="alamat" rows="1" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white"></textarea>
                    </div>
                </div>

                <hr class="border-gray-100 my-2">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Akses Login</label>
                        <input wire:model="email" type="email" placeholder="donatur@mail.com" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password Sementara</label>
                        <input wire:model="password" type="password" placeholder="Minimal 6 karakter" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 bg-white py-2.5">
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeAddModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="saveDonatur" class="px-5 py-2 text-sm font-medium text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors shadow-sm focus:ring-4 focus:ring-rose-100">Simpan Donatur</button>
            </div>
        </div>
    </div>
    @endif

</div>