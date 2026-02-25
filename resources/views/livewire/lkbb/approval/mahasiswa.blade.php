<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\StudentProfile;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.lkbb')] class extends Component {
    use WithPagination;

    #[Computed]
    public function pendingStudents()
    {
        return StudentProfile::with('user')
            ->where('status_verifikasi', 'pending')
            ->latest()
            ->paginate(10);
    }

    public function approveStudent($profileId)
    {
        $profile = StudentProfile::with('user')->find($profileId);
        
        if(!$profile) return;

        try {
            DB::transaction(function () use ($profile) {
                // 1. Ubah status menjadi approved
                $profile->update(['status_verifikasi' => 'approved']);
                
                // 2. Buatkan Dompet Digital (Wallet) otomatis untuk Mahasiswa
                Wallet::firstOrCreate(
                    ['user_id' => $profile->user_id, 'type' => 'USER_WALLET'],
                    [
                        'account_number' => 'MHS-' . strtoupper(Str::random(6)),
                        'balance' => 0,
                        'is_active' => true,
                    ]
                );
            });
            
            session()->flash('message', "Mahasiswa {$profile->user->name} (NIM: {$profile->nim}) disetujui & Dompet Digital berhasil dibuat!");
            
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', "Terjadi kesalahan sistem saat memproses approval.");
        }
    }

    public function rejectStudent($profileId)
    {
        $profile = StudentProfile::find($profileId);
        
        if($profile) {
            $profile->update(['status_verifikasi' => 'rejected']);
            session()->flash('error', "Pendaftaran Mahasiswa dengan NIM {$profile->nim} telah ditolak.");
        }
    }
}; ?>

<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Mahasiswa Beasiswa</h1>
            <p class="text-gray-500 text-sm mt-1">Verifikasi identitas mahasiswa untuk pembukaan dompet beasiswa digital.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Sukses!</strong> {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm">
            <strong class="font-bold">Info:</strong> {{ session('error') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-400 border-b border-gray-100 uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Data Mahasiswa</th>
                        <th class="py-3 px-4 font-semibold">Akademik</th>
                        <th class="py-3 px-4 font-semibold">Dokumen (KTM)</th>
                        <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                    @forelse($this->pendingStudents as $profile)
                    <tr class="hover:bg-gray-50 transition group">
                        
                        <td class="py-4 px-4">
                            <div class="font-bold text-gray-800 text-base">{{ $profile->user->name ?? 'User Terhapus' }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $profile->user->email ?? '-' }}</div>
                        </td>

                        <td class="py-4 px-4">
                            <div class="text-gray-800 font-bold font-mono text-xs mb-1">NIM: {{ $profile->nim }}</div>
                            <div class="text-xs text-gray-500">{{ $profile->university_name }}</div>
                            <div class="text-[10px] text-gray-400 uppercase mt-0.5">{{ $profile->faculty ?? 'Fakultas Umum' }}</div>
                        </td>

                        <td class="py-4 px-4">
                            <button class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-100 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg> 
                                Lihat KTM
                            </button>
                        </td>

                        <td class="py-4 px-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button 
                                    wire:click="approveStudent({{ $profile->id }})"
                                    wire:confirm="Setujui {{ $profile->user->name ?? 'Mahasiswa ini' }} dan buatkan dompet digitalnya?"
                                    class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-bold hover:bg-green-600 transition shadow-sm">
                                    Setujui
                                </button>
                                
                                <button 
                                    wire:click="rejectStudent({{ $profile->id }})"
                                    wire:confirm="Tolak mahasiswa ini?"
                                    class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs font-bold hover:bg-red-600 transition shadow-sm">
                                    Tolak
                                </button>
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-10">
                            <div class="text-4xl mb-2">🎓</div>
                            <div class="text-gray-500 font-medium">Tidak ada data mahasiswa baru.</div>
                            <div class="text-xs text-gray-400 mt-1">Antrean verifikasi kosong.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->pendingStudents->links() }}
        </div>
    </div>
</div>