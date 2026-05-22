<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\SupplierProfile;
use App\Models\SupplyOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\NewSupplierSubmission;
use Carbon\Carbon;

new 
#[Layout('layouts.app')] 
class extends Component {
    use WithFileUploads;

    public $nama_perusahaan, $nama_pemilik, $nik, $no_hp, $alamat_gudang;

    public $nama_bank = 'BCA';
    public $bank_lainnya = '';
    public $no_rekening = '';
    public $foto_ktp, $foto_gudang;

    public string $chartFilter = 'month'; 

    public function mount()
    {
        $profile = SupplierProfile::where('user_id', Auth::id())->first();
        
        if ($profile) {
            $this->nama_perusahaan = $profile->nama_perusahaan;
            $this->nama_pemilik = $profile->nama_pemilik;
            $this->nik = $profile->nik;
            $this->no_hp = $profile->no_hp;
            $this->alamat_gudang = $profile->alamat_gudang;
            $standardBanks = ['BCA', 'BNI', 'BRI', 'Mandiri', 'BJB', 'GoPay', 'OVO'];
                if (in_array($profile->nama_bank, $standardBanks)) {
                    $this->nama_bank = $profile->nama_bank;
                } elseif (!empty($profile->nama_bank)) {
                    $this->nama_bank = 'Lainnya';
                    $this->bank_lainnya = $profile->nama_bank;
                }

                $this->no_rekening = $profile->no_rekening;
        } else {
            $this->nama_pemilik = Auth::user()->name;
        }
    }

    #[Computed]
    public function profile()
    {
        return SupplierProfile::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'status_verifikasi'       => 'belum_melengkapi',
                'nama_pemilik'            => Auth::user()->name,
                'saldo_pendapatan'        => 0, 
                'status_operasional'      => 'buka',
            ]
        );
    }

    #[Computed]
    public function statHariIni()
    {
        $today = Carbon::today();
        
        $baseQuery = SupplyOrder::where('pemasok_id', Auth::id())
            ->whereIn('status', ['selesai', 'dikirim'])
            ->whereDate('updated_at', $today);

        return [
            'total_nominal' => $baseQuery->sum('total_estimasi'), // FIX: was total_amount
            'total_pesanan' => $baseQuery->count(),
        ];
    }

    #[Computed]
    public function pesananBaru()
    {
        return SupplyOrder::where('pemasok_id', Auth::id())
                ->whereIn('status', ['menunggu_pemasok', 'diproses_pemasok'])
                ->count();
    }

    public function getChartData()
    {
        $query = SupplyOrder::where('pemasok_id', Auth::id())
            ->whereIn('status', ['selesai', 'dikirim']); // Sesuaikan dengan enum yang ada

        $labels = [];
        $series = [];

        if ($this->chartFilter === 'today') {
            $txs = (clone $query)->whereDate('updated_at', Carbon::today())->get();
            $grouped = $txs->groupBy(function($item) {
                return Carbon::parse($item->updated_at)->format('H'); 
            });

            for ($i = 6; $i <= 21; $i++) {
                $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
                $labels[] = $hour . ':00';
                // FIX: was total_amount
                $series[] = $grouped->has($hour) ? (int) $grouped->get($hour)->sum('total_estimasi') : 0;
            }
        } elseif ($this->chartFilter === 'month') {
            $now = Carbon::now();
            $txs = (clone $query)->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year)->get();
            $grouped = $txs->groupBy(function($item) {
                return Carbon::parse($item->updated_at)->format('j'); 
            });

            for ($i = 1; $i <= $now->daysInMonth; $i++) {
                $labels[] = $i . ' ' . $now->format('M');
                // FIX: was total_amount
                $series[] = $grouped->has((string)$i) ? (int) $grouped->get((string)$i)->sum('total_estimasi') : 0;
            }
        } elseif ($this->chartFilter === 'year') {
            $now = Carbon::now();
            $txs = (clone $query)->whereYear('updated_at', $now->year)->get();
            $grouped = $txs->groupBy(function($item) {
                return Carbon::parse($item->updated_at)->format('n'); 
            });

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            for ($i = 1; $i <= 12; $i++) {
                $labels[] = $months[$i - 1];
                // FIX: was total_amount
                $series[] = $grouped->has((string)$i) ? (int) $grouped->get((string)$i)->sum('total_estimasi') : 0;
            }
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function setFilter($filter)
    {
        $this->chartFilter = $filter;
        $data = $this->getChartData();
        $this->dispatch('update-chart', labels: $data['labels'], series: $data['series']);
    }

    #[Computed]
    public function riwayatPesanan()
    {
        return SupplyOrder::where('pemasok_id', Auth::id())
            ->latest()
            ->take(4)
            ->get();
    }

    public function submitOnboarding()
    {
        $this->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'nama_pemilik'    => 'required|string|max:255',
            'nik'             => 'required|digits:16',
            'no_hp'           => 'required|string|max:20',
            'alamat_gudang'   => 'required|string|max:255',
            'nama_bank'       => 'required|string',
            'bank_lainnya'    => 'required_if:nama_bank,Lainnya',
            'no_rekening'     => 'required|numeric',
            'foto_ktp'        => $this->profile->foto_ktp ? 'nullable|image|max:2048' : 'required|image|max:2048', 
            'foto_gudang'     => $this->profile->foto_gudang ? 'nullable|image|max:2048' : 'required|image|max:2048', 
        ]);

        $updateData = [
            'nama_perusahaan'   => $this->nama_perusahaan,
            'nama_pemilik'      => $this->nama_pemilik,
            'nik'               => $this->nik,
            'no_hp'             => $this->no_hp,
            'alamat_gudang'     => $this->alamat_gudang,
            'status_verifikasi' => 'menunggu_review', 
            'nama_bank' => $this->nama_bank === 'Lainnya'
                ? $this->bank_lainnya
                : $this->nama_bank,
            'no_rekening' => $this->no_rekening,
        ];

        if ($this->foto_ktp && !is_string($this->foto_ktp)) {
            $updateData['foto_ktp'] = $this->foto_ktp->store('suppliers/ktp', 'public');
        }
        if ($this->foto_gudang && !is_string($this->foto_gudang)) {
            $updateData['foto_gudang'] = $this->foto_gudang->store('suppliers/gudang', 'public');
        }

        $this->profile->update($updateData);
        $lkbbUsers = User::where('role', 'lkbb')->get();

        foreach ($lkbbUsers as $lkbb) {
            $lkbb->notify(new NewSupplierSubmission($this->profile));
        }
        session()->flash('message', 'Data berhasil dikirim! Silakan tunggu verifikasi LKBB.');
        unset($this->profile); 
    }

    public function perbaikiData()
    {
        $this->profile->update(['status_verifikasi' => 'belum_melengkapi']);
        unset($this->profile);
    }
}; ?>

<div class="py-8 px-6 md:px-8 w-full space-y-6 relative">

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- ========================================== --}}
    {{-- FASE 1: ONBOARDING BELUM MELENGKAPI DATA   --}}
    {{-- ========================================== --}}
    @if($this->profile->status_verifikasi === 'belum_melengkapi')
        <div class="max-w-3xl">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Selamat Datang di Mitra Pemasok SCFS!</h2>
                <p class="text-gray-500 mt-1">Sebelum mulai menyuplai barang ke kantin, mohon lengkapi profil usaha Anda.</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Perusahaan / Usaha</label>
                            <input wire:model="nama_perusahaan" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            @error('nama_perusahaan') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Gudang / Tempat Usaha</label>
                            <input wire:model="alamat_gudang" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            @error('alamat_gudang') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemilik (Sesuai KTP)</label>
                            <input wire:model="nama_pemilik" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            @error('nama_pemilik') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nomor Induk Kependudukan (NIK)</label>
                            <input 
                                wire:model.defer="nik"
                                type="text"
                                maxlength="16"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,16)"
                                class="w-full py-2.5 px-4 text-sm font-mono border border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                            >
                            @error('nik') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No Handphone / WA Aktif</label>
                            <input wire:model="no_hp" type="text" class="w-full text-sm rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            @error('no_hp') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-1.5">
                                Rekening Penerimaan Dana
                            </label>

                            <div class="flex flex-col md:flex-row gap-3">
                                {{-- Jenis Bank --}}
                                <div class="w-full md:w-1/3">
                                    <select wire:model.live="nama_bank"
                                        class="w-full text-sm rounded-xl border-blue-200 focus:border-blue-500 focus:ring-blue-500 py-2.5 bg-blue-50/30">
                                        <option value="BCA">BCA</option>
                                        <option value="BNI">BNI</option>
                                        <option value="BRI">BRI</option>
                                        <option value="Mandiri">Mandiri</option>
                                        <option value="BJB">BJB</option>
                                        <option value="GoPay">GoPay</option>
                                        <option value="OVO">OVO</option>
                                        <option value="Lainnya">Bank Lainnya...</option>
                                    </select>
                                    @error('nama_bank') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror

                                    @if($nama_bank === 'Lainnya')
                                        <input wire:model="bank_lainnya" type="text" placeholder="Nama bank..." class="mt-2 w-full text-sm rounded-xl border-blue-200 focus:border-blue-500 focus:ring-blue-500 py-2.5 bg-blue-50/30">
                                        @error('bank_lainnya') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                                    @endif
                                </div>

                                {{-- Nomor Rekening --}}
                                <div class="w-full md:w-2/3">
                                    <input wire:model="no_rekening" type="text" placeholder="Nomor rekening" class="w-full text-sm rounded-xl border-blue-200 focus:border-blue-500 focus:ring-blue-500 py-2.5 bg-blue-50/30">
                                    @error('no_rekening') <span class="text-[10px] text-red-500 mt-1 font-bold block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="border-gray-100 my-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition relative">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Upload Foto KTP *</label>
                            <input wire:model="foto_ktp" type="file" accept="image/*" class="w-full text-xs text-gray-500 cursor-pointer">
                            @error('foto_ktp') <span class="text-[10px] text-red-500 mt-2 font-bold block">{{ $message }}</span> @enderror
                            @if($foto_ktp && !is_string($foto_ktp))
                                <img src="{{ $foto_ktp->temporaryUrl() }}" class="mt-3 mx-auto h-24 rounded-lg object-cover shadow-sm border border-gray-200">
                            @elseif($this->profile->foto_ktp)
                                <div class="mt-3 text-xs text-emerald-600 font-bold">✓ Tersimpan</div>
                            @endif
                        </div>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition relative">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Upload Foto Gudang / Usaha *</label>
                            <input wire:model="foto_gudang" type="file" accept="image/*" class="w-full text-xs text-gray-500 cursor-pointer">
                            @error('foto_gudang') <span class="text-[10px] text-red-500 mt-2 font-bold block">{{ $message }}</span> @enderror
                            @if($foto_gudang && !is_string($foto_gudang))
                                <img src="{{ $foto_gudang->temporaryUrl() }}" class="mt-3 mx-auto h-24 rounded-lg object-cover shadow-sm border border-gray-200">
                            @elseif($this->profile->foto_gudang)
                                <div class="mt-3 text-xs text-emerald-600 font-bold">✓ Tersimpan</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-right">
                    <button wire:click="submitOnboarding" wire:loading.attr="disabled" class="px-6 py-2.5 bg-emerald-600 text-white font-bold text-sm rounded-xl shadow-sm hover:bg-emerald-700 transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="submitOnboarding">Kirim Pengajuan</span>
                        <span wire:loading wire:target="submitOnboarding">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- FASE 2: MENUNGGU REVIEW LKBB               --}}
    {{-- ========================================== --}}
    @elseif($this->profile->status_verifikasi === 'menunggu_review')
        <div class="max-w-xl mx-auto mt-10">
            <div class="bg-white p-8 rounded-3xl shadow-xl text-center border border-gray-100">
                <div class="w-20 h-20 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl animate-pulse">⏳</div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Data Sedang Ditinjau</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-6">Terima kasih telah melengkapi data! Tim LKBB saat ini sedang melakukan verifikasi terhadap profil perusahaan Anda. Proses ini biasanya memakan waktu maksimal 1x24 Jam.</p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-700 rounded-full text-xs font-bold border border-yellow-200 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 animate-ping"></span> Status: Pending Review
                </div>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- FASE 3: PENGAJUAN DITOLAK                  --}}
    {{-- ========================================== --}}
    @elseif($this->profile->status_verifikasi === 'ditolak')
        <div class="max-w-xl mx-auto mt-10">
            <div class="bg-white p-8 rounded-3xl shadow-xl text-center border border-red-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-red-500"></div>
                <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">❌</div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Pengajuan Ditolak</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">Mohon maaf, pengajuan pendaftaran pemasok Anda dikembalikan oleh LKBB karena alasan berikut:</p>
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm font-medium mb-6 text-left">
                    "{{ $this->profile->catatan_penolakan ?? 'Dokumen tidak jelas atau tidak lengkap.' }}"
                </div>
                <button wire:click="perbaikiData" class="px-6 py-2.5 bg-gray-900 text-white font-bold text-sm rounded-xl shadow-sm hover:bg-gray-800 transition flex items-center justify-center gap-2 mx-auto">
                    Perbaiki & Kirim Ulang
                </button>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- FASE 4: DASHBOARD UTAMA (DISETUJUI)        --}}
    {{-- ========================================== --}}
    @elseif($this->profile->status_verifikasi === 'disetujui')
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-gray-200 pb-5">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Halo, {{ $this->profile->nama_perusahaan }}! 👋</h2>
                <p class="text-gray-500 text-sm mt-1">Pantau pesanan dan kelola suplai barang ke LKBB di sini.</p>
            </div>
            <a href="{{ route('pemasok.pesanan-masuk') ?? '#' }}" wire:navigate class="px-6 py-3 bg-[#059669] text-white font-bold text-sm rounded-xl transition shadow-lg shadow-emerald-200 flex items-center gap-2 hover:bg-emerald-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                Kelola Pesanan Suplai
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-6">
            
            {{-- Card 1: Saldo Pemasok --}}
            <div class="bg-gradient-to-br from-[#059669] to-teal-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-200/50 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
                <div class="relative z-10 flex flex-col h-full justify-between">
                    <p class="text-emerald-100 text-[10px] font-extrabold tracking-widest mb-1">SALDO PENDAPATAN SUPLAI</p>
                    <h3 class="text-3xl font-black tracking-tight truncate py-2">Rp {{ number_format($this->profile->saldo_pendapatan ?? 0, 0, ',', '.') }}</h3>
                    <a href="{{ route('pemasok.tarik-dana') ?? '#' }}" class="text-[10px] font-bold text-white hover:text-emerald-100 underline underline-offset-2">Tarik Dana Sekarang →</a>
                </div>
            </div>

            {{-- Card 2: Pesanan Baru --}}
            <div class="bg-gradient-to-br from-amber-500 to-amber-700 rounded-2xl p-5 text-white shadow-lg shadow-amber-200/50 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-8 -mt-8 pointer-events-none"></div>
                <div class="relative z-10 flex flex-col h-full justify-between">
                    <p class="text-amber-100 text-[10px] font-extrabold tracking-widest mb-1 flex items-center justify-between">
                        PESANAN BARU (PO)
                        @if($this->pesananBaru > 0)
                            <span class="bg-red-500 text-white px-1.5 py-0.5 rounded text-[8px] animate-pulse">Perlu Diproses</span>
                        @endif
                    </p>
                    <h3 class="text-3xl font-black tracking-tight truncate py-2">{{ $this->pesananBaru }} <span class="text-lg font-medium">Pesanan</span></h3>
                    <a href="{{ route('pemasok.pesanan-masuk') }}" wire:navigate class="text-[10px] font-bold text-white hover:text-amber-100 underline underline-offset-2">Cek Pesanan Masuk →</a>
                </div>
            </div>

            {{-- Card 3: Status Operasional --}}
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-indigo-500">
                <p class="text-[10px] text-gray-500 font-extrabold tracking-widest mb-1">STATUS OPERASIONAL</p>
                <h3 class="text-2xl font-black text-gray-900 truncate my-1 capitalize">
                    {{ $this->profile->status_operasional === 'buka' ? 'Aktif Menyala' : 'Sedang Tutup' }}
                </h3>
                <p class="text-[10px] font-bold text-gray-400">Pastikan gudang buka untuk menerima PO LKBB.</p>
            </div>

            {{-- Card 4: Volume Suplai Hari Ini --}}
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-center border-l-4 border-l-sky-500">
                <p class="text-[10px] text-gray-500 font-extrabold tracking-widest mb-1">VOLUME SUPLAI (HARI INI)</p>
                <h3 class="text-2xl font-black text-gray-900 truncate my-1">Rp {{ number_format($this->statHariIni['total_nominal'], 0, ',', '.') }}</h3>
                @if($this->statHariIni['total_pesanan'] > 0)
                    <p class="text-[10px] text-sky-600 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ $this->statHariIni['total_pesanan'] }} Suplai Diselesaikan
                    </p>
                @else
                    <p class="text-[10px] text-gray-400 font-medium">- Belum ada suplai selesai</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            
            {{-- AREA GRAFIK (CHART) INTERAKTIF --}}
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                    <div>
                        <h3 class="font-black text-gray-900 text-sm">Grafik Penjualan / Suplai Barang</h3>
                        <p class="text-[10px] font-bold text-gray-500 mt-0.5">Total nominal pesanan (PO) yang telah selesai</p>
                    </div>
                    
                    <div class="inline-flex bg-gray-200 p-1 rounded-lg">
                        <button wire:click="setFilter('today')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'today' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Hari Ini</button>
                        <button wire:click="setFilter('month')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'month' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Bulan Ini</button>
                        <button wire:click="setFilter('year')" class="px-3 py-1.5 text-xs font-bold rounded-md transition {{ $chartFilter === 'year' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tahun Ini</button>
                    </div>
                </div>
                
                <div class="p-6 flex-1">
                    <div 
                        x-data="{
                            chart: null,
                            initChart() {
                                let options = {
                                    chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
                                    series: [{ name: 'Volume Suplai (Rp)', data: [] }],
                                    xaxis: { categories: [], tooltip: { enabled: false }, axisBorder: { show: false } },
                                    yaxis: { labels: { formatter: function(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val); } } },
                                    stroke: { curve: 'smooth', width: 3 },
                                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                                    colors: ['#059669'],
                                    dataLabels: { enabled: false },
                                    grid: { strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
                                    tooltip: { y: { formatter: function(val) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val); } } }
                                };
                                this.chart = new ApexCharts(this.$refs.chartContainer, options);
                                this.chart.render();

                                let initialData = @js($this->getChartData());
                                this.chart.updateOptions({ xaxis: { categories: initialData.labels } });
                                this.chart.updateSeries([{ data: initialData.series }]);
                            }
                        }"
                        x-init="initChart()"
                        @update-chart.window="
                            chart.updateOptions({ xaxis: { categories: $event.detail.labels } });
                            chart.updateSeries([{ data: $event.detail.series }]);
                        "
                    >
                        <div x-ref="chartContainer" wire:ignore></div>
                    </div>
                </div>
            </div>

            {{-- KUMPULAN RIWAYAT (KANAN) --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                
                {{-- RIWAYAT PESANAN PO MASUK --}}
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col flex-1">
                    <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-900 text-sm">Pesanan Terbaru</h3>
                        <a href="{{ route('pemasok.pesanan-masuk') }}" wire:navigate class="text-[10px] font-bold text-emerald-600 hover:text-emerald-800 uppercase tracking-widest">Semua PO</a>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <div class="space-y-1">
                            @forelse($this->riwayatPesanan as $po)
                            <div class="flex items-center justify-between p-2.5 hover:bg-gray-50 rounded-xl transition group border border-transparent hover:border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        📦
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-gray-900">{{ $po->nomor_order }}</p>
                                        <p class="text-[10px] text-gray-500">{{ \Carbon\Carbon::parse($po->created_at)->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    {{-- FIX: was total_amount --}}
                                    <p class="font-bold text-sm text-gray-900">Rp {{ number_format($po->total_estimasi ?? 0, 0, ',', '.') }}</p>
                                    <p class="text-[10px] {{ $po->status === 'selesai' ? 'text-emerald-500' : 'text-amber-500' }} font-bold uppercase">{{ $po->status }}</p>
                                </div>
                            </div>
                            @empty
                            <div class="p-6 text-center text-sm text-gray-400 font-medium">
                                Belum ada pesanan masuk.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>

    @endif
</div>