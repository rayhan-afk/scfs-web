<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\MerchantProfile;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    #[Computed]
    public function pendingMerchants()
    {
        // Mengambil profil merchant yang masih pending, beserta data usernya
        return MerchantProfile::with('user')
            ->where('status_verifikasi', 'pending')
            ->latest()
            ->paginate(10);
    }

    public function approveMerchant($profileId)
    {
        $profile = MerchantProfile::find($profileId);
        
        if($profile) {
            $profile->update([
                'status_verifikasi' => 'approved',
                'credit_limit' => 5000000 // Beri limit otomatis Rp 5 Juta
            ]);
            
            session()->flash('message', "Merchant {$profile->company_name} berhasil disetujui dengan Limit Rp 5 Juta!");
        }
    }

    public function rejectMerchant($profileId)
    {
        $profile = MerchantProfile::find($profileId);
        
        if($profile) {
            $profile->update([
                'status_verifikasi' => 'rejected',
            ]);
            
            session()->flash('error', "Pendaftaran Merchant {$profile->company_name} telah ditolak.");
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Merchant Baru</h1>
            <p class="text-gray-500 text-sm mt-1">Verifikasi data toko, dokumen, dan berikan limit pembiayaan.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Sukses!</strong> {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Ditolak.</strong> {{ session('error') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-400 border-b border-gray-100 uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Data Toko / Merchant</th>
                        <th class="py-3 px-4 font-semibold">Data Pemilik (User)</th>
                        <th class="py-3 px-4 font-semibold">Dokumen</th>
                        <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                    @forelse($this->pendingMerchants as $profile)
                    <tr class="hover:bg-gray-50 transition group">
                        
                        <td class="py-4 px-4">
                            <div class="font-bold text-gray-800 text-base">{{ $profile->company_name }}</div>
                            <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                {{ $profile->phone ?? 'Tidak ada No. HP' }}
                            </div>
                        </td>

                        <td class="py-4 px-4">
                            <div class="text-gray-800 font-medium">{{ $profile->user->name ?? 'User Terhapus' }}</div>
                            <div class="text-xs text-gray-400">{{ $profile->user->email ?? '-' }}</div>
                        </td>

                        <td class="py-4 px-4">
                            <div class="flex gap-2">
                                <button class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-semibold hover:bg-blue-100 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg> KTP
                                </button>
                                <button class="px-2 py-1 bg-purple-50 text-purple-600 rounded text-xs font-semibold hover:bg-purple-100 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> NIB
                                </button>
                            </div>
                        </td>

                        <td class="py-4 px-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button 
                                    wire:click="approveMerchant({{ $profile->id }})"
                                    wire:confirm="Setujui {{ $profile->company_name }} dan beri limit kredit Rp 5 Juta?"
                                    class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-bold hover:bg-green-600 transition shadow-sm">
                                    Setujui
                                </button>
                                
                                <button 
                                    wire:click="rejectMerchant({{ $profile->id }})"
                                    wire:confirm="Yakin ingin menolak pendaftaran ini?"
                                    class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs font-bold hover:bg-red-600 transition shadow-sm">
                                    Tolak
                                </button>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-10">
                            <div class="text-4xl mb-2">🎉</div>
                            <div class="text-gray-500 font-medium">Tidak ada antrean approval.</div>
                            <div class="text-xs text-gray-400 mt-1">Semua merchant baru sudah diverifikasi.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->pendingMerchants->links() }}
        </div>
    </div>
</div>