<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use Illuminate\Support\Str;

new class extends Component {
    public $nominal = '';
    public $metode_pembayaran = '';

    public function prosesTopup()
    {
        // 1. Validasi Input
        $this->validate([
            'nominal' => 'required|numeric|min:10000',
            'metode_pembayaran' => 'required|string'
        ], [
            'nominal.min' => 'Minimal top up adalah Rp 10.000',
            'nominal.required' => 'Nominal wajib diisi',
            'metode_pembayaran.required' => 'Silakan pilih metode pembayaran',
        ]);

        // 2. Dapatkan data user (merchant) yang sedang login
        $user = Auth::user();

        // 3. Tambah saldo ke MerchantProfile (Sama seperti logika LKBB)
        if ($user->merchantProfile) {
            $profile = $user->merchantProfile;
            $profile->saldo_token += $this->nominal;
            $profile->save();
        }

        // 4. Tambah saldo ke tabel Wallets (Sama seperti logika LKBB)
        $merchantWallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'USER_WALLET'],
            ['account_number' => 'USR-' . strtoupper(Str::random(6)), 'balance' => 0, 'is_active' => true]
        );
        $merchantWallet->increment('balance', $this->nominal);

        // 5. Berikan flash message sukses
        session()->flash('success', 'Berhasil! Saldo Rp' . number_format($this->nominal, 0, ',', '.') . ' telah ditambahkan ke dompet Anda.');
        
        // 6. Redirect ke halaman order
        return redirect()->to('/merchant/order'); 
    }
}; ?>

<div class="min-h-[calc(100vh-5rem)] w-full bg-gray-100/50 flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
        
        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-gray-900">Top Up Saldo Dompet</h2>
                <p class="text-xs font-medium text-gray-500 mt-1">Pilih nominal dan metode pembayaran untuk mengisi saldo Anda.</p>
            </div>
            <button onclick="window.history.back()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="p-6 space-y-8">
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs">1</span>
                    Pilih Nominal Top Up
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach([50000, 100000, 200000, 500000] as $preset)
                        <button type="button" wire:click="$set('nominal', {{ $preset }})" 
                            class="py-2.5 px-4 rounded-xl border {{ $nominal == $preset ? 'bg-blue-50 border-blue-500 text-blue-700 shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:border-blue-300' }} text-sm font-bold transition-all">
                            Rp{{ number_format($preset, 0, ',', '.') }}
                        </button>
                    @endforeach
                </div>

                <div class="relative mt-2">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-gray-500">Rp</span>
                    <input wire:model.live="nominal" type="number" placeholder="Atau masukkan nominal lainnya..." 
                        class="w-full py-3 pl-12 pr-4 text-gray-900 font-bold bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition shadow-sm" />
                </div>
                @error('nominal') <span class="text-[10px] font-bold text-rose-500">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs">2</span>
                    Pilih Metode Pembayaran
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="relative flex cursor-pointer rounded-xl border bg-white p-4 shadow-sm focus:outline-none {{ $metode_pembayaran == 'transfer' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/30' : 'border-gray-200 hover:border-blue-200' }}">
                        <input type="radio" wire:model="metode_pembayaran" value="transfer" class="sr-only" />
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-bold text-gray-900 mb-1">Transfer Bank (VA)</span>
                                <span class="flex items-center text-xs text-gray-500 font-medium">BCA, Mandiri, BNI, BRI</span>
                            </span>
                        </span>
                        <svg class="h-5 w-5 {{ $metode_pembayaran == 'transfer' ? 'text-blue-600' : 'hidden' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    </label>

                    <label class="relative flex cursor-pointer rounded-xl border bg-white p-4 shadow-sm focus:outline-none {{ $metode_pembayaran == 'qris' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/30' : 'border-gray-200 hover:border-blue-200' }}">
                        <input type="radio" wire:model="metode_pembayaran" value="qris" class="sr-only" />
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-bold text-gray-900 mb-1">QRIS</span>
                                <span class="flex items-center text-xs text-gray-500 font-medium">Scan aplikasi M-Banking/E-Wallet</span>
                            </span>
                        </span>
                        <svg class="h-5 w-5 {{ $metode_pembayaran == 'qris' ? 'text-blue-600' : 'hidden' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    </label>

                    <label class="relative flex cursor-pointer rounded-xl border bg-white p-4 shadow-sm focus:outline-none {{ $metode_pembayaran == 'ewallet' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/30' : 'border-gray-200 hover:border-blue-200' }}">
                        <input type="radio" wire:model="metode_pembayaran" value="ewallet" class="sr-only" />
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-bold text-gray-900 mb-1">Dompet Digital</span>
                                <span class="flex items-center text-xs text-gray-500 font-medium">Gopay, OVO, DANA, ShopeePay</span>
                            </span>
                        </span>
                        <svg class="h-5 w-5 {{ $metode_pembayaran == 'ewallet' ? 'text-blue-600' : 'hidden' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    </label>

                    <label class="relative flex cursor-pointer rounded-xl border bg-white p-4 shadow-sm focus:outline-none {{ $metode_pembayaran == 'cod' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/30' : 'border-gray-200 hover:border-blue-200' }}">
                        <input type="radio" wire:model="metode_pembayaran" value="cod" class="sr-only" />
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-bold text-gray-900 mb-1">Bayar Tunai (COD)</span>
                                <span class="flex items-center text-xs text-gray-500 font-medium">Bayar saat petugas/pemasok datang</span>
                            </span>
                        </span>
                        <svg class="h-5 w-5 {{ $metode_pembayaran == 'cod' ? 'text-blue-600' : 'hidden' }}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    </label>
                </div>
                @error('metode_pembayaran') <span class="text-[10px] font-bold text-rose-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="p-6 bg-gray-50 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="w-full md:w-auto text-center md:text-left">
                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Top Up</span>
                <span class="text-2xl font-extrabold text-blue-700">
                    Rp{{ $nominal ? number_format((int)$nominal, 0, ',', '.') : '0' }}
                </span>
            </div>
            
            <button wire:click="prosesTopup" wire:loading.attr="disabled"
                @if(!$nominal || !$metode_pembayaran) disabled @endif
                class="w-full md:w-auto px-8 py-3.5 text-sm font-extrabold text-white rounded-xl shadow-lg transition-all flex items-center justify-center bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="prosesTopup">LANJUTKAN PEMBAYARAN</span>
                <span wire:loading wire:target="prosesTopup" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    MEMPROSES...
                </span>
            </button>
        </div>

    </div>
</div>