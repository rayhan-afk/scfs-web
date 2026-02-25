<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    public $search = '';
    
    // Variabel untuk Modal Suntik Saldo
    public $showModal = false;
    public $selectedStudentId = null;
    public $studentName = '';
    public $amount = '';

    // Reset paginasi saat mengetik di kolom pencarian
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function with()
    {
        // FILTER: Mengambil user dengan role mahasiswa
        $mahasiswa = User::with('studentWallet')
            ->whereIn('role', ['mahasiswa', 'Mahasiswa', 'student', 'Student'])
            ->where('name', 'like', '%'.$this->search.'%')
            ->paginate(10);

        return [
            'daftarMahasiswa' => $mahasiswa
        ];
    }

    public function openTopUp($studentId, $name)
    {
        $this->selectedStudentId = $studentId;
        $this->studentName = $name;
        $this->reset(['amount']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function submitBantuan()
    {
        $this->validate([
            'amount' => 'required|numeric|min:1000',
        ]);

        try {
            DB::transaction(function () {
                // 1. Cari atau buat dompet untuk mahasiswa tersebut jika belum punya
                $wallet = Wallet::firstOrCreate(
                    [
                        'user_id' => $this->selectedStudentId,
                        'type' => 'STUDENT_WALLET' // Pastikan relasi di User.php juga menggunakan 'STUDENT_WALLET'
                    ],
                    [
                        'account_number' => 'MHS-' . Str::upper(Str::random(6)),
                        'balance' => 0,
                        'is_active' => true,
                    ]
                );

                // 2. Buat Transaksi
                $transaction = Transaction::create([
                    'user_id' => Auth::id() ?? 1,
                    'order_id' => null,
                    'total_amount' => $this->amount,
                    'type' => 'bantuan_masuk',
                    'status' => 'success',
                    'description' => "Penyaluran Bantuan Mahasiswa ke: {$this->studentName}", 
                ]);

                // 3. Tambah saldo mahasiswa
                $wallet->increment('balance', $this->amount);

                // 4. Catat di Buku Besar (Ledger)
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'entry_type' => 'CREDIT',
                    'amount' => $this->amount,
                    'balance_after' => $wallet->fresh()->balance,
                ]);
            });

            session()->flash('message', 'Bantuan sebesar Rp ' . number_format($this->amount, 0, ',', '.') . ' berhasil disalurkan ke ' . $this->studentName);
            $this->showModal = false;

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Terjadi kesalahan sistem saat memproses bantuan.');
        }
    }
}; ?>

<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Saldo Bantuan (Mahasiswa)</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola dan salurkan dana bantuan khusus mahasiswa.</p>
        </div>
        <div class="w-full md:w-72">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama mahasiswa..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-6 py-4">Data Mahasiswa</th>
                        <th class="px-6 py-4">Saldo Bantuan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($daftarMahasiswa as $mhs)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ $mhs->name }}</div>
                                <div class="text-xs text-gray-500 mt-1">No. Rek: {{ $mhs->studentWallet->account_number ?? 'Belum ada dompet' }}</div>
                            </td>
                            <td class="px-6 py-4 font-bold text-blue-600">
                                Rp {{ number_format($mhs->studentWallet->balance ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="openTopUp({{ $mhs->id }}, '{{ addslashes($mhs->name) }}')" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-semibold inline-flex items-center gap-2">
                                    + Suntik Bantuan
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                @if($search)
                                    Tidak ada mahasiswa dengan nama "{{ $search }}"
                                @else
                                    Belum ada data mahasiswa di sistem.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($daftarMahasiswa->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $daftarMahasiswa->links() }}
            </div>
        @endif
    </div>

    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl transform transition-all">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Suntik Saldo Bantuan</h3>
            <p class="text-sm text-gray-500 mb-6">Penyaluran dana bantuan untuk: <strong class="text-gray-800">{{ $studentName }}</strong></p>

            <form wire:submit="submitBantuan">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Bantuan (Rp)</label>
                    <input type="number" wire:model="amount" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-sm" placeholder="Contoh: 500000">
                    @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold flex items-center gap-2" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitBantuan">Salurkan Dana</span>
                        <span wire:loading wire:target="submitBantuan">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>