@props([
    'events' => collect(),
    'variant' => 'merchant', // merchant|pemasok
])

@php
    $variantMap = [
        'merchant' => ['nodeBg' => 'bg-emerald-500', 'nodeShadow' => 'shadow-emerald-200', 'line' => 'from-emerald-500 via-emerald-400 to-emerald-200', 'glow' => 'bg-emerald-400'],
        'pemasok'  => ['nodeBg' => 'bg-orange-500',  'nodeShadow' => 'shadow-orange-200',  'line' => 'from-orange-500 via-orange-400 to-orange-200',  'glow' => 'bg-orange-400'],
    ];
    $v = $variantMap[$variant] ?? $variantMap['merchant'];

    $iconMap = [
        'shopping-bag' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'package'      => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
        'truck'        => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',
        'check-circle' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    ];
@endphp

<ol class="relative">
    @foreach($events as $i => $event)
        @php
            $state = $event['state'];
            $isCompleted = $state === 'completed';
            $isActive = $state === 'active';
            $isLast = $loop->last;
            $icon = $iconMap[$event['icon']] ?? $iconMap['package'];
        @endphp
        <li class="scfs-timeline-item relative flex gap-4 pb-6 last:pb-0" style="animation-delay: {{ $i * 80 }}ms;">
            {{-- Connector line (behind nodes) --}}
            @unless($isLast)
                <span @class([
                    'absolute left-[17px] top-9 -bottom-0 w-px',
                    'bg-gradient-to-b ' . $v['line'] => $isCompleted,
                    'border-l border-dashed border-gray-300' => ! $isCompleted,
                ])></span>
            @endunless

            {{-- Node --}}
            <div class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center">
                @if($isActive)
                    <span class="absolute inset-0 -m-1 rounded-full {{ $v['glow'] }} opacity-30 animate-ping"></span>
                @endif
                <div @class([
                    'relative flex h-9 w-9 items-center justify-center rounded-full ring-4 ring-white transition-all',
                    $v['nodeBg'] . ' text-white shadow-md ' . $v['nodeShadow'] => $isCompleted || $isActive,
                    'bg-gray-50 text-gray-400 border border-dashed border-gray-300' => $state === 'pending',
                ])>
                    {!! $icon !!}
                </div>
            </div>

            {{-- Content --}}
            <div class="flex-1 pt-1">
                <div class="flex items-baseline justify-between gap-2">
                    <p @class([
                        'text-sm font-black leading-tight',
                        'text-gray-900' => $state !== 'pending',
                        'text-gray-400' => $state === 'pending',
                    ])>{{ $event['label'] }}</p>
                    @if(!empty($event['timestamp']))
                        <span class="whitespace-nowrap text-[10px] font-bold text-gray-400">
                            {{ \Carbon\Carbon::parse($event['timestamp'])->format('d M · H:i') }}
                        </span>
                    @endif
                </div>
                <p @class([
                    'mt-0.5 text-xs leading-snug font-medium',
                    'text-gray-500' => $state !== 'pending',
                    'text-gray-400' => $state === 'pending',
                ])>{{ $event['description'] }}</p>

                @if(!empty($event['meta']['kurir']))
                    <div class="mt-2 inline-flex flex-wrap items-center gap-2 rounded-xl border border-gray-100 bg-white px-2.5 py-1.5 shadow-sm">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-900 text-[9px] font-black text-white">{{ strtoupper(mb_substr($event['meta']['kurir'], 0, 1)) }}</span>
                        <span class="text-[11px] font-bold text-gray-700">{{ $event['meta']['kurir'] }}</span>
                        @if(!empty($event['meta']['resi']))
                            <span class="h-0.5 w-0.5 rounded-full bg-gray-300"></span>
                            <span class="font-mono text-[10px] uppercase tracking-wider text-gray-500">{{ $event['meta']['resi'] }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </li>
    @endforeach
</ol>
