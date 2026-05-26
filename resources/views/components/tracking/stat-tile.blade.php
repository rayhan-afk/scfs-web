@props([
    'label' => '',
    'value' => '0',
    'caption' => null,
    'accent' => 'emerald', // emerald|orange|amber|rose|sky|lime
    'icon' => null,        // SVG markup as string
])

@php
    // Tailwind JIT butuh literal class. Pakai map agar varian dinamis tetap di-compile.
    $accentMap = [
        'emerald' => ['iconBg' => 'bg-emerald-50',  'iconText' => 'text-emerald-600',  'ring' => 'ring-emerald-100',  'glow' => 'bg-emerald-200/50'],
        'orange'  => ['iconBg' => 'bg-orange-50',   'iconText' => 'text-orange-600',   'ring' => 'ring-orange-100',   'glow' => 'bg-orange-200/50'],
        'amber'   => ['iconBg' => 'bg-amber-50',    'iconText' => 'text-amber-600',    'ring' => 'ring-amber-100',    'glow' => 'bg-amber-200/50'],
        'rose'    => ['iconBg' => 'bg-rose-50',     'iconText' => 'text-rose-600',     'ring' => 'ring-rose-100',     'glow' => 'bg-rose-200/50'],
        'sky'     => ['iconBg' => 'bg-sky-50',      'iconText' => 'text-sky-600',      'ring' => 'ring-sky-100',      'glow' => 'bg-sky-200/50'],
        'lime'    => ['iconBg' => 'bg-lime-50',     'iconText' => 'text-lime-600',     'ring' => 'ring-lime-100',     'glow' => 'bg-lime-200/50'],
    ];
    $c = $accentMap[$accent] ?? $accentMap['emerald'];
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-white/60 bg-white/80 backdrop-blur-xl p-4 shadow-[0_4px_20px_-8px_rgba(15,23,42,0.08)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_30px_-12px_rgba(15,23,42,0.15)]']) }}>
    {{-- Soft glow blob --}}
    <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full {{ $c['glow'] }} blur-2xl"></div>

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ $label }}</p>
            <p class="mt-1 text-2xl font-black leading-none text-gray-900">{{ $value }}</p>
            @if($caption)
                <p class="mt-1 text-[10px] font-bold text-gray-400">{{ $caption }}</p>
            @endif
        </div>
        @if($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $c['iconBg'] }} {{ $c['iconText'] }} ring-1 {{ $c['ring'] }}">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
