<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\PengajuanBantuan;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

new 
#[Layout('layouts.lkbb')] 
class extends Component {
    
    public function getPengajuansProperty()
    {
        return PengajuanBantuan::with(['mahasiswaProfile.user'])
            ->where('status', 'diajukan')
            ->latest()
            ->get();
    }

    public function setujui($id)
    {
        $pengajuan = PengajuanBantuan::with('mahasiswaProfile.user')->find($id);
        if (!$pengajuan) return;

        DB::transaction(function () use ($pengajuan) {
            // 1. Ubah status pengajuan & profil jadi disetujui
            $pengajuan->update(['status' => 'disetujui']);
            $pengajuan->mahasiswaProfile->update(['status_bantuan' => 'disetujui']);
            
            // 2. Buat Dompet Mahasiswa jika belum ada, lalu tambah saldonya
            $studentWallet = Wallet::firstOrCreate(
                ['user_id' => $pengajuan->mahasiswaProfile->user_id],
                [
                    'account_number' => 'MHS-' . str_pad($pengajuan->mahasiswaProfile->user_id, 4, '0', STR_PAD_LEFT),
                    'balance' => 0,
                    'type' => 'REGULAR'
                ]
            );
            $studentWallet->increment('balance', $pengajuan->nominal);

            // 3. Potong saldo dari Dompet Donasi LKBB
            $donationWallet = Wallet::where('type', 'DONATION_POOL')->first();
            if ($donationWallet) {
                $donationWallet->decrement('balance', $pengajuan->nominal);
            }

            // 4. Catat histori di tabel Transactions (Sesuai Skema Dashboard LKBB)
            Transaction::create([
                'order_id' => $pengajuan->nomor_pengajuan,
                'user_id' => $pengajuan->mahasiswaProfile->user_id,
                'type' => 'donation_received',
                'status' => 'lunas',
                'total_amount' => $pengajuan->nominal,
                'description' => 'Pencairan Dana Bantuan dari LKBB',
            ]);

            // 5. Update cache saldo di tabel profil mahasiswa
            $pengajuan->mahasiswaProfile->increment('saldo', $pengajuan->nominal);
        });
    }

    public function tolak($id)
    {
        $pengajuan = PengajuanBantuan::find($id);
        if ($pengajuan) {
            $pengajuan->update(['status' => 'ditolak']);
            $pengajuan->mahasiswaProfile->update(['status_bantuan' => 'ditolak']);
        }
    }
}; ?>

<div class="p-6">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
        <a href="{{ route('lkbb.dashboard') }}" class="hover:text-blue-600 transition">Dashboard</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="font-medium text-gray-900">Verifikasi Bantuan Mahasiswa</span>
    </div>

    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Antrean Verifikasi Bantuan</h2>
            <p class="text-gray-500 text-sm mt-1">Setujui pencairan dana sebesar Rp 500.000/mahasiswa dari dompet donatur LKBB.</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-gray-900 text-sm">Menunggu Persetujuan</h3>
            <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-100">{{ $this->pengajuans->count() }} Pengajuan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">No. Pengajuan</th>
                        <th class="px-6 py-4">Mahasiswa Penerima</th>
                        <th class="px-6 py-4 text-center">Data Akademik</th>
                        <th class="px-6 py-4 text-right">Nominal Bantuan</th>
                        <th class="px-6 py-4 text-right">Aksi Eksekusi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->pengajuans as $pengajuan)
                    <tr class="hover:bg-gray-50/80 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 text-xs font-mono">{{ $pengajuan->nomor_pengajuan }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">{{ $pengajuan->created_at->format('d M Y, H:i') }}</div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-purple-100 text-purple-700">
                                    {{ strtoupper(substr($pengajuan->mahasiswaProfile->user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $pengajuan->mahasiswaProfile->user->name }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono">{{ $pengajuan->mahasiswaProfile->nim ?? '-' }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="text-[10px] font-bold text-gray-600 uppercase">{{ $pengajuan->mahasiswaProfile->jurusan ?? '-' }}</div>
                            <div class="text-[10px] text-gray-500 mt-1">IPK: <span class="font-bold text-gray-800">{{ $pengajuan->mahasiswaProfile->ipk ?? '-' }}</span></div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-blue-600">Rp {{ number_format($pengajuan->nominal, 0, ',', '.') }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">Saldo Wallet</div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="tolak({{ $pengajuan->id }})" wire:confirm="Yakin ingin menolak pengajuan ini?" class="px-3 py-1.5 text-[10px] font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition uppercase">
                                    Tolak
                                </button>
                                <button wire:click="setujui({{ $pengajuan->id }})" class="flex items-center px-4 py-1.5 text-[10px] font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-lg shadow-sm transition uppercase">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Cairkan Dana
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                            <div class="text-4xl mb-3">📬</div>
                            <p class="font-medium text-sm">Hore! Antrean kosong.</p>
                            <p class="text-xs text-gray-400 mt-1">Belum ada pengajuan bantuan baru dari Admin.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>