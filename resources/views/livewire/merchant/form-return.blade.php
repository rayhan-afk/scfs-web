<div class="p-6 max-w-4xl mx-auto bg-white rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">Ajukan Return Pesanan #{{ $order->id }}</h2>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="simpanReturn" class="space-y-4">
        <div>
            <label class="block font-medium text-gray-700">Alasan Utama</label>
            <select wire:model="alasan" class="w-full mt-1 border-gray-300 rounded shadow-sm">
                <option value="">-- Pilih Alasan --</option>
                <option value="Barang Rusak">Barang Rusak / Cacat</option>
                <option value="Basi">Basi / Kedaluwarsa</option>
                <option value="Kurang Jumlah">Kurang Jumlah Item</option>
                <option value="Tidak Sesuai">Tidak Sesuai Spesifikasi</option>
            </select>
            @error('alasan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block font-medium text-gray-700">Deskripsi Masalah</label>
            <textarea wire:model="deskripsi_masalah" rows="4" class="w-full mt-1 border-gray-300 rounded shadow-sm" placeholder="Jelaskan secara rinci kondisi barang saat diterima..."></textarea>
            @error('deskripsi_masalah') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block font-medium text-gray-700">Foto Bukti</label>
            <input type="file" wire:model="foto_bukti" class="mt-1 block w-full text-sm text-gray-500">
            @error('foto_bukti') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block font-medium text-gray-700">Solusi yang Diharapkan</label>
            <div class="mt-2 space-x-4">
                <label><input type="radio" wire:model="solusi_diajukan" value="refund"> Pengembalian Dana (Refund)</label>
                <label><input type="radio" wire:model="solusi_diajukan" value="kirim_ulang"> Kirim Ulang Barang</label>
            </div>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Kirim Pengajuan
        </button>
    </form>

    <hr class="my-8">

    <h3 class="text-xl font-bold mb-4 text-gray-800">Status Return Anda</h3>
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 border">ID Order</th>
                <th class="p-2 border">Alasan</th>
                <th class="p-2 border">Status</th>
                <th class="p-2 border">Aksi / Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($riwayat_returns as $ret)
                <tr>
                    <td class="p-2 border">#{{ $ret->supply_order_id }}</td>
                    <td class="p-2 border">{{ $ret->alasan }}</td>
                    <td class="p-2 border">
                        <span class="px-2 py-1 rounded text-xs font-bold 
                            {{ $ret->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $ret->status === 'disetujui' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $ret->status === 'ditolak' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $ret->status === 'banding_lkbb' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $ret->status === 'selesai_lkbb' ? 'bg-blue-100 text-blue-800' : '' }}
                        ">
                            {{ strtoupper($ret->status) }}
                        </span>
                    </td>
                    <td class="p-2 border">
                        @if($ret->status === 'ditolak')
                            <p class="text-sm text-red-600 mb-1">Alasan Pemasok: {{ $ret->catatan_pemasok }}</p>
                            <button wire:click="ajukanBanding({{ $ret->id }})" class="bg-purple-600 text-white text-xs px-2 py-1 rounded hover:bg-purple-700">
                                Ajukan Banding ke LKBB
                            </button>
                        @elseif($ret->status === 'selesai_lkbb')
                            <p class="text-sm text-blue-600">Keputusan LKBB: {{ $ret->catatan_lkbb }}</p>
                        @else
                            <span class="text-gray-400 text-sm">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>