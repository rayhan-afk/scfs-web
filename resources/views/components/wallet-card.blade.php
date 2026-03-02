@props(['wallet', 'theme', 'isSystem' => false])

<div class="{{ $isSystem ? $theme['bg'] : 'bg-white border border-gray-200' }} rounded-2xl p-6 shadow-sm relative overflow-hidden group transition hover:-translate-y-1">
    <div class="flex justify-between items-start mb-4">
        <div class="p-3 rounded-lg {{ $isSystem ? 'bg-white/20' : 'bg-gray-100' }}">
             <svg class="w-6 h-6 {{ $isSystem ? $theme['text'] : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $theme['icon'] }}"></path>
            </svg>
        </div>
        @if($isSystem)
        <span class="px-2 py-1 rounded text-xs font-bold {{ $isSystem ? 'bg-white/20 text-white' : 'bg-gray-100' }}">
            SYSTEM
        </span>
        @endif
    </div>

    <p class="text-sm {{ $isSystem && $wallet->type == 'LKBB_MASTER' ? 'text-blue-100' : 'text-gray-500' }} font-medium">
        {{ $theme['label'] }}
    </p>
    
    <h3 class="text-2xl font-bold {{ $isSystem && $wallet->type == 'LKBB_MASTER' ? 'text-white' : 'text-gray-800' }} mt-1">
        Rp {{ number_format($wallet->balance, 0, ',', '.') }}
    </h3>

    <div class="mt-6 pt-4 border-t {{ $isSystem ? 'border-white/10' : 'border-gray-100' }}">
        <button wire:click="openTopUp({{ $wallet->id }})" 
                class="w-full py-2 px-4 rounded-lg text-sm font-semibold transition flex justify-center items-center gap-2
                {{ $isSystem && $wallet->type == 'LKBB_MASTER' 
                    ? 'bg-white text-blue-600 hover:bg-blue-50' 
                    : 'bg-gray-900 text-white hover:bg-gray-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Suntik / Tambah Saldo
        </button>
    </div>
</div>