<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\InvestorProfile;
use Illuminate\Support\Facades\Hash;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public $search = '';
    public $filterStatus = 'Semua'; 

    // Form Tambah Investor
    public $isAddModalOpen = false;
    public $nama_lengkap, $perusahaan, $no_hp, $alamat, $info_bank;
    public $email, $password; 

    public function getInvestorsProperty()
    {
        $query = User::where('role', 'investor')
                     ->has('investorProfile') 
                     ->with('investorProfile');

        if ($this->filterStatus !== 'Semua') {
            $status = strtolower($this->filterStatus);
            $query->whereHas('investorProfile', function($q) use ($status) {
                $q->where('status_kemitraan', $status);
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('investorProfile', function($q2) {
                    $q2->where('nama_lengkap', 'like', '%' . $this->search . '%')
                       ->orWhere('perusahaan', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->latest()->get();
    }

    public function getStatsProperty()
    {
        return [
            'total_investor' => InvestorProfile::count(),
            'total_modal' => InvestorProfile::sum('total_investasi_aktif'),
            'total_dibagikan' => InvestorProfile::sum('total_bagi_hasil'),
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
        $this->reset(['nama_lengkap', 'perusahaan', 'no_hp', 'alamat', 'info_bank', 'email', 'password']);
    }

    public function saveInvestor()
    {
        $this->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $this->nama_lengkap,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'investor',
        ]);

        InvestorProfile::create([
            'user_id' => $user->id,
            'nama_lengkap' => $this->nama_lengkap,
            'perusahaan' => $this->perusahaan,
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
        <h2 class="text-2xl font-bold text-gray-900">Manajemen Investor</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola data pemodal ekosistem SCFS, pantau pendanaan aktif, dan riwayat bagi hasil.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-0.5">Total Investor</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ $this->stats['total_investor'] }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Investasi Aktif (Dikelola)</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_modal'], 0, ',', '.') }}</h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-50 text-green-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">Total Bagi Hasil Dibagikan</p>
                <h3 class="text-xl font-extrabold text-gray-900">Rp {{ number_format($this->stats['total_dibagikan'], 0, ',', '.') }}</h3>
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
                    class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:border-teal-500 focus:ring-teal-500 transition">
            </div>

            <div class="relative w-full md:w-40">
                <select wire:model.live="filterStatus" class="appearance-none w-full py-2.5 pl-4 pr-10 text-sm font-medium text-gray-700 bg-gray-50 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer transition">
                    <option value="Semua">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Non-aktif</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <button wire:click="openAddModal" class="w-full md:w-auto px-5 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 font-medium text-sm shadow-sm transition flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Tambah Investor
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Nama & Institusi</th>
                        <th class="px-6 py-4">Kontak (No HP)</th>
                        <th class="px-6 py-4 text-right">Modal Dikelola (Aktif)</th>
                        <th class="px-6 py-4 text-right">Profit Diterima</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->investors as $inv)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-teal-100 text-teal-700 border border-teal-200">
                                    {{ strtoupper(substr($inv->pemasokProfile?->nama_lengkap ?? $inv->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $inv->investorProfile?->nama_lengkap ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $inv->investorProfile?->perusahaan ?: 'Investor Individu' }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $inv->investorProfile?->no_hp ?? '-' }}</div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold {{ ($inv->investorProfile?->total_investasi_aktif ?? 0) > 0 ? 'text-teal-600' : 'text-gray-400' }}">
                                Rp {{ number_format($inv->investorProfile?->total_investasi_aktif ?? 0, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-green-600">
                                Rp {{ number_format($inv->investorProfile?->total_bagi_hasil ?? 0, 0, ',', '.') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if(($inv->investorProfile?->status_kemitraan ?? 'nonaktif') == 'aktif')
                                <span class="bg-teal-50 text-teal-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-teal-200">
                                    Aktif
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-gray-200">
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.investor.detail', $inv->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-colors">
                                Detail
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-gray-500 text-sm font-medium">Belum ada data investor yang terdaftar.</p>
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
                    <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Daftarkan Investor Baru
                </h3>
                <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap (Sesuai KTP/Akta)</label>
                        <input wire:model="nama_lengkap" type="text" placeholder="Cth: Budi Gunawan" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Perusahaan / Institusi (Opsional)</label>
                        <input wire:model="perusahaan" type="text" placeholder="Kosongkan jika individu" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA</label>
                        <input wire:model="no_hp" type="text" placeholder="Cth: 0812..." class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Info Rekening Bank (Bagi Hasil)</label>
                        <input wire:model="info_bank" type="text" placeholder="Cth: Mandiri 12345 a.n Budi" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Lengkap</label>
                    <textarea wire:model="alamat" rows="2" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white"></textarea>
                </div>

                <hr class="border-gray-100 my-2">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Login Aplikasi</label>
                        <input wire:model="email" type="email" placeholder="budi@investor.com" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password Sementara</label>
                        <input wire:model="password" type="password" placeholder="Minimal 6 karakter" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeAddModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="saveInvestor" class="px-5 py-2 text-sm font-medium text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-colors shadow-sm focus:ring-4 focus:ring-teal-100">Simpan Investor</button>
            </div>
        </div>
    </div>
    @endif

</div>