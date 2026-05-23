<div class="py-8 px-6 md:px-8 max-w-6xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-gray-200 pb-5">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Ajukan Return Pesanan</h2>
            <p class="text-sm text-gray-500 mt-1">PO <span class="font-mono font-bold text-gray-700">{{ $order->nomor_order }}</span> — laporkan masalah barang yang Anda terima.</p>
        </div>
        <a href="{{ route('merchant.penerimaan') }}" wire:navigate class="text-sm text-gray-500 hover:text-emerald-600 font-medium">← Kembali ke Penerimaan</a>
    </div>

    {{-- Flash --}}
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-xl font-medium">✅ {{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3 rounded-xl font-medium">⚠ {{ session('error') }}</div>
    @endif
    @error('orderStatus')
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 rounded-xl font-medium">⚠ {{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ===== FORM (LEFT 2/3) ===== --}}
        <form wire:submit.prevent="simpanReturn" class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-bold text-gray-900 text-sm">Formulir Komplain</h3>
            </div>

            <div class="p-6 space-y-6">

                {{-- Tipe + Qty --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Tipe Masalah</label>
                        <select wire:model="tipe_masalah" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 py-2.5">
                            <option value="">-- Pilih tipe masalah --</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('tipe_masalah') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Jumlah Bermasalah</label>
                        <input wire:model="qty_bermasalah" type="number" min="1" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 py-2.5 font-mono">
                        @error('qty_bermasalah') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Item-level (optional) --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Item Spesifik (Opsional)</label>
                    <select wire:model="supply_order_detail_id" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500 py-2.5">
                        <option value="">-- Seluruh order --</option>
                        @foreach($order->details as $d)
                            <option value="{{ $d->id }}">{{ $d->nama_produk_snapshot ?? 'Item #'.$d->id }} (qty: {{ $d->qty }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Pilih jika hanya satu item bermasalah, kosongkan jika seluruh order.</p>
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Deskripsi Masalah</label>
                    <textarea wire:model="deskripsi_masalah" rows="4" class="w-full text-sm rounded-xl border-gray-300 focus:border-rose-500 focus:ring-rose-500" placeholder="Contoh: 5 dari 20 kg tomat dalam kondisi membusuk, terlihat bercak hitam dan bau menyengat..."></textarea>
                    @error('deskripsi_masalah') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                {{-- Foto Upload Multiple --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Foto Bukti (1-5 file, max 2MB/file)</label>
                    <input wire:model="foto_bukti_uploads" type="file" multiple accept="image/jpeg,image/png,image/jpg" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer">
                    <div wire:loading wire:target="foto_bukti_uploads" class="text-xs text-rose-500 mt-2 animate-pulse">Mengunggah...</div>
                    @error('foto_bukti_uploads') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    @error('foto_bukti_uploads.*') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror

                    @if(!empty($foto_bukti_uploads))
                        <div class="mt-3 grid grid-cols-3 md:grid-cols-5 gap-2">
                            @foreach($foto_bukti_uploads as $foto)
                                <div class="aspect-square bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
                                    <img src="{{ $foto->temporaryUrl() }}" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Video Upload --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Video Bukti (Opsional, max 20MB, MP4/MOV)</label>
                    <input wire:model="video_bukti_upload" type="file" accept="video/mp4,video/quicktime,video/webm" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    <div wire:loading wire:target="video_bukti_upload" class="text-xs text-indigo-500 mt-2 animate-pulse">Mengunggah video...</div>
                    @error('video_bukti_upload') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    @if($video_bukti_upload)
                        <p class="text-xs text-emerald-600 font-bold mt-2">✓ Video siap di-submit</p>
                    @endif
                </div>

                {{-- Solusi --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Solusi yang Diharapkan</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($solutions as $key => $label)
                            <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition
                                {{ $solusi_diajukan === $key ? 'border-rose-400 bg-rose-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model="solusi_diajukan" value="{{ $key }}" class="mt-1 text-rose-600 focus:ring-rose-500">
                                <span class="text-sm font-medium text-gray-800">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('solusi_diajukan') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="px-6 py-3 bg-rose-600 text-white font-bold text-sm rounded-xl hover:bg-rose-700 transition shadow-md shadow-rose-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpanReturn">Kirim Pengajuan Return</span>
                    <span wire:loading wire:target="simpanReturn">Memproses...</span>
                </button>
            </div>
        </form>

        {{-- ===== SIDEBAR INFO (RIGHT 1/3) ===== --}}
        <div class="space-y-4">
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                <h4 class="font-bold text-amber-900 text-sm flex items-center gap-2">⏰ Deadline Pengajuan</h4>
                <p class="text-xs text-amber-700 mt-2 leading-relaxed">
                    Return wajib diajukan <strong>maksimal 24 jam</strong> sejak barang diterima.
                    Pemasok memiliki <strong>{{ \App\Models\PengajuanReturn::DEADLINE_HOURS }} jam</strong> untuk merespon.
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <h4 class="font-bold text-gray-900 text-sm mb-3">Ringkasan PO</h4>
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between"><dt class="text-gray-500">Nomor</dt><dd class="font-mono font-bold text-gray-900">{{ $order->nomor_order }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Status PO</dt><dd class="font-bold text-gray-900">{{ $order->status }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Total</dt><dd class="font-bold text-emerald-700">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Item</dt><dd class="font-bold text-gray-900">{{ $order->details->count() }} jenis</dd></div>
                </dl>
            </div>
        </div>
    </div>

    {{-- ===== RIWAYAT RETURN UNTUK PO INI ===== --}}
    @if($riwayat_returns->count() > 0)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mt-8">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-bold text-gray-900 text-sm">Riwayat Return untuk PO Ini</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($riwayat_returns as $ret)
                    @php
                        $color = $ret->statusColor();
                        $isFraudFlag = $ret->flag_fraud;
                    @endphp
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider">{{ $ret->statusLabel() }}</span>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md text-[10px] font-bold">{{ $ret->tipeLabel() }}</span>
                                    @if($isFraudFlag)
                                        <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase">⚠ Flagged</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500">Diajukan {{ $ret->created_at->diffForHumans() }} • Solusi diminta: <strong>{{ $ret->solusiLabel() }}</strong></p>
                            </div>
                            @if($ret->canBeAppealed())
                                <button wire:click="ajukanBanding({{ $ret->id }})"
                                        wire:confirm="Ajukan banding ke LKBB? Sengketa akan ditengahi oleh tim arbitrase."
                                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg shadow-sm">
                                    ⚖ Ajukan Banding ke LKBB
                                </button>
                            @endif
                        </div>

                        <p class="text-sm text-gray-700 italic mb-3">"{{ $ret->deskripsi_masalah }}"</p>

                        @if(!empty($ret->foto_bukti))
                            <div class="flex gap-2 mb-3">
                                @foreach($ret->foto_bukti as $foto)
                                    <a href="{{ asset('storage/'.$foto) }}" target="_blank" class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 bg-gray-50 hover:opacity-80 transition">
                                        <img src="{{ asset('storage/'.$foto) }}" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                                @if($ret->video_bukti)
                                    <a href="{{ asset('storage/'.$ret->video_bukti) }}" target="_blank" class="w-16 h-16 rounded-lg bg-indigo-50 border border-indigo-200 flex items-center justify-center hover:opacity-80">
                                        <span class="text-2xl">🎥</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if($ret->catatan_pemasok)
                            <div class="bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-xl p-3 text-xs">
                                <p class="font-bold text-{{ $color }}-700 mb-1">Catatan Pemasok:</p>
                                <p class="text-{{ $color }}-800">{{ $ret->catatan_pemasok }}</p>
                                @if($ret->keputusan_resolusi)
                                    <p class="mt-2 text-{{ $color }}-700"><strong>Resolusi:</strong> {{ ucfirst(str_replace('_', ' ', $ret->keputusan_resolusi)) }}</p>
                                @endif
                            </div>
                        @endif

                        @if($ret->catatan_lkbb)
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs mt-2">
                                <p class="font-bold text-blue-700 mb-1">📜 Keputusan LKBB:</p>
                                <p class="text-blue-800">{{ $ret->catatan_lkbb }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
