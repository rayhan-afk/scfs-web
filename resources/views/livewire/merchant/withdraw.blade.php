<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\MerchantProfile;
use App\Models\Withdrawal;

new 
#[Layout('layouts.app')] 
class extends Component {
    
    // State UI
    public string $nominal_tarik = '';

    /**
     * Data Profil Merchant diambil secara reaktif
     */
    #[Computed]
    public function profile()
    {
        return MerchantProfile::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * Hitung maksimal dana bersih yang BOLEH ditarik (Server-Side Calculation)
     */
    #[Computed]
    public function maksimalTarik()
    {
        return max(0, $this->profile->saldo_token - $this->profile->tagihan_setoran_tunai);
    }

    /**
     * Riwayat Pencairan
     */
    #[Computed]
    public function riwayatPencairan()
    {
        return Withdrawal::where('merchant_id', Auth::id())->latest()->get();
    }

    /**
     * Helper action untuk mengisi nilai maksimal ke form
     */
    public function setTarikSemua()
    {
        if ($this->maksimalTarik > 0) {
            $this->nominal_tarik = (string) $this->maksimalTarik;
        }
    }

    /**
     * Core Action: Proses Eksekusi Pencairan (High Security Area)
     */
    public function ajukanPencairan()
    {
        // 1. Validasi Input Dasar
        $this->validate([
            'nominal_tarik' => [
                'required',
                'numeric',
                'min:10000', // Batas minimum transfer bank biasanya Rp 10.000
                'max:' . $this->maksimalTarik
            ]
        ], [
            'nominal_tarik.max' => 'Nominal melebihi batas saldo bersih Anda.',
            'nominal_tarik.min' => 'Minimal penarikan adalah Rp 10.000.'
        ]);

        $nominalBersih = (float) $this->nominal_tarik;

        try {
            // 2. ACID Transaction (Atomicity, Consistency, Isolation, Durability)
            DB::transaction(function () use ($nominalBersih) {
                
                // PESSIMISTIC LOCK: Cegah Double Spending / Race Condition
                $merchant = MerchantProfile::where('user_id', Auth::id())
                                ->lockForUpdate()
                                ->firstOrFail();

                // Validasi ulang nilai di dalam State yang terkunci (Anti-Tampering)
                $saldoSaatIni = $merchant->saldo_token;
                $hutangSaatIni = $merchant->tagihan_setoran_tunai;
                $batasMaksimal = $saldoSaatIni - $hutangSaatIni;

                if ($nominalBersih > $batasMaksimal) {
                    throw new \Exception('Terjadi perubahan saldo. Transaksi dibatalkan untuk keamanan.');
                }

                // Cek profil Info Pencairan
                if (empty($merchant->info_pencairan)) {
                    throw new \Exception('Nomor Rekening/E-Wallet belum diatur. Silakan perbarui di Pengaturan Profil.');
                }

                // Cek Idempotency (Apakah ada antrean aktif?)
                $adaPending = Withdrawal::where('merchant_id', $merchant->user_id)
                                ->where('status', 'pending')
                                ->lockForUpdate() // Kunci juga tabel withdrawal
                                ->exists();

                if ($adaPending) {
                    throw new \Exception('Anda masih memiliki pengajuan yang sedang diproses oleh LKBB.');
                }

                // 3. Kalkulasi Settlement Akhir
                $kotorDipotong = $nominalBersih + $hutangSaatIni;

                // 4. Audit Trail (Catat di Ledger Withdrawal)
                Withdrawal::create([
                    'nomor_pencairan' => 'WD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                    'merchant_id'     => $merchant->user_id,
                    'nominal_kotor'   => $kotorDipotong, 
                    'potongan_lkbb'   => $hutangSaatIni,
                    'nominal_bersih'  => $nominalBersih,
                    'info_pencairan'  => $merchant->info_pencairan,
                    'status'          => 'pending',
                ]);

                // 5. Escrow Deduction (Potong uang Merchant)
                $merchant->decrement('saldo_token', $kotorDipotong);
                $merchant->update(['tagihan_setoran_tunai' => 0]); // Lunas 100%
            });

            // 6. Reset Form & UI Feedback
            $this->reset('nominal_tarik');
            unset($this->profile); // Paksa refresh computed property
            session()->flash('success', 'Pengajuan dana Rp ' . number_format($nominalBersih, 0, ',', '.') . ' berhasil dikirim ke antrean LKBB.');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

{{-- PERUBAHAN DI SINI: max-w-6xl dan mx-auto DIHAPUS --}}
<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Pencairan Dana</h2>
        <p class="text-gray-500 text-sm mt-1">Cairkan pendapatan toko Anda secara aman ke Rekening Bank atau E-Wallet.</p>
    </div>

    {{-- Flash Messages (Menggunakan komponen alert yang konsisten) --}}
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
        
        {{-- COLUMN 1: FORM PENARIKAN --}}
        <div class="lg:col-span-1 space-y-6">
            
            {{-- Kartu Info Saldo --}}
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                
                <div class="relative z-10">
                    <p class="text-emerald-100 text-[10px] font-bold tracking-wider mb-1 uppercase">Saldo Bisa Ditarik (Bersih)</p>
                    <h3 class="text-3xl font-extrabold tracking-tight truncate">Rp {{ number_format($this->maksimalTarik, 0, ',', '.') }}</h3>
                </div>

                <div class="relative z-10 flex justify-between items-center mt-6 pt-4 border-t border-emerald-400/30">
                    <div>
                        <p class="text-[9px] text-emerald-200 font-bold uppercase mb-0.5">Saldo Kotor</p>
                        <p class="text-xs font-bold truncate">Rp {{ number_format($this->profile->saldo_token, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] text-emerald-200 font-bold uppercase mb-0.5">Hutang Fee LKBB</p>
                        <p class="text-xs font-bold text-rose-200 truncate">-Rp {{ number_format($this->profile->tagihan_setoran_tunai, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Form Input --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm relative">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-5 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Ajukan Penarikan Baru
                </h3>

                <form wire:submit.prevent="ajukanPencairan" class="space-y-5">
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nominal Tarik Bersih</label>
                            <button type="button" wire:click="setTarikSemua" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md hover:bg-emerald-100 transition-colors focus:outline-none">
                                Tarik Maksimal
                            </button>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 font-bold">Rp</span>
                            <input wire:model="nominal_tarik" type="number" step="1000" placeholder="0" 
                                class="w-full py-3.5 pl-12 pr-4 text-lg font-bold text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 transition">
                        </div>
                        @error('nominal_tarik') <span class="text-rose-500 text-[10px] mt-1.5 font-bold block">{{ $message }}</span> @enderror
                        
                        <p class="text-[10px] text-gray-400 mt-2 italic leading-relaxed">*Saat penarikan, sistem akan otomatis memotong sisa saldo untuk melunasi tagihan Fee LKBB Anda.</p>
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                        <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Transfer Ke:</span>
                        @if($this->profile->info_pencairan)
                            <span class="text-xs font-extrabold text-gray-800">{{ $this->profile->info_pencairan }}</span>
                        @else
                            <span class="text-xs font-bold text-rose-500">Belum diatur. Buka menu Pengaturan Profil.</span>
                        @endif
                    </div>

                    <button type="submit"
                        wire:loading.attr="disabled"
                        @if($this->maksimalTarik < 10000 || empty($this->profile->info_pencairan)) disabled @endif
                        class="w-full py-3.5 text-sm font-extrabold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-200 flex justify-center items-center gap-2 focus:ring-4 focus:ring-emerald-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="ajukanPencairan">Kirim Pengajuan</span>
                        <span wire:loading wire:target="ajukanPencairan">Mengunci Saldo...</span>
                    </button>
                </form>
            </div>

        </div>

        {{-- COLUMN 2: TABEL RIWAYAT --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 text-sm">Riwayat & Status Settlement</h3>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse min-w-max">
                    <thead class="bg-white text-gray-400 text-[10px] uppercase font-bold tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">ID / Waktu</th>
                            <th class="px-6 py-4 text-right">Penarikan Bersih</th>
                            <th class="px-6 py-4 text-right">Potongan LKBB</th>
                            <th class="px-6 py-4 text-center">Status Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($this->riwayatPencairan as $wd)
                            <tr class="hover:bg-gray-50 transition group">
                                <td class="px-6 py-4">
                                    <div class="text-[11px] font-bold text-gray-900 font-mono">{{ $wd->nomor_pencairan }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $wd->created_at->format('d M Y, H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm font-extrabold text-emerald-600">Rp {{ number_format($wd->nominal_bersih, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-xs font-bold text-rose-500">Rp {{ number_format($wd->potongan_lkbb, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($wd->status == 'pending')
                                        <span class="bg-amber-50 text-amber-600 border border-amber-200 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Diproses LKBB
                                        </span>
                                    @elseif($wd->status == 'disetujui')
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider">Berhasil Transfer</span>
                                    @else
                                        <span class="bg-rose-50 text-rose-700 border border-rose-200 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase tracking-wider cursor-help" title="{{ $wd->catatan_lkbb }}">Ditolak</span>
                                        <p class="text-[9px] text-rose-400 mt-1 truncate max-w-[120px] mx-auto">{{ $wd->catatan_lkbb }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center text-gray-400">
                                    <div class="text-4xl mb-4 opacity-40">🏦</div>
                                    <p class="text-sm font-bold text-gray-600">Belum Ada Riwayat Penarikan</p>
                                    <p class="text-xs mt-1">Lakukan penarikan dana jika saldo Anda sudah mencukupi.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>