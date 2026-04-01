<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\PemasokProfile;
use Illuminate\Support\Facades\Hash;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    // Pencarian & Filter
    public $search = '';
    public $filterKategori = 'Semua'; 

    // Form Tambah Pemasok
    public $isAddModalOpen = false;
    public $nama_perusahaan, $nama_pic, $no_hp, $kategori_barang, $alamat, $info_bank;
    public $email, $password; 

    public function getPemasoksProperty()
    {
        $query = User::where('role', 'pemasok')
                     ->has('pemasokProfile') 
                     ->with('pemasokProfile');

        if ($this->filterKategori !== 'Semua') {
            $query->whereHas('pemasokProfile', function($q) {
                $q->where('kategori_barang', $this->filterKategori);
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('pemasokProfile', function($q2) {
                    $q2->where('nama_perusahaan', 'like', '%' . $this->search . '%')
                       ->orWhere('nama_pic', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->latest()->get();
    }

    public function getStatsProperty()
    {
        return [
            'total_pemasok' => PemasokProfile::count(),
            'total_hutang' => PemasokProfile::sum('tagihan_berjalan'),
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
        $this->reset(['nama_perusahaan', 'nama_pic', 'no_hp', 'kategori_barang', 'alamat', 'info_bank', 'email', 'password']);
        $this->kategori_barang = 'Sembako'; // default
    }

    public function savePemasok()
    {
        $this->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'nama_pic' => 'required|string|max:255',
            'kategori_barang' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $this->nama_pic, // PIC sebagai nama user login
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'pemasok',
        ]);

        PemasokProfile::create([
            'user_id' => $user->id,
            'nama_perusahaan' => $this->nama_perusahaan,
            'nama_pic' => $this->nama_pic,
            'kategori_barang' => $this->kategori_barang,
            'no_hp' => $this->no_hp,
            'alamat' => $this->alamat,
            'info_bank' => $this->info_bank,
            'status_kemitraan' => 'aktif',
        ]);

        $this->closeAddModal();
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">
    
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Manajemen Pemasok (Supplier)</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola mitra rantai pasok bahan baku kantin dan pantau tagihan pembayaran LKBB.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-0.5">Mitra Pemasok</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ $this->stats['total_pemasok'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Tagihan Berjalan (Hutang LKBB)</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_hutang'], 0, ',', '.') }}</h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 opacity-70">
            <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Total PO Selesai</p>
                <h3 class="text-xl font-extrabold text-gray-500">0 <span class="text-xs font-medium">Transaksi</span></h3>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto flex-1">
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input wire:model.live="search" type="text" placeholder="Cari nama perusahaan atau PIC..." 
                    class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500 transition">
            </div>

            <div class="relative w-full md:w-48">
                <select wire:model.live="filterKategori" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition">
                    <option value="Semua">Semua Kategori</option>
                    <option value="Sembako">Sembako</option>
                    <option value="Daging & Unggas">Daging & Unggas</option>
                    <option value="Sayur & Buah">Sayur & Buah</option>
                    <option value="Kemasan">Kemasan</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <button wire:click="openAddModal" class="bg-[#137FEC] text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-[#0f6fd1] transition shadow-lg shadow-gray-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Pemasok
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Perusahaan / Toko</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Kontak (PIC)</th>
                        <th class="px-6 py-4 text-right">Tagihan Berjalan</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->pemasoks as $pemasok)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-xl flex items-center justify-center text-lg shadow-sm font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">
                                    📦
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $pemasok->pemasokProfile?->nama_perusahaan ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">PIC: <span class="font-medium text-gray-700">{{ $pemasok->pemasokProfile?->nama_pic ?? '-' }}</span></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span class="bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded-md font-medium border border-gray-200">
                                {{ $pemasok->pemasokProfile?->kategori_barang ?? '-' }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $pemasok->pemasokProfile?->no_hp ?? '-' }}</div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold {{ ($pemasok->pemasokProfile?->tagihan_berjalan ?? 0) > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                                Rp {{ number_format($pemasok->pemasokProfile?->tagihan_berjalan ?? 0, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if(($pemasok->pemasokProfile?->status_kemitraan ?? 'nonaktif') == 'aktif')
                                <span class="bg-indigo-50 text-indigo-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-indigo-200">
                                    Aktif
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-gray-200">
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.pemasok.detail', $pemasok->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                                Detail
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-4xl mb-3">📦</div>
                            <p class="text-gray-500 text-sm font-medium">Belum ada data pemasok yang terdaftar.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isAddModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Daftarkan Pemasok Baru
                </h3>
                <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Perusahaan / Toko</label>
                        <input wire:model="nama_perusahaan" type="text" placeholder="Cth: PT Pangan Nusantara" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Kategori Produk</label>
                        <select wire:model="kategori_barang" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5 cursor-pointer">
                            <option value="Sembako">Sembako</option>
                            <option value="Daging & Unggas">Daging & Unggas</option>
                            <option value="Sayur & Buah">Sayur & Buah</option>
                            <option value="Kemasan">Kemasan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama PIC (Penanggung Jawab)</label>
                        <input wire:model="nama_pic" type="text" placeholder="Cth: Budi Santoso" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA</label>
                        <input wire:model="no_hp" type="text" placeholder="Cth: 0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Info Rekening Bank (Untuk Pembayaran LKBB)</label>
                    <input wire:model="info_bank" type="text" placeholder="Cth: BCA 1234567890 a.n PT Pangan" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                </div>

                <hr class="border-gray-100 my-2">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Login Aplikasi</label>
                        <input wire:model="email" type="email" placeholder="budi@pangan.com" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password Sementara</label>
                        <input wire:model="password" type="password" placeholder="Minimal 6 karakter" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white py-2.5">
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeAddModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="savePemasok" class="bg-[#137FEC] text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-[#0f6fd1] transition shadow-lg shadow-gray-200 flex items-center gap-2">Simpan Pemasok</button>
            </div>
        </div>
    </div>
    @endif

</div>