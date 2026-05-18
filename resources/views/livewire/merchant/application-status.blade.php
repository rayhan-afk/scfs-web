<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')]
class extends Component {

    public $merchant;
    public $status;

    public function mount()
    {
        $this->merchant = Auth::user()->merchantProfile;
        $this->status = $this->merchant?->status_verifikasi;
    }

};

?>

<div class="max-w-5xl mx-auto py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-800">Status Pengajuan Merchant</h1>
        <p class="text-gray-500 mt-2">Pantau proses verifikasi merchant SCFS Anda secara realtime.</p>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8">

            <div class="flex items-center gap-4 mb-8">

                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl
                    {{ $status === 'disetujui' ? 'bg-green-100' : ($status === 'ditolak' ? 'bg-red-100' : 'bg-yellow-100') }}">

                    {{ $status === 'disetujui' ? '✅' : ($status === 'ditolak' ? '❌' : '⏳') }}

                </div>

                <div>
                    <h2 class="text-2xl font-black text-gray-800">
                        {{ $status === 'disetujui'
                            ? 'Merchant Disetujui'
                            : ($status === 'ditolak'
                                ? 'Pengajuan Ditolak'
                                : 'Menunggu Verifikasi') }}
                    </h2>

                    <p class="text-gray-500 mt-1">
                        {{ $status === 'disetujui'
                            ? 'Merchant Anda sudah aktif dan dapat digunakan.'
                            : ($status === 'ditolak'
                                ? 'Silakan revisi data pengajuan merchant Anda.'
                                : 'Tim LKBB sedang memverifikasi data merchant Anda.') }}
                    </p>
                </div>

            </div>

            @if($status === 'ditolak')
                <div class="mb-8 bg-red-50 border border-red-200 rounded-2xl p-5">
                    <h3 class="font-bold text-red-700 mb-2">Alasan Penolakan</h3>

                    <p class="text-sm text-red-600 leading-relaxed">
                        {{ $merchant->catatan_penolakan ?? 'Tidak ada catatan.' }}
                    </p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Checklist Dokumen</h3>

                    <div class="space-y-3 text-sm">

                        <div class="flex items-center justify-between">
                            <span>Foto KTP</span>
                            <span>{{ $merchant?->foto_ktp ? '✅' : '❌' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>Foto Kantin</span>
                            <span>{{ $merchant?->foto_kantin ? '✅' : '❌' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>NIK</span>
                            <span>{{ $merchant?->nik ? '✅' : '❌' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>No HP</span>
                            <span>{{ $merchant?->no_hp ? '✅' : '❌' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>Rekening</span>
                            <span>{{ $merchant?->no_rekening ? '✅' : '❌' }}</span>
                        </div>

                    </div>
                </div>

                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Informasi Merchant</h3>

                    <div class="space-y-3 text-sm">

                        <div class="flex items-center justify-between">
                            <span>Nama Kantin</span>
                            <span class="font-semibold">{{ $merchant?->nama_kantin ?? '-' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>Pemilik</span>
                            <span class="font-semibold">{{ $merchant?->nama_pemilik ?? '-' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>Lokasi</span>
                            <span class="font-semibold">{{ $merchant?->lokasi_blok ?? '-' }}</span>
                        </div>

                    </div>
                </div>

            </div>

            @if($status === 'ditolak')
                <div class="mt-8">
                    <a href="{{ route('merchant.profile') }}"
                       class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl transition shadow-lg shadow-red-100">
                        Revisi Pengajuan
                    </a>
                </div>
            @endif

        </div>
    </div>
</div>