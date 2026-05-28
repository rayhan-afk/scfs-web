@props([
    'percentage' => 0,
    'variant' => 'merchant', // merchant|pemasok
    'label' => null,
    'sublabel' => null,
])

@php
    $variantMap = [
        'merchant' => 'from-emerald-400 via-emerald-500 to-lime-500',
        'pemasok'  => 'from-orange-400 via-amber-500 to-orange-600',
    ];
    $bar = $variantMap[$variant] ?? $variantMap['merchant'];
    $pct = max(0, min(100, (int) $percentage));
@endphp

<div {{ $attributes }}>
    @if($label || $sublabel)
        <div class="flex items-end justify-between mb-1.5">
            @if($label)<p class="text-[10px] font-black uppercase tracking-widest text-gray-600">{{ $label }}</p>@endif
            @if($sublabel)<p class="text-[10px] font-bold text-gray-400">{{ $sublabel }}</p>@endif
        </div>
    @endif
    <div class="relative h-2 w-full overflow-hidden rounded-full bg-gray-100">
        <div class="relative h-full overflow-hidden rounded-full bg-gradient-to-r {{ $bar }} transition-all duration-700 ease-out"
             style="width: {{ $pct }}%;">
            <div class="scfs-shimmer absolute inset-y-0 -inset-x-1/2 bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
        </div>
    </div>
</div>
