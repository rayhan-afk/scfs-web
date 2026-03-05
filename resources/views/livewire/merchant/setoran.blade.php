<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\MerchantProfile;
use App\Models\SetoranTunai;
use App\Models\Transaction;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    public function mount()
    {
        // 🛠️ AUTO-HEALING SCRIPT: Sinkronisasi Ulang Data Akuntansi
        // Sistem menghitung ulang HANYA transaksi yang benar-benar TUNAI
        $totalFeeTunai = Transaction::where('merchant_id', Auth::id())
            ->where('type', 'pembayaran_makanan_tunai') // STRICT: Hanya Tunai
            ->whereIn('status', ['sukses', 'lunas'])
            ->sum('fee_lkbb');

        // Kurangi dengan yang sudah pernah disetorkan / sedang dijemput
        $totalSudahDisetor = SetoranTunai::where('merchant_id', Auth::id())
            ->whereIn('status', ['menunggu_penjemputan', 'selesai'])
            ->sum('nominal');

        $hutangFisikReal = max(0, $totalFeeTunai - $totalSudahDisetor);

        // Update database merchant secara diam-diam agar datanya akurat kembali
        $profile = MerchantProfile::where('user_id', Auth::id())->first();
        if ($profile && $profile->tagihan_setoran_tunai != $hutangFisikReal) {
            $profile->update(['tagihan_setoran_tunai' => $hutangFisikReal]);
        }
    }

    #[Computed]
    public function profile()
    {
        return MerchantProfile::where('user_id', Auth::id())->firstOrFail();
    }

    #[Computed]
    public function riwayatSetoran()
    {
        return SetoranTunai::where('merchant_id', Auth::id())
                ->latest()
                ->get();
    }

    #[Computed]
    public function adaPenjemputanAktif()
    {
        return SetoranTunai::where('merchant_id', Auth::id())
                ->where('status', 'menunggu_penjemputan')
                ->exists();
    }

    /**
     * CORE ACTION: Memanggil Petugas LKBB untuk mengambil uang fisik
     */
    public function panggilPetugas()
    {
        try {
            DB::transaction(function () {
                // 1. Lock Row (Anti Race Condition)
                $merchant = MerchantProfile::where('user_id', Auth::id())
                                ->lockForUpdate()
                                ->firstOrFail();

                // 2. Validasi Bisnis (Logical Constraints)
                if ($merchant->tagihan_setoran_tunai <= 0) {
                    throw new \Exception('Anda tidak memiliki tagihan setoran tunai saat ini.');
                }

                // 3. Validasi Idempotency (Anti-Spam Request)
                $cekAktif = SetoranTunai::where('merchant_id', $merchant->user_id)
                                ->where('status', 'menunggu_penjemputan')
                                ->lockForUpdate()
                                ->exists();

                if ($cekAktif) {
                    throw new \Exception('Petugas sedang dalam perjalanan. Mohon tunggu proses penjemputan sebelumnya selesai.');
                }

                // 4. Buat Tiket Penjemputan Uang Fisik
                SetoranTunai::create([
                    'nomor_setoran' => 'ST-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                    'merchant_id'   => $merchant->user_id,
                    'nominal'       => $merchant->tagihan_setoran_tunai,
                    'status'        => 'menunggu_penjemputan'
                ]);

            });

            unset($this->adaPenjemputanAktif); // Refresh computed property
            session()->flash('success', 'Permintaan penjemputan uang berhasil dikirim. Siapkan uang tunai Anda.');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

{{-- PERBAIKAN UI: Hapus max-w-6xl dan mx-auto agar Fluid mepet sidebar --}}
<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Tagihan Setoran Tunai</h2>
        <p class="text-gray-500 text-sm mt-1">Buku catatan uang fisik di laci Anda yang menjadi hak/profit LKBB.</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6 animate-pulse">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3.5 rounded-xl flex items-center gap-3 shadow-sm mb-6">
            <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        
        {{-- COLUMN 1: KARTU HUTANG FISIK --}}
        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                
                <div class="relative z-10 flex items-center gap-3 mb-4">
                    <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-rose-100 text-[10px] font-bold tracking-wider uppercase">Uang Fisik Wajib Setor</p>
                    </div>
                </div>

                <div class="relative z-10">
                    <h3 class="text-4xl font-extrabold tracking-tight truncate">Rp {{ number_format($this->profile->tagihan_setoran_tunai, 0, ',', '.') }}</h3>
                </div>

                <div class="relative z-10 mt-5 pt-5 border-t border-rose-400/30">
                    <p class="text-[10px] text-rose-100 leading-relaxed">
                        Ini adalah akumulasi persentase bagi hasil ({{ $this->profile->persentase_bagi_hasil }}%) milik LKBB dari pembeli yang membayar Anda menggunakan <strong>Uang Tunai</strong>.
                    </p>
                </div>
            </div>

            {{-- Action Box --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm relative">
                <h3 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider mb-2">Penyerahan Uang</h3>
                <p class="text-[11px] text-gray-500 mb-5 leading-relaxed">Jika uang kas sudah Anda siapkan di laci, klik tombol di bawah untuk memanggil Petugas LKBB.</p>

                @if($this->adaPenjemputanAktif)
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
                        <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-xs font-bold text-amber-800">Petugas Sedang Menuju Lokasi</p>
                        <p class="text-[10px] text-amber-600 mt-1">Siapkan uang pas sejumlah tagihan Anda.</p>
                    </div>
                @else
                    <button wire:click="panggilPetugas" wire:loading.attr="disabled"
                        @if($this->profile->tagihan_setoran_tunai <= 0) disabled @endif
                        class="w-full py-3.5 text-sm font-extrabold text-white bg-gray-900 rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex justify-center items-center gap-2 focus:ring-4 focus:ring-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="panggilPetugas">Panggil Petugas LKBB</span>
                        <span wire:loading wire:target="panggilPetugas">Memproses...</span>
                    </button>
                @endif
            </div>

        </div>

        {{-- COLUMN 2: RIWAYAT SETORAN TUNAI --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 text-sm">Riwayat Serah Terima Kas</h3>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse min-w-max">
                    <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">ID Tiket / Waktu</th>
                            <th class="px-6 py-4 text-right">Nominal Setoran Fisik</th>
                            <th class="px-6 py-4">Status & Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($this->riwayatSetoran as $setoran)
                            <tr class="hover:bg-gray-50 transition group">
                                <td class="px-6 py-4">
                                    <div class="text-[11px] font-bold text-gray-900 font-mono">{{ $setoran->nomor_setoran }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $setoran->created_at->format('d M Y, H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm font-extrabold text-gray-900">Rp {{ number_format($setoran->nominal, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($setoran->status == 'menunggu_penjemputan')
                                        <span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Menunggu Penjemputan
                                        </span>
                                        <p class="text-[9px] text-gray-400 mt-1">-</p>
                                    @elseif($setoran->status == 'selesai')
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">✅ Lunas / Diterima</span>
                                        <p class="text-[9px] text-gray-500 mt-1 font-medium">Petugas: <span class="font-bold text-gray-800">{{ $setoran->nama_petugas ?? 'Admin' }}</span></p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-20 text-center text-gray-400">
                                    <div class="text-4xl mb-4 opacity-40">🤝</div>
                                    <p class="text-sm font-bold text-gray-600">Belum Ada Riwayat Setoran</p>
                                    <p class="text-xs mt-1">Anda belum pernah menyerahkan uang tunai ke petugas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>