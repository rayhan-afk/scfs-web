<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\User;

new
#[Layout('layouts.lkbb')]
class extends Component {
    use WithPagination;

    public $search = '';
    public $activeTab = 'Semua'; // Semua | Disetujui | Diajukan | Belum Diajukan | Ditolak

    public function updatedSearch()    { $this->resetPage(); }
    public function updatedActiveTab() { $this->resetPage(); }

    /**
     * Daftar Mahasiswa beserta agregat metrik (anti N+1 via withSum).
     */
    #[Computed]
    public function mahasiswas()
    {
        $query = User::query()
            ->where('role', 'mahasiswa')
            ->with([
                'mahasiswaProfile:id,user_id,nim,jurusan,no_hp,semester,ipk,status_verifikasi,status_bantuan,saldo',
            ])
            ->withSum(['transactions as total_bantuan_cair' => function ($q) {
                $q->where('type', 'penerimaan_bantuan')->whereIn('status', ['success', 'sukses', 'lunas']);
            }], 'total_amount')
            ->withSum(['transactions as total_jajan' => function ($q) {
                $q->where('type', 'pembayaran_makanan')->whereIn('status', ['success', 'sukses', 'lunas']);
            }], 'total_amount');

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhereHas('mahasiswaProfile', function ($p) use ($term) {
                      $p->where('nim', 'like', "%{$term}%")
                        ->orWhere('jurusan', 'like', "%{$term}%")
                        ->orWhere('no_hp', 'like', "%{$term}%");
                  });
            });
        }

        if ($this->activeTab !== 'Semua') {
            $status = strtolower(str_replace(' ', '_', $this->activeTab));
            $query->whereHas('mahasiswaProfile', function ($p) use ($status) {
                if ($status === 'belum_diajukan') {
                    $p->where(function ($s) {
                        $s->whereNull('status_bantuan')->orWhere('status_bantuan', 'belum_diajukan');
                    });
                } else {
                    $p->where('status_bantuan', $status);
                }
            });
        }

        return $query->latest()->paginate(15);
    }
}; ?>

<div class="py-12 px-6 md:px-8 w-full space-y-6 relative">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Buku Besar Mahasiswa</h2>
            <p class="text-gray-500 text-sm mt-1">Audit saldo, alokasi beasiswa, dan penyerapan di kantin tiap mahasiswa penerima manfaat.</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-96">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama, NIM, jurusan, atau no HP..."
                class="w-full py-2.5 pl-10 pr-4 text-sm text-gray-700 bg-gray-50 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition">
        </div>

        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            @foreach(['Semua', 'Belum Diajukan', 'Diajukan', 'Disetujui', 'Ditolak'] as $tab)
                <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-2 text-sm rounded-xl transition-all whitespace-nowrap
                    {{ $activeTab === $tab ? 'bg-blue-600 text-white shadow-md font-bold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100 font-medium' }}">
                    {{ $tab }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-gray-900 text-sm">Daftar Mahasiswa Terdaftar</h3>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-100">Total: {{ $this->mahasiswas->total() }} Orang</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white text-gray-500 text-[10px] uppercase font-bold tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Nama / NIM</th>
                        <th class="px-6 py-4">Jurusan</th>
                        <th class="px-6 py-4 text-right">Sisa Saldo</th>
                        <th class="px-6 py-4 text-right">Total Bantuan</th>
                        <th class="px-6 py-4 text-right">Total Jajan</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->mahasiswas as $mhs)
                        @php $profile = $mhs->mahasiswaProfile; @endphp
                        <tr class="hover:bg-gray-50/80 transition group">
                            {{-- Nama --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-sm font-bold bg-blue-100 text-blue-600 flex-shrink-0">
                                        {{ strtoupper(substr($mhs->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm">{{ $mhs->name }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ $profile->nim ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Jurusan --}}
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-700">{{ $profile->jurusan ?? '-' }}</div>
                                <div class="text-[10px] font-medium text-gray-500 mt-0.5">Smt: {{ $profile->semester ?: '-' }} | IPK: <span class="font-bold text-gray-700">{{ $profile->ipk ?: '-' }}</span></div>
                            </td>

                            {{-- Saldo --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold {{ ($profile?->saldo ?? 0) > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    Rp {{ number_format($profile?->saldo ?? 0, 0, ',', '.') }}
                                </div>
                            </td>

                            {{-- Total Bantuan --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-emerald-600">Rp {{ number_format($mhs->total_bantuan_cair ?? 0, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-gray-400 font-medium mt-0.5">Akumulasi cair</div>
                            </td>

                            {{-- Total Jajan --}}
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-purple-600">Rp {{ number_format($mhs->total_jajan ?? 0, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-gray-400 font-medium mt-0.5">QR Pay</div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @php $sb = $profile?->status_bantuan ?? 'belum_diajukan'; @endphp
                                @if($sb === 'disetujui')
                                    <span class="bg-green-100 text-green-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-green-200">Ready</span>
                                @elseif($sb === 'diajukan')
                                    <span class="bg-blue-100 text-blue-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-blue-200 inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Menunggu LKBB
                                    </span>
                                @elseif($sb === 'ditolak')
                                    <span class="bg-red-100 text-red-700 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-red-200">Ditolak</span>
                                @else
                                    <span class="bg-gray-100 text-gray-500 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider border border-gray-200">Belum Diajukan</span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('lkbb.entitas.mahasiswa-detail', $mhs->id) }}" wire:navigate
                                   class="inline-flex items-center px-3 py-1.5 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors uppercase tracking-wider">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="text-4xl mb-3">🎓</div>
                        Belum ada data mahasiswa.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->mahasiswas->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $this->mahasiswas->links() }}</div>
        @endif
    </div>
</div>
