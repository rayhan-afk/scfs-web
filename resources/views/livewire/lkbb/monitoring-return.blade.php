<div class="p-6 bg-white rounded-lg shadow">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Pusat Arbitrase & Monitoring Return</h2>
        <p class="text-gray-500 text-sm">LKBB hanya melakukan intervensi eksekusi pada transaksi berstatus sengketa (Banding).</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-800 text-white">
                    <th class="p-3 border">Detail Transaksi</th>
                    <th class="p-3 border">Indikasi Masalah</th>
                    <th class="p-3 border">Status Sistem</th>
                    <th class="p-3 border">Aksi Mediasi LKBB</th>
                </tr>
            </thead>
            <tbody>
                @foreach($all_returns as $ret)
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 border text-sm">
                            <strong>Order ID:</strong> #{{ $ret->supply_order_id }} <br>
                            <strong>Merchant:</strong> {{ $ret->merchant->name }} <br>
                            <strong>Pemasok:</strong> {{ $ret->supplier->name }}
                        </td>
                        <td class="p-3 border text-sm">
                            <span class="font-semibold text-amber-700">[{{ $ret->alasan }}]</span> <br>
                            <span class="text-gray-500">"{{ $ret->deskripsi_masalah }}"</span>
                        </td>
                        <td class="p-3 border">
                            <span class="px-2 py-1 rounded text-xs font-bold
                                {{ $ret->status === 'banding_lkbb' ? 'bg-purple-100 text-purple-800 animate-pulse' : 'bg-gray-100 text-gray-600' }}
                            ">
                                {{ strtoupper($ret->status) }}
                            </span>
                        </td>
                        <td class="p-3 border w-1/3">
                            @if($ret->status === 'banding_lkbb')
                                <div class="space-y-2 bg-purple-50 p-3 rounded border border-purple-200">
                                    <p class="text-xs text-red-600"><strong>Alasan Tolak Pemasok sebelumnya:</strong> {{ $ret->catatan_pemasok }}</p>
                                    <textarea wire:model="catatan_lkbb.{{ $ret->id }}" placeholder="Tulis kesimpulan penandatanganan sengketa hukum..." class="w-full text-xs p-2 border rounded bg-white"></textarea>
                                    <div class="flex gap-1">
                                        <button wire:click="putuskanSengketa({{ $ret->id }}, 'menangkan_merchant')" class="bg-green-600 text-white text-xs px-2 py-1.5 rounded hover:bg-green-700 flex-1 font-medium">
                                            Menangkan Merchant (Refund/Kirim)
                                        </button>
                                        <button wire:click="putuskanSengketa({{ $ret->id }}, 'menangkan_pemasok')" class="bg-red-600 text-white text-xs px-2 py-1.5 rounded hover:bg-red-700 flex-1 font-medium">
                                            Tolak Banding (Pemasok Benar)
                                        </button>
                                    </div>
                                </div>
                            @elseif($ret->status === 'selesai_lkbb')
                                <span class="text-sm text-gray-600 block bg-gray-100 p-2 rounded">
                                    📜 {{ $ret->catatan_lkbb }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs italic">Berjalan otomatis di luar LKBB</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>