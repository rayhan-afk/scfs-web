<div class="py-8 px-6 md:px-8 w-full space-y-6">

    {{-- Header --}}
    <div class="border-b border-gray-200 pb-5">
        <h2 class="text-2xl font-bold text-gray-900">Manajemen Return Merchant</h2>
        <p class="text-sm text-gray-500 mt-1">Anda adalah <strong>reviewer utama</strong>. Setujui dengan resolusi yang tepat, atau tolak dengan alasan jelas.</p>
    </div>

    @if(session('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-xl font-medium">{{ session('message') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3 rounded-xl font-medium">{{ session('error') }}</div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-amber-700 uppercase tracking-widest">Perlu Direview</p>
            <p class="text-3xl font-black text-amber-900 mt-1">{{ $this->counts['pending'] }}</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-widest">Disetujui</p>
            <p class="text-3xl font-black text-emerald-900 mt-1">{{ $this->counts['approved'] }}</p>
        </div>
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-rose-700 uppercase tracking-widest">Ditolak</p>
            <p class="text-3xl font-black text-rose-900 mt-1">{{ $this->counts['rejected'] }}</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-purple-700 uppercase tracking-widest">Banding LKBB</p>
            <p class="text-3xl font-black text-purple-900 mt-1">{{ $this->counts['escalated'] }}</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor PO..." class="flex-1 rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 text-sm py-2.5">
        <select wire:model.live="statusFilter" class="w-full md:w-64 rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 text-sm py-2.5">
            <option value="semua">Semua Status</option>
            <option value="pending_supplier_review">⏳ Perlu Review</option>
            <option value="approved">✅ Disetujui</option>
            <option value="rejected">❌ Ditolak</option>
            <option value="escalated_lkbb">⚖ Banding LKBB</option>
            <option value="resolved">📜 Diselesaikan LKBB</option>
        </select>
    </div>

    {{-- List --}}
    <div class="space-y-3">
        @forelse($this->returns as $ret)
            @php $color = $ret->statusColor(); @endphp
            <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition" wire:key="ret-{{ $ret->id }}">
                <div class="flex flex-col md:flex-row gap-4 justify-between">
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider">{{ $ret->statusLabel() }}</span>
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-md text-[10px] font-bold">{{ $ret->tipeLabel() }}</span>
                            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-md text-[10px] font-bold">Qty: {{ $ret->qty_bermasalah }}</span>
                            @if($ret->flag_fraud)
                                <span class="bg-rose-100 text-rose-700 px-2 py-1 rounded-md text-[10px] font-bold uppercase animate-pulse">⚠ Flagged</span>
                            @endif
                            @if($ret->isExpired())
                                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-[10px] font-bold uppercase">⏱ Expired</span>
                            @endif
                        </div>
                        <div class="text-sm">
                            <span class="font-mono text-xs text-gray-500">{{ $ret->supplyOrder->nomor_order ?? '-' }}</span>
                            <span class="mx-2 text-gray-300">•</span>
                            <span class="font-bold text-gray-900">{{ $ret->merchant->merchantProfile->nama_kantin ?? $ret->merchant->name }}</span>
                            <span class="mx-2 text-gray-300">•</span>
                            <span class="text-gray-500 text-xs">{{ $ret->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-700 italic line-clamp-2">"{{ $ret->deskripsi_masalah }}"</p>
                        <p class="text-xs text-gray-500">Solusi diminta: <strong class="text-gray-700">{{ $ret->solusiLabel() }}</strong></p>
                    </div>
                    <div class="flex flex-col gap-2 items-stretch md:w-48">
                        @if($ret->status === 'pending_supplier_review')
                            <button wire:click="openDetail({{ $ret->id }})" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl shadow-sm">
                                Review Sekarang
                            </button>
                            @if($ret->deadline_at)
                                <p class="text-[10px] text-gray-500 text-center">Deadline: {{ $ret->deadline_at->diffForHumans() }}</p>
                            @endif
                        @else
                            <button wire:click="openDetail({{ $ret->id }})" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl">
                                Lihat Detail
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border border-gray-200 rounded-2xl py-16 text-center text-gray-400 text-sm">Belum ada pengajuan return di kategori ini.</div>
        @endforelse
    </div>

    {{ $this->returns->links() }}

    {{-- ===== MODAL DETAIL & ACTION ===== --}}
    @if($showModal && $this->selected)
        @php $ret = $this->selected; $color = $ret->statusColor(); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-hidden flex flex-col">

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900">Detail Pengajuan Return</h3>
                        <p class="text-[11px] font-mono text-gray-500 mt-0.5">PO {{ $ret->supplyOrder->nomor_order ?? '-' }} • {{ $ret->merchant->name }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-5">

                    {{-- Status Badge --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="bg-{{ $color }}-100 text-{{ $color }}-700 px-3 py-1.5 rounded-lg text-xs font-bold uppercase">{{ $ret->statusLabel() }}</span>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold">{{ $ret->tipeLabel() }}</span>
                        <span class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold">Qty Bermasalah: {{ $ret->qty_bermasalah }}</span>
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">Deskripsi Masalah</p>
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-800 leading-relaxed">{{ $ret->deskripsi_masalah }}</div>
                    </div>

                    {{-- Bukti --}}
                    <div>
                        <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">Bukti Foto/Video</p>
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
                            @foreach(($ret->foto_bukti ?? []) as $foto)
                                <a href="{{ asset('storage/'.$foto) }}" target="_blank" class="aspect-square bg-gray-50 border border-gray-200 rounded-lg overflow-hidden hover:opacity-80 transition">
                                    <img src="{{ asset('storage/'.$foto) }}" class="w-full h-full object-cover">
                                </a>
                            @endforeach
                            @if($ret->video_bukti)
                                <a href="{{ asset('storage/'.$ret->video_bukti) }}" target="_blank" class="aspect-square bg-indigo-50 border-2 border-indigo-300 rounded-lg flex flex-col items-center justify-center hover:opacity-80">
                                    <span class="text-3xl">🎥</span>
                                    <span class="text-[10px] font-bold text-indigo-700 mt-1">Video</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Item & Solusi --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Item Terdampak</p>
                            <p class="text-sm font-bold text-gray-900">
                                @if($ret->supplyOrderDetail)
                                    {{ $ret->supplyOrderDetail->nama_produk_snapshot }} (qty order: {{ $ret->supplyOrderDetail->qty }})
                                @else
                                    Seluruh order
                                @endif
                            </p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-xl border border-orange-200">
                            <p class="text-[10px] font-bold text-orange-700 uppercase mb-1">Solusi yang Diminta Merchant</p>
                            <p class="text-sm font-bold text-orange-900">{{ $ret->solusiLabel() }}</p>
                        </div>
                    </div>

                    {{-- Audit Trail --}}
                    @if(!empty($ret->riwayat_audit))
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">📋 Audit Trail</p>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 max-h-40 overflow-y-auto space-y-2">
                                @foreach($ret->riwayat_audit as $log)
                                    <div class="flex gap-3 text-xs">
                                        <span class="font-mono text-gray-400 shrink-0">{{ \Carbon\Carbon::parse($log['at'])->format('d/m H:i') }}</span>
                                        <span class="font-bold text-gray-700 uppercase shrink-0">{{ $log['actor'] }}</span>
                                        <span class="text-gray-600">{{ $log['event'] }}{{ $log['detail'] ? ' — '.$log['detail'] : '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ===== ACTION ZONE (hanya jika pending) ===== --}}
                    @if($ret->status === 'pending_supplier_review')
                        <hr class="border-gray-200">
                        <div class="space-y-4">
                            <div>
                                <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">Jenis Resolusi (wajib jika menyetujui)</p>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach(\App\Models\PengajuanReturn::SOLUTIONS as $key => $label)
                                        <label class="flex items-start gap-2 p-3 rounded-xl border-2 cursor-pointer transition
                                            {{ $keputusan_resolusi === $key ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                                            <input type="radio" wire:model="keputusan_resolusi" value="{{ $key }}" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-xs font-medium text-gray-800">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('keputusan_resolusi') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">Catatan untuk Merchant</p>
                                <textarea wire:model="catatan" rows="3" class="w-full text-sm rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Refund akan diproses dalam 3 hari kerja..."></textarea>
                                @error('catatan') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @else
                        @if($ret->catatan_pemasok)
                            <div class="bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-xl p-4">
                                <p class="text-[11px] font-bold text-{{ $color }}-700 uppercase mb-1">Catatan Anda Sebelumnya</p>
                                <p class="text-sm text-{{ $color }}-900">{{ $ret->catatan_pemasok }}</p>
                                @if($ret->keputusan_resolusi)
                                    <p class="text-xs text-{{ $color }}-700 mt-2"><strong>Resolusi:</strong> {{ ucfirst(str_replace('_', ' ', $ret->keputusan_resolusi)) }}</p>
                                @endif
                            </div>
                        @endif
                        @if($ret->catatan_lkbb)
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                <p class="text-[11px] font-bold text-blue-700 uppercase mb-1">📜 Keputusan LKBB</p>
                                <p class="text-sm text-blue-900">{{ $ret->catatan_lkbb }}</p>
                            </div>
                        @endif
                    @endif

                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-between gap-3">
                    <button wire:click="closeDetail" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100">Tutup</button>
                    @if($ret->status === 'pending_supplier_review')
                        <div class="flex gap-2">
                            <button wire:click="reject" wire:confirm="Tolak return ini? Merchant dapat mengajukan banding ke LKBB."
                                    class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl shadow-md">
                                ✕ Tolak
                            </button>
                            <button wire:click="approve" wire:confirm="Setujui return dengan resolusi yang dipilih?"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-md">
                                ✓ Setujui dengan Resolusi
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
