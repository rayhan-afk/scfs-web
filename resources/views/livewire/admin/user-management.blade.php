<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\User;

new 
#[Layout('layouts.app')]
class extends Component {
    use WithPagination;

    public $search = '';
    public $filterRole = ''; // Variabel untuk menyimpan filter role

    // Modal States
    public $isEditModalOpen = false;
    public $isDeleteModalOpen = false;

    // Form Data
    public $userId, $name, $email, $role;

    // Reset paginasi saat ada pencarian atau filter yang berubah
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterRole()
    {
        $this->resetPage();
    }

    public function getUsersProperty()
    {
        $query = User::with('latestLogin');

        // Pencarian teks (Nama, Email)
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Filter berdasarkan Role
        if ($this->filterRole !== '') {
            $query->where('role', $this->filterRole);
        }

        return $query->latest()->paginate(10);
    }

    // --- FUNGSI EDIT ---
    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        
        $this->isEditModalOpen = true;
    }

    public function updateUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
        ]);

        $user = User::findOrFail($this->userId);
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->isEditModalOpen = false;
        session()->flash('message', 'Data pengguna berhasil diperbarui!');
    }

    // --- FUNGSI HAPUS ---
    public function confirmDelete($id)
    {
        $this->userId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function deleteUser()
    {
        $user = User::findOrFail($this->userId);

        if (auth()->id() === $user->id) {
            session()->flash('error', 'Akses ditolak: Anda tidak dapat menghapus akun Anda sendiri.');
            $this->isDeleteModalOpen = false;
            return;
        }

        $user->delete();
        $this->isDeleteModalOpen = false;
        session()->flash('message', 'Akun pengguna berhasil dihapus permanen.');
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Manajemen Akun Pengguna</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola akses, edit data dasar, dan pantau aktivitas log masuk pengguna.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col lg:flex-row justify-between items-center gap-4">
        
        <div class="flex flex-col md:flex-row gap-3 w-full lg:w-auto flex-1">
            <div class="relative w-full lg:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau email..." 
                    class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 transition">
            </div>

            <div class="relative w-full lg:w-48">
                <select wire:model.live="filterRole" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition">
                    <option value="">Semua Hak Akses</option>
                    <option value="admin">Admin</option>
                    <option value="lkbb">LKBB</option>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="merchant">Merchant</option>
                    <option value="pemasok">Pemasok</option>
                    <option value="investor">Investor</option>
                    <option value="donatur">Donatur</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 px-4 py-2.5 bg-blue-50 border border-blue-100 rounded-xl shadow-sm whitespace-nowrap w-full lg:w-auto justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <div class="text-sm">
                <span class="font-extrabold text-blue-700">{{ $this->users->total() }}</span> 
                <span class="font-medium text-blue-600">Pengguna</span>
            </div>
        </div>

    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/80 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Informasi Pengguna</th>
                        <th class="px-6 py-4 text-center">Hak Akses (Role)</th>
                        <th class="px-6 py-4">Tanggal Bergabung</th>
                        <th class="px-6 py-4">Terakhir Login</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->users as $user)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-blue-100 text-blue-700 border border-blue-200 flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                        {{ $user->name }}
                                        @if(auth()->id() === $user->id)
                                            <span class="text-[9px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">You</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5 font-mono">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @php
                                $roleClass = match($user->role) {
                                    'admin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'lkbb' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                    'mahasiswa' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'merchant' => 'bg-orange-100 text-orange-700 border-orange-200',
                                    'pemasok' => 'bg-teal-100 text-teal-700 border-teal-200',
                                    'investor' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'donatur' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    default => 'bg-gray-100 text-gray-600 border-gray-200'
                                };
                            @endphp
                            <span class="{{ $roleClass }} text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border">
                                {{ $user->role ?? 'User' }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-800">{{ $user->created_at->format('d M Y') }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">{{ $user->created_at->format('H:i') }} WIB</div>
                        </td>

                        <td class="px-6 py-4">
                            @if($user->latestLogin)
                                <div class="text-sm font-medium text-emerald-600">{{ $user->latestLogin->login_at->diffForHumans() }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $user->latestLogin->login_at->format('d M Y, H:i') }}</div>
                            @else
                                <div class="text-sm font-medium text-gray-400 italic">Belum pernah login</div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="editUser({{ $user->id }})" class="p-2 text-blue-600 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 hover:border-blue-200 transition" title="Edit Akun">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                
                                @if(auth()->id() !== $user->id)
                                <button wire:click="confirmDelete({{ $user->id }})" class="p-2 text-red-600 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 hover:border-red-200 transition" title="Hapus Akun">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <p class="text-sm font-medium">Tidak ada pengguna yang ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($this->users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $this->users->links(data: ['scrollTo' => false]) }}
        </div>
        @endif
    </div>

    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Data Pengguna
                </h3>
                <button wire:click="$set('isEditModalOpen', false)" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <input wire:model="name" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    @error('name') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email (Login)</label>
                    <input wire:model="email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white py-2.5">
                    @error('email') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Role (Hak Akses)</label>
                    <input wire:model="role" type="text" disabled class="w-full text-sm rounded-xl border-gray-200 bg-gray-100 text-gray-500 py-2.5 cursor-not-allowed">
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="$set('isEditModalOpen', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">Batal</button>
                <button wire:click="updateUser" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-sm">Simpan</button>
            </div>
        </div>
    </div>
    @endif

    @if($isDeleteModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden text-center">
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Hapus Pengguna Ini?</h3>
                <p class="text-sm text-gray-500">Tindakan ini permanen. Semua data profil yang terkait dengan akun ini akan ikut terhapus.</p>
            </div>
            
            <div class="px-6 py-4 flex gap-3 bg-gray-50/50 border-t border-gray-100">
                <button wire:click="$set('isDeleteModalOpen', false)" class="w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">Batal</button>
                <button wire:click="deleteUser" class="w-full px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition shadow-sm">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif

</div>