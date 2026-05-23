<div class="py-8 px-6 md:px-8 w-full space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 border-b border-gray-200 pb-5">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Riwayat Pengajuan PO</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau lifecycle pendanaan setiap PO yang Anda ajukan ke LKBB.</p>
        </div>
        <a href="{{ route('merchant.order') }}" wire:navigate class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md transition">
            + Buat PO Baru
        </a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm font-medium">
            ⚠ {{ session('error') }}
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor PO..." class="flex-1 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm py-2.5">
        <select wire:model.live="statusFilter" class="w-full md:w-64 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm py-2.5">
            <option value="semua">Semua Status</option>
            <option value="menunggu_pemasok">Menunggu Pemasok</option>
            <option value="menunggu_lkbb">Menunggu LKBB</option>
            <option value="revisi">⚠ Perlu Revisi</option>
            <option value="diproses_pemasok">Diproses Pemasok</option>
            <option value="dikirim">Dikirim</option>
            <option value="selesai">Selesai</option>
            <option value="ditolak">Ditolak Final</option>
        </select>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-[10px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-4">Nomor PO</th>
                        <th class="px-5 py-4">Pemasok</th>
                        <th class="px-5 py-4 text-right">Total Pendanaan</th>
                        <th class="px-5 py-4">Tanggal Diajukan</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($this->orders as $po)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-900 font-mono text-xs">{{ $po->nomor_order }}</div>
                                @if($po->tanggal_kebutuhan)
                                    <div class="text-[10px] text-gray-400 mt-0.5">Butuh: {{ \Carbon\Carbon::parse($po->tanggal_kebutuhan)->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-gray-900 text-sm">{{ $po->pemasok->pemasokProfile->nama_perusahaan ?? $po->pemasok->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 text-right font-extrabold text-gray-900">Rp {{ number_format($po->total_estimasi, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 text-gray-600 text-xs">{{ $po->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-4 text-center">
                                @switch($po->status)
                                    @case('menunggu_pemasok') <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Menunggu Pemasok</span> @break
                                    @case('menunggu_lkbb')    <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Menunggu LKBB</span> @break
                                    @case('revisi')          <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase animate-pulse">⚠ Perlu Revisi</span> @break
                                    @case('diproses_pemasok') <span class="bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Diproses</span> @break
                                    @case('dikirim')         <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Dikirim</span> @break
                                    @case('selesai')         <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Selesai</span> @break
                                    @case('ditolak')         <span class="bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Ditolak Final</span> @break
                                @endswitch
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button wire:click="openDetail({{ $po->id }})" class="px-4 py-2 text-xs font-bold rounded-lg transition
                                    {{ $po->status === 'revisi'
                                        ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-md'
                                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                                    {{ $po->status === 'revisi' ? 'Lihat & Ajukan Ulang' : 'Detail' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-gray-400 text-sm">Belum ada riwayat PO.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->orders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $this->orders->links() }}</div>
        @endif
    </div>

    {{-- Modal Detail / Review Ulang --}}
    @if($showModal && $this->selectedOrder)
        @php $po = $this->selectedOrder; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900">Detail PO</h3>
                        <p class="text-[11px] font-mono text-gray-500 mt-0.5">{{ $po->nomor_order }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-5">

                    {{-- Status Banner --}}
                    @if($po->status === 'revisi')
                        <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">⚠</span>
                                <div class="flex-1">
                                    <h4 class="font-black text-amber-900 mb-2">PO Diminta Revisi oleh LKBB</h4>
                                    <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider mb-2">Catatan LKBB:</p>
                                    <div class="bg-white border border-amber-100 rounded-xl p-3">
                                        <p class="text-sm text-amber-900 whitespace-pre-line leading-relaxed">{{ $po->catatan ?? '-' }}</p>
                                    </div>
                                    <p class="text-[11px] text-amber-700 mt-3 italic leading-relaxed">
                                        Perbaiki masalah yang disebutkan di atas (mis. lunasi tunggakan, lengkapi data),
                                        lalu klik <strong>Ajukan Review Ulang</strong> untuk dikirim kembali ke LKBB.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($po->status === 'ditolak')
                        <div class="bg-rose-50 border-2 border-rose-200 rounded-2xl p-5">
                            <h4 class="font-black text-rose-900 mb-2">❌ PO Ditolak Final</h4>
                            <p class="text-[11px] font-bold text-rose-700 uppercase mb-1">Alasan:</p>
                            <p class="text-sm text-rose-800 whitespace-pre-line">{{ $po->catatan ?? '-' }}</p>
                            <p class="text-[11px] text-rose-600 mt-2 italic">PO ini ditutup permanen — silakan buat PO baru jika masih dibutuhkan.</p>
                        </div>
                    @elseif($po->catatan)
                        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                            <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">Catatan</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $po->catatan }}</p>
                        </div>
                    @endif

                    {{-- Ringkasan --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Pemasok</p>
                            <p class="text-sm font-bold text-gray-900">{{ $po->pemasok->pemasokProfile->nama_perusahaan ?? $po->pemasok->name ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Total Pendanaan</p>
                            <p class="text-sm font-extrabold text-emerald-700">Rp {{ number_format($po->total_estimasi, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal Pengajuan</p>
                            <p class="text-sm text-gray-900">{{ $po->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal Kebutuhan</p>
                            <p class="text-sm text-gray-900">{{ $po->tanggal_kebutuhan ? \Carbon\Carbon::parse($po->tanggal_kebutuhan)->format('d M Y') : '-' }}</p>
                        </div>
                    </div>

                    {{-- Rincian Item --}}
                    <div>
                        <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">Rincian Barang</p>
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-bold">Produk</th>
                                        <th class="px-4 py-2 text-center font-bold">Qty</th>
                                        <th class="px-4 py-2 text-right font-bold">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($po->details as $d)
                                        @php $sub = ($d->harga_modal_snapshot ?? 0) * $d->qty + ($d->margin_pemasok_snapshot ?? 0) * $d->qty; @endphp
                                        <tr>
                                            <td class="px-4 py-2 text-gray-900">{{ $d->nama_produk_snapshot ?? '-' }}</td>
                                            <td class="px-4 py-2 text-center text-gray-700">{{ $d->qty }}</td>
                                            <td class="px-4 py-2 text-right text-gray-900 font-medium">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-between items-center gap-3">
                    <button wire:click="closeDetail" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition">
                        Tutup
                    </button>

                    @if($po->status === 'revisi')
                        <button wire:click="ajukanReviewUlang"
                                wire:confirm="Pastikan masalah yang disebutkan LKBB sudah diperbaiki. Ajukan ulang sekarang?"
                                wire:loading.attr="disabled"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 shadow-md transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="ajukanReviewUlang">⟲ Ajukan Review Ulang</span>
                            <span wire:loading wire:target="ajukanReviewUlang">Memproses...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
