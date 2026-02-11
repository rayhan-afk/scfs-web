<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $products = [];

    public function mount()
    {
        // Ambil produk aktif
        $this->products = Product::where('is_active', true)->get();
    }

    public function buyProduct($productId)
    {
        $user = Auth::user();
        $product = Product::find($productId);
        $wallet = $user->wallet;

        // 1. Validasi Dasar
        if (!$product || !$wallet) {
            $this->js("alert('Terjadi kesalahan data.')");
            return;
        }

        // 2. Cek Stok (Opsional, tapi bagus ada)
        if ($product->stock <= 0) {
            $this->js("alert('Stok habis, bos!')");
            return;
        }

        // 3. Cek Saldo Cukup Gak?
        if ($wallet->grant_balance < $product->price) {
            $this->js("alert('Saldo tidak cukup! Harga: {$product->price}, Saldo: {$wallet->grant_balance}')");
            return;
        }

        // 4. EKSEKUSI TRANSAKSI (Pakai DB Transaction biar aman)
        DB::transaction(function () use ($user, $product, $wallet) {
            
            // A. Kurangi Saldo
            $wallet->decrement('grant_balance', $product->price);

            // B. Kurangi Stok
            $product->decrement('stock');

            // C. Catat Riwayat Transaksi
            Transaction::create([
                'user_id' => $user->id,
                'order_id' => 'TRX-' . time() . rand(100, 999),
                'total_amount' => $product->price,
                'status' => 'success',
                'type' => 'purchase',
            ]);
        });

        // 5. Kirim Sinyal ke Komponen Lain (biar saldo di atas update otomatis)
        $this->dispatch('transaction-success');

        // 6. Notifikasi Sukses
        $this->js("alert('Berhasil membeli {$product->name} seharga Rp {$product->price}!')");
    }
}; ?>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Menu Kantin (Live Transaction)</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($products as $product)
            <div class="border rounded-lg p-4 hover:shadow-lg transition bg-white flex flex-col justify-between h-full">
                
                <div>
                    <div class="h-32 bg-indigo-50 rounded-md mb-3 flex items-center justify-center text-indigo-300">
                        <span class="text-xs font-bold">FOTO MAKANAN</span>
                    </div>

                    <h4 class="font-bold text-md text-gray-800">{{ $product->name }}</h4>
                    <p class="text-xs text-gray-500 mb-2">{{ ucfirst($product->category) }}</p>
                    <p class="text-sm text-gray-600 mb-4">Stok: {{ $product->stock }}</p>
                </div>

                <div class="mt-auto flex justify-between items-center">
                    <span class="text-lg font-bold text-indigo-600">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </span>
                    
                    <button 
                        wire:click="buyProduct({{ $product->id }})"
                        wire:confirm="Yakin mau beli {{ $product->name }}?"
                        class="px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition"
                    >
                        Beli
                    </button>
                </div>
                
            </div>
        @endforeach
    </div>
</div>