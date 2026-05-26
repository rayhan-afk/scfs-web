@props([
    'name' => null,
    'phone' => null,
    'resi' => null,
    'status' => 'dikirim',   // dikirim|selesai|other
    'variant' => 'merchant', // merchant|pemasok
])

@php
    $waUrl = \App\Services\Tracking\TrackingTimelineService::normalizeWhatsappUrl($phone);
    $variantMap = [
        'merchant' => ['avatarGrad' => 'from-emerald-500 to-lime-500', 'avatarShadow' => 'shadow-emerald-200', 'glow' => 'from-emerald-100 to-lime-100', 'accentLine' => 'from-emerald-400 via-lime-500 to-emerald-400'],
        'pemasok'  => ['avatarGrad' => 'from-orange-500 to-amber-500', 'avatarShadow' => 'shadow-orange-200', 'glow' => 'from-orange-100 to-amber-100', 'accentLine' => 'from-orange-400 via-amber-500 to-orange-400'],
    ];
    $v = $variantMap[$variant] ?? $variantMap['merchant'];
    $isMoving = $status === 'dikirim';
    $isArrived = $status === 'selesai';
@endphp

@if($name)
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-white/60 bg-white/80 backdrop-blur-xl shadow-[0_4px_20px_-8px_rgba(15,23,42,0.1)]']) }}>
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r {{ $v['accentLine'] }}"></div>
        <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-gradient-to-br {{ $v['glow'] }} opacity-60 blur-2xl"></div>

        <div class="relative flex items-center gap-3 p-3">
            {{-- Avatar inisial + ikon motor sebagai badge --}}
            <div class="relative shrink-0">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br {{ $v['avatarGrad'] }} text-white shadow-md {{ $v['avatarShadow'] }} ring-2 ring-white">
                    <span class="text-base font-black tracking-tight">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
                </div>
                <div class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-white text-gray-700 shadow">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="5.5" cy="17.5" r="2.5"/>
                        <circle cx="18.5" cy="17.5" r="2.5"/>
                        <path d="M8 17.5h8"/>
                        <path d="M13 17.5V8h4l2 4"/>
                        <path d="M5.5 15 7 9h4l1.5 6"/>
                    </svg>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-black leading-tight text-gray-900">{{ $name }}</p>
                <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[10px] font-bold text-gray-500">
                    @if($isMoving)
                        <span class="inline-flex items-center gap-1 text-emerald-600">
                            <span class="relative flex h-1.5 w-1.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            </span>
                            Menuju lokasi
                        </span>
                    @elseif($isArrived)
                        <span class="inline-flex items-center gap-1 text-emerald-600">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            Telah tiba
                        </span>
                    @else
                        <span>Kurir</span>
                    @endif
                    @if($resi)
                        <span class="h-1 w-1 rounded-full bg-gray-300"></span>
                        <span class="font-mono uppercase tracking-wider text-gray-600">{{ $resi }}</span>
                    @endif
                </div>
                @if($phone)
                    <p class="mt-0.5 text-[10px] font-bold text-gray-400">{{ $phone }}</p>
                @endif
            </div>

            @if($waUrl)
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" title="Chat WhatsApp {{ $name }}"
                   class="group flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white shadow-md shadow-emerald-200 transition-all hover:bg-[#1eb058] active:scale-95">
                    {{-- WhatsApp glyph --}}
                    <svg class="h-5 w-5" viewBox="0 0 32 32" fill="currentColor">
                        <path d="M16.001 3C9.373 3 4 8.373 4 15c0 2.39.69 4.62 1.886 6.49L4 29l7.71-1.83A12 12 0 1 0 16 3zm0 21.8a9.78 9.78 0 0 1-4.985-1.36l-.358-.214-4.578 1.087 1.108-4.46-.234-.37A9.79 9.79 0 1 1 25.79 15 9.78 9.78 0 0 1 16 24.8zm5.39-7.32c-.296-.148-1.75-.864-2.022-.962-.272-.099-.47-.148-.668.148-.198.296-.766.962-.94 1.16-.173.198-.346.222-.643.074-.296-.148-1.252-.46-2.385-1.47-.881-.785-1.476-1.755-1.65-2.052-.173-.297-.018-.457.13-.605.133-.132.297-.346.445-.519.148-.173.198-.297.297-.495.099-.198.05-.371-.025-.52-.075-.148-.668-1.61-.916-2.205-.241-.58-.486-.501-.668-.51-.173-.008-.371-.01-.57-.01a1.1 1.1 0 0 0-.792.371c-.272.297-1.04 1.016-1.04 2.478 0 1.462 1.066 2.875 1.215 3.073.148.198 2.098 3.203 5.083 4.494 2.985 1.291 2.985.86 3.523.806.538-.05 1.75-.715 1.996-1.404.247-.69.247-1.28.173-1.404-.074-.124-.272-.198-.569-.346z"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-gray-200 bg-gray-50/60 px-3 py-3.5 text-center']) }}>
        <p class="text-xs font-bold text-gray-400">Belum ada info kurir</p>
    </div>
@endif
