<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Transaction; // Tambahkan model Transaction

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public User $user;
    public $activeTab = 'pendanaan'; 

    // Variabel Form Edit Investor
    public $isEditModalOpen = false;
    public $edit_nama_lengkap, $edit_perusahaan, $edit_no_hp, $edit_alamat, $edit_info_bank, $edit_status_kemitraan;
    public $edit_email;

    public function mount($id)
    {
        $this->user = User::with('investorProfile')->findOrFail($id);
    }

    // Mengambil riwayat uang masuk dari Investor ke LKBB (Deposit)
    public function getRiwayatDepositProperty()
    {
        return Transaction::where('user_id', $this->user->id)
            ->whereIn('type', ['deposit', 'investasi'])
            ->latest()
            ->get();
    }

    // Mengambil riwayat uang keluar dari LKBB ke Investor (Bagi Hasil)
    public function getRiwayatProfitProperty()
    {
        return Transaction::where('user_id', $this->user->id)
            ->whereIn('type', ['bagi_hasil', 'profit_share', 'dividend'])
            ->latest()
            ->get();
    }

    // =====================================
    // FUNGSI EDIT DATA INVESTOR
    // =====================================
    public function openEditModal()
    {
        if ($this->user->investorProfile) {
            $this->edit_nama_lengkap = $this->user->investorProfile->nama_lengkap;
            $this->edit_perusahaan = $this->user->investorProfile->perusahaan;
            $this->edit_no_hp = $this->user->investorProfile->no_hp;
            $this->edit_alamat = $this->user->investorProfile->alamat;
            $this->edit_info_bank = $this->user->investorProfile->info_bank;
            $this->edit_status_kemitraan = $this->user->investorProfile->status_kemitraan;
            
            $this->edit_email = $this->user->email;
            
            $this->isEditModalOpen = true;
        }
    }

    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    public function updateInvestor()
    {
        $this->validate([
            'edit_nama_lengkap' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $this->user->id,
            'edit_status_kemitraan' => 'required|in:aktif,nonaktif',
        ]);

        $this->user->update([
            'name' => $this->edit_nama_lengkap, 
            'email' => $this->edit_email,
        ]);

        if ($this->user->investorProfile) {
            $this->user->investorProfile->update([
                'nama_lengkap' => $this->edit_nama_lengkap,
                'perusahaan' => $this->edit_perusahaan,
                'no_hp' => $this->edit_no_hp,
                'alamat' => $this->edit_alamat,
                'info_bank' => $this->edit_info_bank,
                'status_kemitraan' => $this->edit_status_kemitraan,
            ]);
        }

        $this->user->refresh();
        $this->closeEditModal();
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.investor.index') }}" class="hover:text-teal-600 transition">Data Investor</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Detail Portofolio Investor</span>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-16 h-16 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-2xl font-bold shadow-inner border border-teal-200 flex-shrink-0">
                {{ strtoupper(substr($user->investorProfile->nama_lengkap ?? $user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $user->investorProfile->nama_lengkap ?? 'Nama Investor' }}</h2>
                    @if(($user->investorProfile->status_kemitraan ?? 'nonaktif') == 'aktif')
                        <span class="bg-teal-100 text-teal-700 text-[10px] px-2.5 py-1 rounded-md font-bold border border-teal-200 uppercase tracking-wide">Investor Aktif</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-md font-bold border border-gray-200 uppercase tracking-wide">Nonaktif</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">Institusi: <span class="font-medium text-gray-700">{{ $user->investorProfile->perusahaan ?: 'Individu / Pribadi' }}</span></p>
            </div>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <a href="{{ route('admin.investor.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 font-medium text-sm transition text-center w-full md:w-auto flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
            <button wire:click="openEditModal" class="px-5 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 font-medium text-sm shadow-sm transition flex items-center justify-center w-full md:w-auto gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                Edit Data
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
        
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col h-full w-full relative overflow-hidden">
            <div class="flex items-center gap-2 mb-6 text-teal-700 font-bold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                Informasi Pemodal
            </div>
            
            <div class="space-y-4 flex-1">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Email Akses</p>
                        <p class="text-gray-900 font-medium text-sm truncate">{{ $user->email ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">No Handphone</p>
                        <p class="text-gray-900 font-medium text-sm">{{ $user->investorProfile->no_hp ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Alamat</p>
                        <p class="text-gray-900 font-medium text-sm leading-relaxed">{{ $user->investorProfile->alamat ?: '-' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3 p-3 bg-teal-50 border border-teal-100 rounded-xl mt-4">
                    <svg class="w-5 h-5 text-teal-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <div>
                        <p class="text-[10px] text-teal-600 font-bold uppercase tracking-wider mb-0.5">Rekening Tujuan Bagi Hasil</p>
                        <p class="text-teal-900 font-bold text-sm">{{ $user->investorProfile->info_bank ?: 'Belum diisi' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-teal-500 to-teal-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full justify-center">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-4">
                    <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-teal-50 text-[10px] font-bold tracking-wider">PORTOFOLIO</span>
                </div>
                
                <div>
                    <p class="text-teal-100 text-xs font-bold tracking-wider mb-1">TOTAL DEPOSIT (MODAL AKTIF)</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->investorProfile->total_investasi_aktif ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] text-teal-100 mt-3 opacity-90 leading-relaxed">Total dana segar dari investor yang sedang dipercayakan ke LKBB untuk diputar.</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col h-full w-full justify-center">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-4">
                    <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <span class="text-emerald-50 text-[10px] font-bold tracking-wider">RETURN ON INVESTMENT</span>
                </div>
                
                <div>
                    <p class="text-emerald-100 text-xs font-bold tracking-wider mb-1">TOTAL PROFIT (BAGI HASIL)</p>
                    <h3 class="text-3xl font-extrabold tracking-tight drop-shadow-md truncate">
                        <span class="text-xl align-top mr-1 opacity-80">Rp</span>{{ number_format($user->investorProfile->total_bagi_hasil ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-[11px] text-emerald-100 mt-3 opacity-90 leading-relaxed">Akumulasi keuntungan historis yang sudah berhasil ditransfer LKBB ke rekening investor.</p>
                </div>
            </div>
        </div>
        
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mt-4">
        
        <div class="flex border-b border-gray-100 px-6 gap-6 overflow-x-auto">
            <button wire:click="$set('activeTab', 'pendanaan')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'pendanaan' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' }}">Riwayat Deposit & Penambahan Modal</button>
            <button wire:click="$set('activeTab', 'bagi_hasil')" class="py-4 font-bold text-sm whitespace-nowrap transition-colors {{ $activeTab == 'bagi_hasil' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700' }}">Riwayat Pembayaran Profit</button>
        </div>
        
        <div class="p-0 overflow-x-auto">
            
            @if($activeTab == 'pendanaan')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Transaksi</th>
                        <th class="px-6 py-4">Waktu Deposit</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-right">Nominal Masuk</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->riwayatDeposit as $trx)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-500">{{ $trx->order_id ?? 'TRX-'.$trx->id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 font-bold text-sm text-gray-900">{{ $trx->description ?: 'Deposit Modal LKBB' }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-teal-600">+ Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if(in_array($trx->status, ['sukses', 'lunas', 'berhasil']))
                                <span class="bg-teal-50 text-teal-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-teal-100">Sukses</span>
                            @else
                                <span class="bg-yellow-50 text-yellow-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-yellow-100">{{ $trx->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat deposit atau penambahan modal.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif

            @if($activeTab == 'bagi_hasil')
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ID Transfer</th>
                        <th class="px-6 py-4">Tanggal Pembayaran</th>
                        <th class="px-6 py-4">Keterangan Transfer</th>
                        <th class="px-6 py-4 text-right">Nominal Profit</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->riwayatProfit as $trx)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-xs text-gray-500">{{ $trx->order_id ?? 'TRX-'.$trx->id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 font-medium text-sm text-gray-900">{{ $trx->description ?: 'Bagi Hasil Bulanan' }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-green-600">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if(in_array($trx->status, ['sukses', 'lunas', 'berhasil']))
                                <span class="bg-green-50 text-green-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-green-200">Terbayar</span>
                            @else
                                <span class="bg-yellow-50 text-yellow-600 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider border border-yellow-100">{{ $trx->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat transfer profit ke investor ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @endif
            
        </div>
    </div>

    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Profil Investor
                </h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap (Sesuai KTP)</label>
                        <input wire:model="edit_nama_lengkap" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Perusahaan / Institusi</label>
                        <input wire:model="edit_perusahaan" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA</label>
                        <input wire:model="edit_no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email Akses Login</label>
                        <input wire:model="edit_email" type="email" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Lengkap</label>
                    <textarea wire:model="edit_alamat" rows="2" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-teal-50 border border-teal-100 rounded-xl">
                        <label class="block text-[10px] font-bold text-teal-600 uppercase tracking-wider mb-1.5">Info Rekening (Tujuan Bagi Hasil)</label>
                        <input wire:model="edit_info_bank" type="text" placeholder="Cth: Mandiri 12345 a.n Budi" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status Kemitraan</label>
                        <select wire:model="edit_status_kemitraan" class="w-full text-sm rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 bg-white py-2.5 cursor-pointer mt-3">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Non-aktif (Tarik Dana)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100">Batal</button>
                <button wire:click="updateInvestor" class="px-5 py-2 text-sm font-medium text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-colors shadow-sm focus:ring-4 focus:ring-teal-100">Simpan Perubahan</button>
            </div>
        </div>
    </div>
    @endif

</div>