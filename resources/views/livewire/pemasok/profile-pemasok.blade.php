<div class="max-w-4xl mx-auto py-6 space-y-6">
    
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow-sm flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <div class="h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold uppercase">
                {{ substr($nama_usaha, 0, 1) ?: 'P' }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $nama_usaha ?: 'Nama Usaha Belum Diatur' }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Terverifikasi
                </span>
            </div>
        </div>
    </div>

    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('informasi')" class="{{ $activeTab === 'informasi' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Informasi Usaha
            </button>
            <button wire:click="switchTab('dokumen')" class="{{ $activeTab === 'dokumen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Dokumen & Rekening
            </button>
            <button wire:click="switchTab('keamanan')" class="{{ $activeTab === 'keamanan' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Keamanan Akun
            </button>
        </nav>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm">
        
        @if($activeTab === 'informasi')
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Detail Perusahaan</h3>
                <button wire:click="toggleEdit" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                    {{ $isEditing ? 'Batal Edit' : 'Edit Profil' }}
                </button>
            </div>

            <form wire:submit.prevent="simpanInformasi" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Perusahaan/Grosir</label>
                    @if($isEditing)
                        <input type="text" wire:model="nama_usaha" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('nama_usaha') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @else
                        <p class="mt-1 text-gray-900 bg-gray-50 p-2 rounded-md border border-transparent">{{ $nama_usaha ?: '-' }}</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor HP / WhatsApp Aktif</label>
                    @if($isEditing)
                        <input type="text" wire:model="no_hp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('no_hp') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @else
                        <p class="mt-1 text-gray-900 bg-gray-50 p-2 rounded-md border border-transparent">{{ $no_hp ?: '-' }}</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat Gudang Utama</label>
                    @if($isEditing)
                        <textarea wire:model="alamat_gudang" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('alamat_gudang') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @else
                        <p class="mt-1 text-gray-900 bg-gray-50 p-2 rounded-md border border-transparent min-h-[40px]">{{ $alamat_gudang ?: '-' }}</p>
                    @endif
                </div>

                @if($isEditing)
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">Simpan Perubahan</button>
                    </div>
                @endif
            </form>
        @endif

        @if($activeTab === 'dokumen')
            <div class="space-y-8">
                
                {{-- BAHAGIAN 1: REKENING PENCAIRAN --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Rekening Pencairan</h3>
                    <div class="flex items-center justify-between bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                        <div>
                            <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Nommor Akun Aktif</p>
                            <p class="text-xl font-black text-gray-900 tracking-wider">
                                @if($info_rekening && strlen($info_rekening) > 6)
                                    {{ substr($info_rekening, 0, strpos($info_rekening, '-')) }} - 
                                    {{ str_repeat('*', 4) . substr($info_rekening, -4) }}
                                @else
                                    {{ $info_rekening ?: 'Belum diatur' }}
                                @endif
                            </p>
                        </div>
                        <button wire:click="$set('showRekeningModal', true)" class="text-sm bg-white border border-blue-200 text-blue-600 font-bold px-4 py-2 rounded-lg hover:bg-blue-600 hover:text-white transition-colors shadow-sm">
                            Ubah Rekening
                        </button>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- BAHAGIAN 2: DOKUMEN LEGALITAS --}}
                <form wire:submit.prevent="simpanDokumen">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Dokumen Pemasok</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Upload KTP --}}
                        <div class="border border-gray-200 rounded-xl p-5 relative">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Foto KTP Pemilik</label>
                            
                            <div class="w-full h-40 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden mb-3 relative">
                                @if($foto_ktp_baru)
                                    <img src="{{ $foto_ktp_baru->temporaryUrl() }}" class="object-cover w-full h-full">
                                    <span class="absolute top-2 right-2 bg-green-500 text-white text-[10px] px-2 py-1 rounded font-bold">Baru</span>
                                @elseif($foto_ktp_lama)
                                    <img src="{{ asset('storage/' . $foto_ktp_lama) }}" class="object-cover w-full h-full">
                                @else
                                    <span class="text-gray-400 text-sm">Tidak Ada File KTP</span>
                                @endif
                            </div>

                            <input type="file" wire:model="foto_ktp_baru" id="upload-ktp" class="hidden" accept="image/*">
                            <label for="upload-ktp" class="w-full block text-center cursor-pointer bg-gray-100 px-4 py-2 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-200 transition">
                                Ganti KTP
                            </label>
                            <div wire:loading wire:target="foto_ktp_baru" class="text-[10px] text-blue-500 mt-1 text-center w-full">Sedang memuat...</div>
                            @error('foto_ktp_baru') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        {{-- Upload Foto Gudang/Usaha --}}
                        <div class="border border-gray-200 rounded-xl p-5 relative">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Foto Gudang / Usaha</label>
                            
                            <div class="w-full h-40 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden mb-3 relative">
                                @if($foto_usaha_baru)
                                    <img src="{{ $foto_usaha_baru->temporaryUrl() }}" class="object-cover w-full h-full">
                                    <span class="absolute top-2 right-2 bg-green-500 text-white text-[10px] px-2 py-1 rounded font-bold">Baru</span>
                                @elseif($foto_usaha_lama)
                                    <img src="{{ asset('storage/' . $foto_usaha_lama) }}" class="object-cover w-full h-full">
                                @else
                                    <span class="text-gray-400 text-sm">Tidak Ada File Foto Usaha</span>
                                @endif
                            </div>

                            <input type="file" wire:model="foto_usaha_baru" id="upload-usaha" class="hidden" accept="image/*">
                            <label for="upload-usaha" class="w-full block text-center cursor-pointer bg-gray-100 px-4 py-2 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-200 transition">
                                Ganti Foto Usaha
                            </label>
                            <div wire:loading wire:target="foto_usaha_baru" class="text-[10px] text-blue-500 mt-1 text-center w-full">Sedang memuat...</div>
                            @error('foto_usaha_baru') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($foto_ktp_baru || $foto_usaha_baru)
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2.5 rounded-lg hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                            Simpan Dokumen Terbaru
                        </button>
                    </div>
                    @endif
                </form>
            </div>
        @endif

       @if($activeTab === 'keamanan')
            <div class="space-y-6 max-w-xl">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Perbarui Kata Sandi</h3>
                    <p class="text-sm text-gray-500 mb-6 border-b border-gray-100 pb-4">
                        Pastikan akun Anda menggunakan kata sandi acak yang panjang agar tetap aman.
                    </p>
                </div>

                <form wire:submit.prevent="updatePassword" class="space-y-5">
                    
                    {{-- Password Lama --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-2">
                            Kata Sandi Saat Ini
                        </label>
                        <input type="password" wire:model="current_password" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 text-sm" placeholder="Masukkan kata sandi lama...">
                        @error('current_password') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Password Baru --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-2">
                            Kata Sandi Baru
                        </label>
                        <input type="password" wire:model="password" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 text-sm" placeholder="Minimal 8 karakter...">
                        @error('password') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Konfirmasi Password Baru --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-widest mb-2">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <input type="password" wire:model="password_confirmation" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 text-sm" placeholder="Ketik ulang kata sandi baru...">
                    </div>

                    <div class="pt-4 flex items-center gap-4">
                        <button type="submit" class="bg-gray-800 text-white font-bold px-6 py-2.5 rounded-xl hover:bg-gray-900 transition-colors shadow-lg shadow-gray-200">
                            Simpan Kata Sandi
                        </button>
                        
                        {{-- Indikator Loading saat proses simpan --}}
                        <div wire:loading wire:target="updatePassword" class="text-sm text-gray-500 flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Menyimpan...
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>

    @if($showRekeningModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 transition-opacity">
            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full m-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Ubah Akaun Pencairan</h3>
                    <button wire:click="$set('showRekeningModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                
                <p class="text-sm text-gray-500 mb-6 bg-yellow-50 p-3 rounded-lg border border-yellow-100 text-yellow-800">
                    🔒 Demi keselamatan dana anda, silahkan masukkan passwoard akun anda
                </p>
                
                <form wire:submit.prevent="ubahRekening">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Passwoard Anda</label>
                            <input type="password" wire:model="password_konfirmasi" class="w-full rounded-xl border-gray-300 focus:ring-blue-500 py-3 text-sm" placeholder="Masukkan kata laluan...">
                            @error('password_konfirmasi') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <hr class="border-gray-100 my-2">

                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-1">
                                <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Bank</label>
                                <select wire:model="nama_bank_baru" class="w-full rounded-xl border-blue-100 bg-blue-50/30 py-3 text-sm focus:ring-blue-500">
                                    <option value="">Pilih</option>
                                    @foreach($daftar_bank as $bank)
                                        <option value="{{ $bank }}">{{ $bank }}</option>
                                    @endforeach
                                </select>
                                @error('nama_bank_baru') <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Nomor Akun Rekening</label>
                                <input type="number" wire:model="nomor_rekening_baru" placeholder="Cth: 8830xxxx" class="w-full rounded-xl border-blue-100 bg-blue-50/30 py-3 text-sm focus:ring-blue-500">
                                @error('nomor_rekening_baru') <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-8">
                        <button type="button" wire:click="$set('showRekeningModal', false)" class="px-5 py-2.5 text-gray-700 bg-gray-100 rounded-xl font-bold hover:bg-gray-200 transition-colors">Batal</button>
                        <button type="submit" class="px-5 py-2.5 text-white bg-blue-600 rounded-xl font-bold hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">Simpan Akun</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>