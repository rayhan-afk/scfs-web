<?php

use App\Models\SupplyOrder;
use function Livewire\Volt\{layout, with, state, usesPagination};

// 1. Mengaktifkan Fitur Bawaan
usesPagination();
layout('layouts.lkbb');

// 2. Mendefinisikan State (Pengganti public property)
state(['search' => '']);

// 3. Mengirim Data ke Blade (Pengganti render/with class)
with(function () {
    return [
        'orders' => SupplyOrder::with(['merchant.merchantProfile', 'pemasok.pemasokProfile'])
            ->whereIn('status', ['diproses_pemasok', 'dikirim', 'selesai', 'ditolak'])
            ->when($this->search, function ($query) {
                $query->where('nomor_order', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(15),
    ];
});

?>

<div class="p-6 max-w-7xl mx-auto space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Riwayat Pendanaan PO</h1>
            <p class="text-sm font-medium text-gray-500 mt-1">Laporan seluruh aliran dana investasi ke Pemasok.</p>
        </div>
        
        <div class="relative w-full sm:w-64">
            <input type="text" wire:model.live="search" placeholder="Cari No PO..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-indigo-500 shadow-sm font-bold text-gray-600">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[10px] uppercase tracking-widest text-gray-400">
                        <th class="px-6 py-4 font-bold">Data Transaksi</th>
                        <th class="px-6 py-4 font-bold">Rantai Pasok</th>
                        <th class="px-6 py-4 font-bold text-right">Nilai Pencairan</th>
                        <th class="px-6 py-4 font-bold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    {{-- DI SINI VARIABEL $orders PASTI TERBACA --}}
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-black text-gray-800 text-sm">{{ $order->nomor_order }}</div>
                                <div class="text-[11px] font-bold text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y - H:i') }}</div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-1/2">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Kantin / Merchant</p>
                                        <p class="text-xs font-black text-gray-800 truncate">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</p>
                                    </div>
                                    <svg class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    <div class="w-1/2 text-right">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Pemasok</p>
                                        <p class="text-xs font-black text-gray-800 truncate">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <span class="font-black text-indigo-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($order->status === 'ditolak')
                                    <span class="inline-flex px-3 py-1 bg-rose-50 text-rose-600 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-rose-200">Ditolak</span>
                                @elseif($order->status === 'selesai')
                                    <span class="inline-flex px-3 py-1 bg-green-50 text-green-600 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-green-200">Selesai Lunas</span>
                                @else
                                    <span class="inline-flex px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-emerald-200">Dana Dicairkan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-16 text-center">
                                <div class="text-3xl mb-2 opacity-50">🗄️</div>
                                <p class="text-gray-900 font-black text-sm">Riwayat Masih Kosong</p>
                                <p class="text-xs font-bold text-gray-400 mt-1">Belum ada PO yang Anda danai atau tolak.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-50">
            {{ $orders->links() }}
        </div>
    </div>
</div>