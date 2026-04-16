<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use App\Models\SetoranTunai;
use App\Models\MerchantProfile;

new 
#[Layout('layouts.lkbb')] 
class extends Component {
    
    public string $nama_petugas = '';
    public ?int $selectedSetoranId = null;
    public bool $isModalOpen = false;

    #[Computed]
    public function tiketPending()
    {
        return SetoranTunai::with('merchant')
                ->where('status', 'menunggu_penjemputan') 
                ->latest()
                ->get();
    }

    #[Computed]
    public function riwayatHariIni()
    {
        return SetoranTunai::with('merchant')
                ->where('status', 'selesai')
                ->whereDate('updated_at', today())
                ->latest()
                ->get();
    }

    public function openAccModal($id)
    {
        $this->selectedSetoranId = $id;
        $this->nama_petugas = ''; 
        $this->isModalOpen = true;
    }

    public function konfirmasiTerimaUang()
    {
        $this->validate([
            'nama_petugas' => 'required|min:3'
        ], [
            'nama_petugas.required' => 'Nama petugas wajib diisi untuk catatan audit.',
            'nama_petugas.min' => 'Nama petugas terlalu singkat.'
        ]);

        $setId = $this->selectedSetoranId;
        $petugas = $this->nama_petugas;

        try {
            DB::transaction(function () use ($setId, $petugas) {
                
                $setoran = SetoranTunai::where('id', $setId)
                                ->lockForUpdate()
                                ->firstOrFail();

                $merchant = MerchantProfile::where('user_id', $setoran->merchant_id)
                                ->lockForUpdate()
                                ->firstOrFail();

                $setoran->update([
                    'status' => 'selesai',
                    'nama_petugas' => $petugas
                ]);

                $hutangLama = (float) $merchant->tagihan_setoran_tunai;
                $nominalSetor = (float) $setoran->nominal;
                
                $hutangBaru = max(0, $hutangLama - $nominalSetor);
                $merchant->update(['tagihan_setoran_tunai' => $hutangBaru]);
            });

            $this->isModalOpen = false;
            $this->selectedSetoranId = null;
            $this->nama_petugas = '';
            
            unset($this->tiketPending); 
            unset($this->riwayatHariIni); 

            session()->flash('success', 'Uang tunai berhasil diterima dan hutang kantin telah lunas!');

        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }
}; ?>

{{-- PERBAIKAN: Menghapus max-w-7xl mx-auto agar desain melebar penuh (Fluid) --}}
<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Penagihan Tunai (Pickup)</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola penjemputan uang fisik fee LKBB dari kantin-kantin (Merchant).</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-4 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <span class="font-bold">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-4">
            <span class="font-bold">{{ session('error') }}</span>
        </div>
    @endif

    {{-- PERBAIKAN GRID: Dibuat lebih lebar dan menyesuaikan layar besar (xl:gap-8) --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 xl:gap-8">
        
        {{-- KOLOM KIRI (LEBIH LEBAR UNTUK KARTU) --}}
        <div class="xl:col-span-2 space-y-4">
            <h3 class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Menunggu Penjemputan Uang
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                @forelse($this->tiketPending as $tiket)
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden flex flex-col justify-between">
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-400"></div>
                        
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-mono text-xs font-extrabold text-gray-900">{{ $tiket->nomor_setoran }}</span>
                                <span class="bg-amber-50 text-amber-700 text-[10px] px-2.5 py-1 rounded-md font-bold uppercase">Pickup</span>
                            </div>
                            
                            <h4 class="font-bold text-gray-800 text-base truncate">{{ $tiket->merchant->name ?? 'Kantin ID: '.$tiket->merchant_id }}</h4>
                            <p class="text-xs text-gray-400 mb-5">{{ $tiket->created_at->format('d M Y, H:i') }}</p>

                            <div class="bg-gray-50 rounded-xl p-4 flex justify-between items-center mb-5 border border-gray-100">
                                <span class="text-xs font-bold text-gray-500 uppercase">Tagihan Fisik:</span>
                                <span class="text-xl font-black text-blue-600">Rp {{ number_format($tiket->nominal, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <button wire:click="openAccModal({{ $tiket->id }})" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-all shadow-lg shadow-blue-200 flex justify-center items-center gap-2 mt-auto focus:ring-4 focus:ring-blue-100">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            ACC Uang Diterima
                        </button>
                    </div>
                @empty
                    <div class="col-span-full bg-white border border-gray-200 rounded-2xl p-10 text-center shadow-sm">
                        <div class="text-5xl mb-4 opacity-40">🛵</div>
                        <p class="text-base font-bold text-gray-600">Semua penjemputan uang sudah beres!</p>
                        <p class="text-xs text-gray-400 mt-1">Belum ada tugas penagihan tunai untuk saat ini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- KOLOM KANAN (SEKARANG PUNYA RUANG LEBIH LUAS) --}}
        <div class="xl:col-span-1">
            <h3 class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-4">Setoran Masuk Hari Ini</h3>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden h-full flex flex-col">
                <ul class="divide-y divide-gray-100 flex-1">
                    @forelse($this->riwayatHariIni as $riwayat)
                        <li class="p-5 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="font-bold text-sm text-gray-900 truncate pr-2">{{ $riwayat->merchant->name ?? 'Kantin' }}</span>
                                <span class="text-xs font-black text-emerald-600 whitespace-nowrap">Rp {{ number_format($riwayat->nominal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-500 mt-2">
                                <span>Kolektor: <strong class="text-gray-700">{{ $riwayat->nama_petugas }}</strong></span>
                                <span>{{ $riwayat->updated_at->format('H:i') }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-gray-500 font-medium">Belum ada uang fisik yang diterima hari ini.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" x-data x-transition>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-7 relative">
                <div class="flex justify-center mb-5">
                    <div class="p-3.5 bg-blue-50 text-blue-600 rounded-full">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-center text-gray-900 mb-1">Konfirmasi Setoran</h3>
                <p class="text-sm text-center text-gray-500 mb-6 leading-relaxed">Siapa nama petugas LKBB yang membawa uang fisik ini ke kantor?</p>
                
                <form wire:submit="konfirmasiTerimaUang">
                    <div class="mb-6">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase mb-2">Nama Kolektor / Petugas</label>
                        <input type="text" wire:model="nama_petugas" placeholder="Contoh: Pak Budi" class="w-full border border-gray-300 rounded-xl p-3.5 text-sm focus:ring-blue-500 focus:border-blue-500 font-medium transition-colors">
                        @error('nama_petugas') <span class="text-xs text-rose-500 font-bold block mt-1.5">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="flex-1 py-3 text-sm font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 py-3 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 disabled:opacity-50 focus:ring-4 focus:ring-blue-100">
                            <span wire:loading.remove wire:target="konfirmasiTerimaUang">Simpan Data</span>
                            <span wire:loading wire:target="konfirmasiTerimaUang">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>