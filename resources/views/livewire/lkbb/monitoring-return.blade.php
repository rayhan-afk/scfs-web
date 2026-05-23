<div class="p-6 max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="border-b border-gray-200 pb-5">
        <h2 class="text-2xl font-bold text-gray-800">Pusat Arbitrase & Monitoring Return</h2>
        <p class="text-sm text-gray-500 mt-1">LKBB hanya menjadi <strong>mediator</strong> untuk sengketa (banding). Persetujuan utama tetap di pemasok.</p>
    </div>

    @if(session('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-xl font-medium">{{ session('message') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3 rounded-xl font-medium">{{ session('error') }}</div>
    @endif

    {{-- Analytics --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total Return</p>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ $this->analytics['total'] }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-amber-700 uppercase tracking-widest">Pending Pemasok</p>
            <p class="text-3xl font-black text-amber-900 mt-1">{{ $this->analytics['pending'] }}</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 ring-2 ring-purple-300">
            <p class="text-[10px] font-bold text-purple-700 uppercase tracking-widest">⚖ Perlu Arbitrase</p>
            <p class="text-3xl font-black text-purple-900 mt-1">{{ $this->analytics['escalated'] }}</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-blue-700 uppercase tracking-widest">Resolved LKBB</p>
            <p class="text-3xl font-black text-blue-900 mt-1">{{ $this->analytics['resolved'] }}</p>
        </div>
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
            <p class="text-[10px] font-bold text-rose-700 uppercase tracking-widest">⚠ Fraud Flag</p>
            <p class="text-3xl font-black text-rose-900 mt-1">{{ $this->analytics['fraud_flag'] }}</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor PO..." class="flex-1 rounded-xl border-gray-200 focus:border-purple-500 focus:ring-purple-500 text-sm py-2.5">
        <select wire:model.live="statusFilter" class="w-full md:w-64 rounded-xl border-gray-200 focus:border-purple-500 focus:ring-purple-500 text-sm py-2.5">
            <option value="semua">Semua Status</option>
            <option value="pending_supplier_review">⏳ Pending Pemasok</option>
            <option value="approved">✅ Disetujui Pemasok</option>
            <option value="rejected">❌ Ditolak Pemasok</option>
            <option value="escalated_lkbb">⚖ Eskalasi (BUTUH AKSI)</option>
            <option value="resolved">📜 Resolved LKBB</option>
        </select>
        <label class="flex items-center gap-2 px-3 py-2.5 bg-rose-50 border border-rose-200 rounded-xl cursor-pointer">
            <input type="checkbox" wire:model.live="onlyFraud" class="text-rose-600 focus:ring-rose-500 rounded">
            <span class="text-xs font-bold text-rose-700">Hanya yang Flagged</span>
        </label>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-[10px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-4">Detail</th>
                        <th class="px-5 py-4">Pihak</th>
                        <th class="px-5 py-4">Masalah</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($this->returns as $ret)
                        @php $color = $ret->statusColor(); @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-mono text-xs font-bold text-gray-700">{{ $ret->supplyOrder->nomor_order ?? '-' }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $ret->created_at->format('d M Y, H:i') }}</div>
                                @if($ret->flag_fraud)
                                    <span class="inline-block mt-1 bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase">⚠ Flagged</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs"><strong class="text-gray-900">M:</strong> {{ $ret->merchant->name ?? '-' }}</div>
                                <div class="text-xs"><strong class="text-gray-900">P:</strong> {{ $ret->supplier->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded text-[10px] font-bold">{{ $ret->tipeLabel() }}</span>
                                <p class="text-xs text-gray-600 mt-1 line-clamp-1">{{ $ret->deskripsi_masalah }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="bg-{{ $color }}-100 text-{{ $color }}-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">{{ $ret->statusLabel() }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button wire:click="openDetail({{ $ret->id }})" class="px-4 py-2 text-xs font-bold rounded-lg transition
                                    {{ $ret->status === 'escalated_lkbb'
                                        ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-md animate-pulse'
                                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                                    {{ $ret->status === 'escalated_lkbb' ? '⚖ Arbitrase' : 'Detail' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-16 text-center text-gray-400 text-sm">Tidak ada return di kategori ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->returns->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $this->returns->links() }}</div>
        @endif
    </div>

    {{-- ===== MODAL ARBITRASE / DETAIL ===== --}}
    @if($showModal && $this->selected)
        @php $ret = $this->selected; $color = $ret->statusColor(); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-hidden flex flex-col">

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900">⚖ Arbitrase Return</h3>
                        <p class="text-[11px] font-mono text-gray-500 mt-0.5">PO {{ $ret->supplyOrder->nomor_order ?? '-' }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-5">

                    {{-- Status & Pihak --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Merchant (Pelapor)</p>
                            <p class="text-sm font-bold text-gray-900">{{ $ret->merchant->name }}</p>
                            <p class="text-xs text-gray-500">{{ $ret->merchant->email }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Pemasok (Terlapor)</p>
                            <p class="text-sm font-bold text-gray-900">{{ $ret->supplier->name }}</p>
                            <p class="text-xs text-gray-500">{{ $ret->supplier->email }}</p>
                        </div>
                    </div>

                    {{-- Klaim Merchant --}}
                    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
                        <p class="text-[11px] font-bold text-rose-700 uppercase mb-2">📩 Klaim Merchant</p>
                        <div class="space-y-2 text-sm">
                            <p><strong class="text-rose-900">Tipe:</strong> {{ $ret->tipeLabel() }} (qty: {{ $ret->qty_bermasalah }})</p>
                            <p><strong class="text-rose-900">Solusi diminta:</strong> {{ $ret->solusiLabel() }}</p>
                            <p class="italic text-rose-800">"{{ $ret->deskripsi_masalah }}"</p>
                        </div>
                        @if(!empty($ret->foto_bukti) || $ret->video_bukti)
                            <div class="mt-3 flex gap-2">
                                @foreach(($ret->foto_bukti ?? []) as $foto)
                                    <a href="{{ asset('storage/'.$foto) }}" target="_blank" class="w-14 h-14 rounded-lg overflow-hidden border border-rose-200 hover:opacity-80">
                                        <img src="{{ asset('storage/'.$foto) }}" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                                @if($ret->video_bukti)
                                    <a href="{{ asset('storage/'.$ret->video_bukti) }}" target="_blank" class="w-14 h-14 rounded-lg bg-indigo-100 border border-indigo-200 flex items-center justify-center hover:opacity-80">🎥</a>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Respons Pemasok --}}
                    @if($ret->catatan_pemasok)
                        <div class="bg-orange-50 border border-orange-200 rounded-2xl p-4">
                            <p class="text-[11px] font-bold text-orange-700 uppercase mb-2">📤 Respons Pemasok</p>
                            <p class="text-sm text-orange-900">{{ $ret->catatan_pemasok }}</p>
                        </div>
                    @endif

                    {{-- Audit --}}
                    @if(!empty($ret->riwayat_audit))
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">📋 Audit Trail Lengkap</p>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 max-h-40 overflow-y-auto space-y-2">
                                @foreach($ret->riwayat_audit as $log)
                                    <div class="flex gap-3 text-xs">
                                        <span class="font-mono text-gray-400 shrink-0">{{ \Carbon\Carbon::parse($log['at'])->format('d/m H:i') }}</span>
                                        <span class="font-bold text-gray-700 uppercase shrink-0">{{ $log['actor'] }}</span>
                                        <span class="text-gray-600">{{ $log['event'] }}{{ ($log['detail'] ?? null) ? ' — '.$log['detail'] : '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Fraud Toggle --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-700">Status fraud flag: <strong class="{{ $ret->flag_fraud ? 'text-rose-700' : 'text-emerald-700' }}">{{ $ret->flag_fraud ? 'FLAGGED' : 'CLEAN' }}</strong></span>
                        <button wire:click="toggleFraud({{ $ret->id }})" class="text-xs font-bold px-3 py-1.5 rounded-lg {{ $ret->flag_fraud ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                            {{ $ret->flag_fraud ? 'Clear Flag' : 'Mark as Fraud' }}
                        </button>
                    </div>

                    {{-- ===== ARBITRASE (hanya jika escalated) ===== --}}
                    @if($ret->status === 'escalated_lkbb')
                        <hr class="border-gray-200">
                        <div class="bg-purple-50 border-2 border-purple-300 rounded-2xl p-5 space-y-4">
                            <h4 class="font-black text-purple-900 text-sm">⚖ Putuskan Sengketa</h4>

                            <div>
                                <p class="text-[11px] font-bold text-purple-700 uppercase mb-2">Keputusan Final</p>
                                <div class="space-y-2">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition bg-white
                                        {{ $keputusan === 'menangkan_merchant_refund' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 hover:border-emerald-300' }}">
                                        <input type="radio" wire:model="keputusan" value="menangkan_merchant_refund" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">Menangkan Merchant — Refund</p>
                                            <p class="text-xs text-gray-500">Pemasok wajib refund. Settlement diproses operasional LKBB.</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition bg-white
                                        {{ $keputusan === 'menangkan_merchant_replace' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 hover:border-emerald-300' }}">
                                        <input type="radio" wire:model="keputusan" value="menangkan_merchant_replace" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">Menangkan Merchant — Kirim Ulang</p>
                                            <p class="text-xs text-gray-500">Pemasok wajib kirim barang pengganti.</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition bg-white
                                        {{ $keputusan === 'menangkan_pemasok' ? 'border-rose-400 bg-rose-50' : 'border-gray-200 hover:border-rose-300' }}">
                                        <input type="radio" wire:model="keputusan" value="menangkan_pemasok" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">Menangkan Pemasok — Tolak Banding</p>
                                            <p class="text-xs text-gray-500">Klaim merchant tidak terbukti. Return dibatalkan.</p>
                                        </div>
                                    </label>
                                </div>
                                @error('keputusan') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-purple-700 uppercase mb-2">Alasan Keputusan (wajib, audit)</p>
                                <textarea wire:model="catatan" rows="4" class="w-full text-sm rounded-xl border-purple-200 focus:border-purple-500 focus:ring-purple-500 bg-white" placeholder="Cantumkan bukti & pertimbangan: kondisi foto, kronologi, kepatuhan kontrak..."></textarea>
                                @error('catatan') <span class="text-rose-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @elseif($ret->catatan_lkbb)
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <p class="text-[11px] font-bold text-blue-700 uppercase mb-1">📜 Keputusan LKBB Sebelumnya</p>
                            <p class="text-sm text-blue-900">{{ $ret->catatan_lkbb }}</p>
                            @if($ret->keputusan_resolusi)
                                <p class="text-xs text-blue-700 mt-2"><strong>Resolusi:</strong> {{ ucfirst(str_replace('_', ' ', $ret->keputusan_resolusi)) }}</p>
                            @endif
                        </div>
                    @endif

                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-between gap-3">
                    <button wire:click="closeDetail" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100">Tutup</button>
                    @if($ret->status === 'escalated_lkbb')
                        <button wire:click="putuskan"
                                wire:confirm="Putuskan sengketa? Keputusan ini FINAL dan tidak bisa dibatalkan."
                                class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold rounded-xl shadow-md">
                            ⚖ Putuskan Sengketa
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
