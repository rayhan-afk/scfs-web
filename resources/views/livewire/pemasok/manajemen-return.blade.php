<div class="p-6 bg-white rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Manajemen Pengajuan Return dari Merchant</h2>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-6">
        @forelse($returns as $ret)
            <div class="p-4 border rounded-lg bg-gray-50 flex flex-col md:flex-row gap-4 items-start justify-between">
                <div class="space-y-2 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-lg text-blue-600">Order #{{ $ret->supply_order_id }}</span>
                        <span class="text-sm bg-gray-200 text-gray-700 px-2 py-0.5 rounded">Merchant: {{ $ret->merchant->name }}</span>
                    </div>
                    <p><strong>Masalah:</strong> <span class="text-red-600 font-semibold">{{ $ret->alasan }}</span></p>
                    <p class="text-gray-600 text-sm">"{{ $ret->deskripsi_masalah }}"</p>
                    <p class="text-sm text-gray-700"><strong>Solusi Diminta:</strong> {{ strtoupper($ret->solusi_diajukan) }}</p>
                    
                    @if($ret->foto_bukti)
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $ret->foto_bukti) }}" target="_blank" class="text-blue-500 hover:underline text-sm font-semibold flex items-center gap-1">
                                🖼️ Lihat Foto Bukti Transparan
                            </a>
                        </div>
                    @endif
                </div>

                <div class="w-full md:w-80 space-y-2">
                    <span class="block text-xs font-bold text-gray-400 uppercase">Status: {{ $ret->status }}</span>
                    
                    @if($ret->status === 'pending')
                        <textarea wire:model="catatan_pemasok.{{ $ret->id }}" placeholder="Tulis catatan persetujuan atau alasan penolakan di sini..." class="w-full text-sm p-2 border rounded"></textarea>
                        <div class="flex gap-2">
                            <button wire:click="prosesSetuju({{ $ret->id }})" class="flex-1 bg-green-600 text-white text-sm py-2 rounded hover:bg-green-700 font-bold">
                                Setujui
                            </button>
                            <button wire:click="prosesTolak({{ $ret->id }})" class="flex-1 bg-red-600 text-white text-sm py-2 rounded hover:bg-red-700 font-bold">
                                Tolak
                            </button>
                        </div>
                    @else
                        <div class="bg-white p-3 rounded border text-sm text-gray-600">
                            <strong>Respon Anda:</strong> {{ $ret->catatan_pemasok ?? '-' }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center py-8">Belum ada pengajuan return barang saat ini.</p>
        @endforelse
    </div>
</div>