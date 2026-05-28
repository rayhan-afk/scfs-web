# Pengiriman & Penerimaan Tracking Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign Pemasok (`pengiriman-logistik`) and Merchant (`penerimaan`) pages into premium tracking UI (Shopee/J&T-grade) without changing the database schema, while extracting reusable timeline/courier/stats components and a `TrackingTimelineService`.

**Architecture:**
- Pure visual & interaction overhaul on top of existing `SupplyOrder` columns (`status`, `nama_kurir`, `no_hp_kurir`, `no_resi`, `created_at`, `updated_at`). No new tables/columns.
- New service `App\Services\Tracking\TrackingTimelineService` derives an ordered event list and progress % from the order's current `status`. WhatsApp URL normalizer lives here too.
- New Blade components under `resources/views/components/tracking/` (`stat-tile`, `progress-track`, `courier-card`, `status-timeline`) provide reusable building blocks themed via a `variant` prop (`merchant` = emerald/lime, `pemasok` = orange/amber).
- Pemasok page and Merchant page assemble those components, add a hero header, stats row, glass cards, embedded timeline, and embedded courier card.

**Tech Stack:**
- Laravel 11, PHP 8+
- Livewire 3 (class-based for Pemasok, Volt single-file for Merchant)
- Tailwind CSS (Vite build) + Plus Jakarta Sans (already loaded in `layouts/app.blade.php`)
- AlpineJS (for modal `x-data` toggles — already in use)
- Pest for tests, sqlite `:memory:` per `phpunit.xml`

**Existing data flow (do not change):**
- `SupplyOrder.status` enum used: `pending`, `menunggu_lkbb`, `menunggu_pemasok`, `diproses_pemasok`, `dikirim`, `selesai`, `ditolak`
- Pemasok assigns kurir via `simpanPengiriman()` → transitions `diproses_pemasok` → `dikirim`
- Merchant confirms via `konfirmasiTerima()` → transitions `dikirim` → `selesai`

**Out of scope (per user decision):**
- DB schema changes (no `shipping_events` table, no JSON tracking_log)
- QR/barcode scan FAB, websocket realtime notifications, kurir photo upload
- Surat jalan print modal (already polished in previous turn)

---

## File Structure

**Create:**
- `app/Services/Tracking/TrackingTimelineService.php` — derives timeline events + progress + WhatsApp URL
- `tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php` — Pest tests, no DB
- `resources/views/components/tracking/stat-tile.blade.php` — glass mini-card
- `resources/views/components/tracking/progress-track.blade.php` — animated horizontal bar
- `resources/views/components/tracking/courier-card.blade.php` — kurir card with WA button
- `resources/views/components/tracking/status-timeline.blade.php` — vertical timeline

**Modify:**
- `resources/css/app.css` — add `scfs-shimmer` and `scfs-timeline-in` keyframes
- `app/Livewire/Pemasok/PengirimanLogistik.php` — add `#[Computed]` stats + timeline accessor
- `resources/views/livewire/pemasok/pengiriman-logistik.blade.php` — full markup redesign (orange theme)
- `resources/views/livewire/merchant/penerimaan.blade.php` — full markup redesign (emerald theme) + Volt `#[Computed]` stats

**Do NOT touch:**
- Database migrations
- `simpanPengiriman()` / `konfirmasiTerima()` business logic
- Surat jalan print modal block (`#area-cetak-label`)

---

## Task 1: Add tracking CSS keyframes to global stylesheet

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1: Append keyframes + helper classes**

Open `resources/css/app.css` and append at the end:

```css
/* === SCFS Tracking UI animations === */
@keyframes scfs-shimmer {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.scfs-shimmer {
    animation: scfs-shimmer 2.2s linear infinite;
}

@keyframes scfs-timeline-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.scfs-timeline-item {
    animation: scfs-timeline-in 0.4s ease-out backwards;
}

@keyframes scfs-fade-in-up {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.scfs-fade-in-up {
    animation: scfs-fade-in-up 0.5s ease-out backwards;
}
```

- [ ] **Step 2: Rebuild assets**

Run: `npm run build`
Expected: Vite finishes without errors and emits a new hashed CSS bundle in `public/build/`.

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css public/build
git commit -m "feat(tracking): tambah keyframes shimmer & timeline-in untuk UI pelacakan"
```

---

## Task 2: TrackingTimelineService — scaffolding + buildEvents (TDD)

**Files:**
- Create: `app/Services/Tracking/TrackingTimelineService.php`
- Create: `tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`

- [ ] **Step 1: Write failing tests for `buildEvents`**

Create `tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`:

```php
<?php

use App\Models\SupplyOrder;
use App\Services\Tracking\TrackingTimelineService;
use Carbon\Carbon;

function makeOrder(array $attrs = []): SupplyOrder
{
    $order = new SupplyOrder();
    $order->status = $attrs['status'] ?? 'pending';
    $order->nama_kurir = $attrs['nama_kurir'] ?? null;
    $order->no_hp_kurir = $attrs['no_hp_kurir'] ?? null;
    $order->no_resi = $attrs['no_resi'] ?? null;
    $order->created_at = $attrs['created_at'] ?? Carbon::parse('2026-05-20 09:00:00');
    $order->updated_at = $attrs['updated_at'] ?? Carbon::parse('2026-05-21 14:30:00');
    return $order;
}

it('builds the canonical 4-step timeline', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'pending']));

    expect($events)->toHaveCount(4);
    expect($events->pluck('key')->all())->toBe([
        'pesanan_dibuat',
        'diproses_pemasok',
        'dikirim',
        'selesai',
    ]);
});

it('marks pesanan_dibuat active for a brand-new pending order', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'pending']));

    expect($events[0]['state'])->toBe('active');
    expect($events[1]['state'])->toBe('pending');
    expect($events[3]['state'])->toBe('pending');
});

it('marks every step completed for a selesai order', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'selesai']));

    foreach ($events as $event) {
        expect($event['state'])->toBe('completed');
    }
});

it('marks dikirim active and earlier steps completed when status is dikirim', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'dikirim']));

    expect($events[0]['state'])->toBe('completed');
    expect($events[1]['state'])->toBe('completed');
    expect($events[2]['state'])->toBe('active');
    expect($events[3]['state'])->toBe('pending');
});
```

- [ ] **Step 2: Run tests, confirm failure**

Run: `vendor\bin\pest tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`
Expected: 4 failures with "Class App\Services\Tracking\TrackingTimelineService not found".

- [ ] **Step 3: Implement the service**

Create `app/Services/Tracking/TrackingTimelineService.php`:

```php
<?php

namespace App\Services\Tracking;

use App\Models\SupplyOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrackingTimelineService
{
    /**
     * Definisi 4 etape utama pelacakan supply order.
     * Urutan ini menentukan posisi (1..4) yang dipakai untuk derive state.
     */
    private const FLOW = [
        [
            'key' => 'pesanan_dibuat',
            'label' => 'Pesanan Dibuat',
            'description' => 'Merchant mengajukan kebutuhan supply ke sistem SCFS.',
            'icon' => 'shopping-bag',
        ],
        [
            'key' => 'diproses_pemasok',
            'label' => 'Disiapkan Pemasok',
            'description' => 'Pemasok mengemas barang & menyiapkan armada.',
            'icon' => 'package',
        ],
        [
            'key' => 'dikirim',
            'label' => 'Dalam Perjalanan',
            'description' => 'Kurir membawa barang menuju lokasi merchant.',
            'icon' => 'truck',
        ],
        [
            'key' => 'selesai',
            'label' => 'Diterima Merchant',
            'description' => 'Barang sampai dan dikonfirmasi oleh merchant.',
            'icon' => 'check-circle',
        ],
    ];

    public function buildEvents(SupplyOrder $order): Collection
    {
        $currentPosition = $this->positionForStatus($order->status);
        $isTerminated = $order->status === 'ditolak';

        return collect(self::FLOW)->map(function (array $step, int $index) use ($order, $currentPosition, $isTerminated) {
            $position = $index + 1;
            $state = $this->stateFor($position, $currentPosition, $order->status, $isTerminated);

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'description' => $step['description'],
                'icon' => $step['icon'],
                'timestamp' => $this->timestampFor($order, $position, $currentPosition),
                'state' => $state,
                'meta' => $this->metaFor($order, $step['key']),
            ];
        });
    }

    private function positionForStatus(string $status): int
    {
        return match ($status) {
            'pending', 'menunggu_lkbb', 'menunggu_pemasok' => 1,
            'diproses_pemasok' => 2,
            'dikirim' => 3,
            'selesai' => 4,
            default => 1,
        };
    }

    private function stateFor(int $position, int $currentPosition, string $status, bool $isTerminated): string
    {
        if ($isTerminated) {
            return 'pending';
        }
        if ($status === 'selesai') {
            return 'completed';
        }
        if ($position < $currentPosition) {
            return 'completed';
        }
        if ($position === $currentPosition) {
            return 'active';
        }
        return 'pending';
    }

    private function timestampFor(SupplyOrder $order, int $position, int $currentPosition): ?Carbon
    {
        if ($position > $currentPosition) {
            return null;
        }
        if ($position === 1) {
            return $order->created_at;
        }
        if ($position === $currentPosition) {
            return $order->updated_at;
        }
        return null; // Intermediate completed steps don't have stored timestamps.
    }

    private function metaFor(SupplyOrder $order, string $key): array
    {
        if ($key !== 'dikirim') {
            return [];
        }
        return array_filter([
            'kurir' => $order->nama_kurir,
            'hp' => $order->no_hp_kurir,
            'resi' => $order->no_resi,
        ], fn ($v) => filled($v));
    }
}
```

- [ ] **Step 4: Run tests, confirm pass**

Run: `vendor\bin\pest tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`
Expected: 4 passing tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Tracking/TrackingTimelineService.php tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php
git commit -m "feat(tracking): TrackingTimelineService.buildEvents derive etape dari status PO"
```

---

## Task 3: TrackingTimelineService — progress % + meta + WhatsApp normalizer (TDD)

**Files:**
- Modify: `app/Services/Tracking/TrackingTimelineService.php`
- Modify: `tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`

- [ ] **Step 1: Append failing tests**

Append to `tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`:

```php
it('exposes kurir meta on the dikirim step when available', function () {
    $order = makeOrder([
        'status' => 'dikirim',
        'nama_kurir' => 'Budi Santoso',
        'no_hp_kurir' => '081234567890',
        'no_resi' => 'SCFS-ABC123',
    ]);

    $events = (new TrackingTimelineService())->buildEvents($order);
    $dikirim = $events->firstWhere('key', 'dikirim');

    expect($dikirim['meta'])->toMatchArray([
        'kurir' => 'Budi Santoso',
        'hp' => '081234567890',
        'resi' => 'SCFS-ABC123',
    ]);
});

it('omits kurir meta keys when columns are null', function () {
    $events = (new TrackingTimelineService())->buildEvents(makeOrder(['status' => 'diproses_pemasok']));
    $dikirim = $events->firstWhere('key', 'dikirim');

    expect($dikirim['meta'])->toBe([]);
});

it('computes progress percentage from status', function () {
    $svc = new TrackingTimelineService();

    expect($svc->progressPercentage(makeOrder(['status' => 'pending'])))->toBe(12);
    expect($svc->progressPercentage(makeOrder(['status' => 'diproses_pemasok'])))->toBe(37);
    expect($svc->progressPercentage(makeOrder(['status' => 'dikirim'])))->toBe(62);
    expect($svc->progressPercentage(makeOrder(['status' => 'selesai'])))->toBe(100);
    expect($svc->progressPercentage(makeOrder(['status' => 'ditolak'])))->toBe(0);
});

it('normalizes Indonesian phone numbers to wa.me URLs', function () {
    expect(TrackingTimelineService::normalizeWhatsappUrl('081234567890'))
        ->toBe('https://wa.me/6281234567890');

    expect(TrackingTimelineService::normalizeWhatsappUrl('+62 812-3456-7890'))
        ->toBe('https://wa.me/6281234567890');

    expect(TrackingTimelineService::normalizeWhatsappUrl('6281234567890'))
        ->toBe('https://wa.me/6281234567890');

    expect(TrackingTimelineService::normalizeWhatsappUrl(null))->toBeNull();
    expect(TrackingTimelineService::normalizeWhatsappUrl(''))->toBeNull();
});
```

- [ ] **Step 2: Run tests, confirm failure**

Run: `vendor\bin\pest tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`
Expected: 4 new failures (progressPercentage, normalizeWhatsappUrl undefined).

- [ ] **Step 3: Add `progressPercentage` and `normalizeWhatsappUrl`**

Append two methods to `app/Services/Tracking/TrackingTimelineService.php` (inside the class, before the closing brace):

```php
    public function progressPercentage(SupplyOrder $order): int
    {
        if ($order->status === 'ditolak') {
            return 0;
        }
        if ($order->status === 'selesai') {
            return 100;
        }
        $position = $this->positionForStatus($order->status);
        // Step aktif ditampilkan setengah-jalan untuk feedback visual.
        return (int) floor((($position - 0.5) / count(self::FLOW)) * 100);
    }

    public static function normalizeWhatsappUrl(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }
        return 'https://wa.me/' . $digits;
    }
```

- [ ] **Step 4: Run all tests, confirm pass**

Run: `vendor\bin\pest tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php`
Expected: 8 passing tests total.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Tracking/TrackingTimelineService.php tests/Unit/Services/Tracking/TrackingTimelineServiceTest.php
git commit -m "feat(tracking): tambah progressPercentage & normalizeWhatsappUrl"
```

---

## Task 4: Blade component — `<x-tracking.stat-tile>`

**Files:**
- Create: `resources/views/components/tracking/stat-tile.blade.php`

- [ ] **Step 1: Create the component file**

Create `resources/views/components/tracking/stat-tile.blade.php`:

```blade
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
```

- [ ] **Step 2: Smoke-render the component**

Open `routes/web.php` and add a temporary route (will remove later):

```php
Route::get('/__tracking-stat-smoke', function () {
    return view('tracking-smoke');
});
```

Create `resources/views/tracking-smoke.blade.php`:

```blade
<!DOCTYPE html>
<html><head>@vite('resources/css/app.css')</head>
<body class="bg-gray-100 p-8">
    <div class="grid grid-cols-4 gap-4 max-w-4xl">
        <x-tracking.stat-tile label="Perlu Dikirim" value="12" caption="hari ini" accent="orange" :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4\'/></svg>'" />
        <x-tracking.stat-tile label="Sedang Jalan" value="3" accent="amber" />
        <x-tracking.stat-tile label="Selesai" value="142" accent="emerald" />
        <x-tracking.stat-tile label="Nilai" value="Rp 12 Jt" accent="sky" />
    </div>
</body></html>
```

Visit `http://localhost/scfs-web/public/__tracking-stat-smoke` (or your local URL) and confirm all 4 tiles render with distinct accent colors, hover lift works, glow blobs visible.

- [ ] **Step 3: Remove the smoke route and view**

Delete the `Route::get('/__tracking-stat-smoke'...)` line from `routes/web.php` and delete `resources/views/tracking-smoke.blade.php`.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/tracking/stat-tile.blade.php
git commit -m "feat(tracking): komponen stat-tile glass dengan 6 accent varian"
```

---

## Task 5: Blade component — `<x-tracking.progress-track>`

**Files:**
- Create: `resources/views/components/tracking/progress-track.blade.php`

- [ ] **Step 1: Create the component**

Create `resources/views/components/tracking/progress-track.blade.php`:

```blade
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
```

- [ ] **Step 2: Visual smoke check**

Re-add the smoke route temporarily (same `/__tracking-stat-smoke` path) but with body:

```blade
<x-tracking.progress-track :percentage="37" variant="pemasok" label="Proses Pengiriman" sublabel="37%" class="max-w-md mb-4" />
<x-tracking.progress-track :percentage="100" variant="merchant" label="Selesai" sublabel="100%" class="max-w-md" />
```

Visit the URL and confirm: orange shimmering bar at 37%, full emerald bar at 100%, shimmer animation visible.

Remove the smoke route/view after confirming.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/tracking/progress-track.blade.php
git commit -m "feat(tracking): komponen progress-track dengan animasi shimmer"
```

---

## Task 6: Blade component — `<x-tracking.courier-card>`

**Files:**
- Create: `resources/views/components/tracking/courier-card.blade.php`

- [ ] **Step 1: Create the component**

Create `resources/views/components/tracking/courier-card.blade.php`:

```blade
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
```

- [ ] **Step 2: Smoke test the WA URL**

Add a temporary smoke route and view that renders:

```blade
<x-tracking.courier-card name="Budi Santoso" phone="081234567890" resi="SCFS-ABC123" status="dikirim" variant="merchant" class="max-w-md" />
<x-tracking.courier-card name="Siti Aminah" phone="081234567890" resi="SCFS-XYZ999" status="selesai" variant="pemasok" class="max-w-md mt-4" />
<x-tracking.courier-card class="max-w-md mt-4" /> {{-- empty state --}}
```

Visit, click the WhatsApp button on the first card, confirm it opens `https://wa.me/6281234567890` in a new tab.

Remove the smoke route/view.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/tracking/courier-card.blade.php
git commit -m "feat(tracking): komponen courier-card glass dengan tombol WhatsApp"
```

---

## Task 7: Blade component — `<x-tracking.status-timeline>`

**Files:**
- Create: `resources/views/components/tracking/status-timeline.blade.php`

- [ ] **Step 1: Create the component**

Create `resources/views/components/tracking/status-timeline.blade.php`:

```blade
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
```

- [ ] **Step 2: Smoke render**

Temporary smoke route, render with sample events:

```blade
@php
    $events = (new \App\Services\Tracking\TrackingTimelineService())->buildEvents(
        tap(new \App\Models\SupplyOrder(), function ($o) {
            $o->status = 'dikirim';
            $o->nama_kurir = 'Budi';
            $o->no_resi = 'RES-001';
            $o->created_at = now()->subDays(2);
            $o->updated_at = now()->subHours(3);
        })
    );
@endphp
<div class="max-w-md mx-auto bg-white p-6 rounded-3xl mt-12">
    <x-tracking.status-timeline :events="$events" variant="merchant" />
</div>
```

Confirm: 4 nodes, first two filled emerald, third pulsing+ping, fourth dashed gray. Connector lines gradient between completed, dashed between pending. Kurir chip shows on "Dalam Perjalanan".

Switch `variant="pemasok"` and confirm nodes turn orange.

Remove smoke route/view.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/tracking/status-timeline.blade.php
git commit -m "feat(tracking): komponen status-timeline vertikal dengan animasi reveal"
```

---

## Task 8: Pemasok PHP class — add stats computed props + service injection

**Files:**
- Modify: `app/Livewire/Pemasok/PengirimanLogistik.php`

- [ ] **Step 1: Add `#[Computed]` stats + tracking events accessor**

Open `app/Livewire/Pemasok/PengirimanLogistik.php`. After the existing `selectedOrder` computed (line ~86) and before `render()`, insert:

```php
    #[Computed]
    public function stats(): array
    {
        $base = SupplyOrder::where('pemasok_id', Auth::id());

        return [
            'perlu_dikirim' => (clone $base)->where('status', 'diproses_pemasok')->count(),
            'sedang_jalan' => (clone $base)->where('status', 'dikirim')->count(),
            'selesai_bulan_ini' => (clone $base)
                ->where('status', 'selesai')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'nilai_aktif' => (clone $base)
                ->whereIn('status', ['diproses_pemasok', 'dikirim'])
                ->sum('total_estimasi'),
        ];
    }

    #[Computed]
    public function selectedOrderEvents()
    {
        if (! $this->selectedOrder) {
            return collect();
        }
        return app(\App\Services\Tracking\TrackingTimelineService::class)
            ->buildEvents($this->selectedOrder);
    }

    #[Computed]
    public function selectedOrderProgress(): int
    {
        if (! $this->selectedOrder) {
            return 0;
        }
        return app(\App\Services\Tracking\TrackingTimelineService::class)
            ->progressPercentage($this->selectedOrder);
    }
```

- [ ] **Step 2: Make per-row tracking events available in `render()`**

Replace the existing `render()` method body. Inside `render()`, after the `$orders = ...` block and before the `return view(...)`, build a map of order_id → events so the Blade list can iterate cheaply without calling the service in a loop attribute:

```php
        $svc = app(\App\Services\Tracking\TrackingTimelineService::class);
        $trackingByOrder = $orders->getCollection()->mapWithKeys(fn ($o) => [
            $o->id => [
                'events' => $svc->buildEvents($o),
                'progress' => $svc->progressPercentage($o),
            ],
        ]);

        return view('livewire.pemasok.pengiriman-logistik', [
            'orders' => $orders,
            'countPerluDikirim' => $countPerluDikirim,
            'trackingByOrder' => $trackingByOrder,
        ])->layout('layouts.app');
```

- [ ] **Step 3: Sanity-check the class compiles**

Run: `php artisan livewire:discover` then `php artisan view:clear`
Expected: no errors. (If `livewire:discover` is not available in this version, `php artisan optimize:clear` works as a substitute.)

- [ ] **Step 4: Commit**

```bash
git add app/Livewire/Pemasok/PengirimanLogistik.php
git commit -m "feat(pemasok): expose stats & tracking events ke view via TrackingTimelineService"
```

---

## Task 9: Pemasok view redesign — full rewrite (orange theme)

**Files:**
- Modify: `resources/views/livewire/pemasok/pengiriman-logistik.blade.php`

- [ ] **Step 1: Read the current file once**

Open `resources/views/livewire/pemasok/pengiriman-logistik.blade.php`. Note: the **3 existing modals** (detail / atur / cetak surat jalan) must be preserved verbatim — they are wired to `wire:click` / `wire:submit` handlers that already work. Only the page body above them is being rewritten.

- [ ] **Step 2: Replace the page-body markup**

Replace everything from the opening `<div class="p-6 relative max-w-7xl mx-auto">` down to the closing `</div>` of the order list (i.e. the line right before `{{-- MODAL LIHAT DETAIL PESANAN --}}`) with the markup below. Keep the three modal blocks (`{{-- MODAL LIHAT DETAIL PESANAN --}}`, `{{-- MODAL ATUR PENGIRIMAN --}}`, `{{-- MODAL CETAK SURAT JALAN --}}`) and the final `</div>` untouched.

```blade
<div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-6 lg:py-8">

    {{-- CSS Khusus Cetak (jangan dihapus, dipakai oleh modal surat jalan) --}}
    <style>
        @media print {
            body * { visibility: hidden; }
            #area-cetak-label, #area-cetak-label * { visibility: visible; }
            #area-cetak-label { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>

    {{-- ===== Hero header dengan gradien oranye ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 p-6 sm:p-8 mb-6 shadow-[0_20px_50px_-20px_rgba(234,88,12,0.5)]">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-10 -bottom-20 h-56 w-56 rounded-full bg-amber-300/30 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="text-white">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-100/90">Pemasok · Operasional Armada</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-black tracking-tight">Halo, {{ Auth::user()->name }} 👋</h1>
                <p class="mt-1 text-sm text-amber-50/90 font-medium">Atur armada, lacak setiap paket, dan pastikan barang tiba di kantin merchant tepat waktu.</p>
            </div>
            <div class="relative w-full md:w-80">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nomor PO / kantin / resi…"
                       class="w-full rounded-2xl border border-white/40 bg-white/95 backdrop-blur pl-11 pr-4 py-3 text-sm font-bold text-gray-700 placeholder:font-medium placeholder:text-gray-400 shadow-lg shadow-orange-900/20 outline-none focus:ring-2 focus:ring-white">
                <svg class="absolute left-3.5 top-3.5 h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- ===== Stats row ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <x-tracking.stat-tile
            label="Perlu Dikirim"
            :value="$this->stats['perlu_dikirim']"
            caption="armada belum diatur"
            accent="orange"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Sedang Jalan"
            :value="$this->stats['sedang_jalan']"
            caption="armada aktif"
            accent="amber"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 4m0 0v3a1 1 0 01-1 1h-2M14 8h4l3 4\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Selesai Bulan Ini"
            :value="$this->stats['selesai_bulan_ini']"
            caption="diterima merchant"
            accent="emerald"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2.5\' d=\'M5 13l4 4L19 7\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Nilai Aktif"
            :value="'Rp ' . number_format($this->stats['nilai_aktif'], 0, ',', '.')"
            caption="dalam pengiriman"
            accent="sky"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\'/></svg>'"
        />
    </div>

    {{-- ===== Flash message ===== --}}
    @if (session()->has('message'))
        <div class="mb-5 flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/80 backdrop-blur px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm scfs-fade-in-up">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- ===== Tabs (orange-themed) ===== --}}
    <div class="mb-6 flex w-full overflow-x-auto rounded-2xl border border-white/60 bg-white/80 p-1.5 shadow-sm backdrop-blur-xl sm:w-max scrollbar-hide">
        <button wire:click="setTab('diproses_pemasok')" @class([
            'relative flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md shadow-orange-200' => $activeTab === 'diproses_pemasok',
            'text-gray-500 hover:bg-gray-50' => $activeTab !== 'diproses_pemasok',
        ])>
            Perlu Dikirim
            @if($countPerluDikirim > 0)
                <span @class([
                    'ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-black',
                    'bg-white/25 text-white' => $activeTab === 'diproses_pemasok',
                    'bg-orange-100 text-orange-600' => $activeTab !== 'diproses_pemasok',
                ])>{{ $countPerluDikirim }}</span>
            @endif
        </button>
        <button wire:click="setTab('dikirim')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md shadow-amber-200' => $activeTab === 'dikirim',
            'text-gray-500 hover:bg-gray-50' => $activeTab !== 'dikirim',
        ])>Sedang Jalan</button>
        <button wire:click="setTab('selesai')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200' => $activeTab === 'selesai',
            'text-gray-500 hover:bg-gray-50' => $activeTab !== 'selesai',
        ])>Diterima Merchant</button>
    </div>

    {{-- ===== Order list ===== --}}
    <div class="space-y-4">
        @forelse($orders as $i => $order)
            @php $tracking = $trackingByOrder[$order->id] ?? ['events' => collect(), 'progress' => 0]; @endphp
            <article wire:key="order-{{ $order->id }}" class="scfs-fade-in-up relative overflow-hidden rounded-3xl border border-white/60 bg-white/85 backdrop-blur-xl p-5 sm:p-6 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)]" style="animation-delay: {{ $i * 60 }}ms;">

                {{-- Header card --}}
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 mb-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-orange-700">{{ $order->nomor_order }}</span>
                        <span class="text-xs font-bold text-gray-400">Butuh tgl {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Nilai</p>
                        <p class="text-base font-black text-orange-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                </header>

                <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    {{-- Kolom kiri: penerima + timeline + courier --}}
                    <div class="space-y-5">
                        {{-- Penerima --}}
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-md shadow-orange-200 ring-2 ring-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Tujuan</p>
                                <h3 class="text-base font-black leading-tight text-gray-900 truncate">{{ $order->merchant->merchantProfile->nama_kantin ?? $order->merchant->name }}</h3>
                                <p class="mt-0.5 text-xs font-medium text-gray-600">
                                    Blok <span class="font-bold">{{ $order->merchant->merchantProfile->lokasi_blok ?? 'Belum diatur' }}</span>
                                    · {{ $order->merchant->merchantProfile->nama_pemilik ?? '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Progress + mini timeline --}}
                        <div>
                            <x-tracking.progress-track :percentage="$tracking['progress']" variant="pemasok" :label="'Progres Pengiriman'" :sublabel="$tracking['progress'] . '%'" />
                            <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                                <x-tracking.status-timeline :events="$tracking['events']" variant="pemasok" />
                            </div>
                        </div>

                        {{-- Catatan merchant --}}
                        @if($order->catatan)
                            <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2 text-[11px] font-bold text-amber-800">
                                ✱ Catatan merchant: {{ $order->catatan }}
                            </div>
                        @endif
                    </div>

                    {{-- Kolom kanan: kurir card + aksi --}}
                    <aside class="flex flex-col gap-3 border-t border-gray-100 pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        @if($order->nama_kurir)
                            <x-tracking.courier-card
                                :name="$order->nama_kurir"
                                :phone="$order->no_hp_kurir"
                                :resi="$order->no_resi"
                                :status="$order->status"
                                variant="pemasok"
                            />
                        @endif

                        @if($activeTab === 'diproses_pemasok')
                            <button wire:click="bukaModalAtur({{ $order->id }})" class="w-full rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-orange-200 transition hover:shadow-xl hover:shadow-orange-300 active:scale-[0.98]">
                                🛵 Atur Kurir & Kirim
                            </button>
                            <div class="flex gap-2">
                                <button wire:click="bukaModalDetail({{ $order->id }})" class="flex-1 rounded-2xl border border-orange-200 bg-white px-3 py-2.5 text-xs font-bold text-orange-600 transition hover:bg-orange-50">Detail</button>
                                <button wire:click="cetakLabel({{ $order->id }})" class="flex-1 rounded-2xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50">Cetak Label</button>
                            </div>
                        @elseif($activeTab === 'dikirim')
                            <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-50">Lihat Detail Pesanan</button>
                            <button wire:click="cetakLabel({{ $order->id }})" class="w-full rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-xs font-bold text-orange-600 transition hover:bg-orange-100">Cetak Ulang Surat Jalan</button>
                        @else
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-lime-50 border border-emerald-200 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">✓ Diterima merchant</p>
                                <p class="mt-0.5 text-[10px] font-bold text-emerald-600">{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y · H:i') }}</p>
                            </div>
                            <button wire:click="bukaModalDetail({{ $order->id }})" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50">Cek Rincian</button>
                        @endif
                    </aside>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white/60 py-20 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-amber-100 text-orange-500">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800">Tidak ada pengiriman</h3>
                <p class="mt-1 text-sm font-bold text-gray-500">Data pesanan di kategori ini masih kosong.</p>
            </div>
        @endforelse

        <div class="mt-5">{{ $orders->links() }}</div>
    </div>
```

**Important:** Do NOT delete the `</div>` that closes the outermost wrapper, and do NOT delete or modify the three modal blocks (`{{-- MODAL LIHAT DETAIL PESANAN --}}`, `{{-- MODAL ATUR PENGIRIMAN --}}`, `{{-- MODAL CETAK SURAT JALAN --}}`). They stay verbatim, immediately after the closing of the order list block above.

- [ ] **Step 3: Visual verification**

Run: `php artisan view:clear` then load `/pemasok/pengiriman` while logged in as a pemasok account.
Expected: orange hero, 4 stat tiles, restyled tabs, order cards with mini timeline + progress + courier card embedded. All three modals still open/work (klik "Detail", "Atur Kurir", "Cetak Label").

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/pemasok/pengiriman-logistik.blade.php
git commit -m "feat(pemasok): redesign halaman pengiriman premium oranye dengan timeline + stats"
```

---

## Task 10: Merchant Volt class — add stats + tracking computed

**Files:**
- Modify: `resources/views/livewire/merchant/penerimaan.blade.php` (Volt PHP header at top)

- [ ] **Step 1: Add `#[Computed]` stats + tracking accessors**

Open `resources/views/livewire/merchant/penerimaan.blade.php`. Inside the `new #[Layout('layouts.app')] class extends Component { ... }` block, right after the existing `supplyOrders()` `#[Computed]` method, add:

```php
    #[Computed]
    public function stats(): array
    {
        $base = SupplyOrder::where('merchant_id', Auth::id());

        return [
            'aktif' => (clone $base)->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'menunggu_pemasok'])->count(),
            'sedang_dikirim' => (clone $base)->where('status', 'dikirim')->count(),
            'diterima_bulan_ini' => (clone $base)
                ->where('status', 'selesai')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
            'nilai_aktif' => (clone $base)
                ->whereIn('status', ['menunggu_lkbb', 'diproses_pemasok', 'dikirim', 'menunggu_pemasok'])
                ->sum('total_estimasi'),
        ];
    }

    public function trackingFor(SupplyOrder $order): array
    {
        $svc = app(\App\Services\Tracking\TrackingTimelineService::class);
        return [
            'events' => $svc->buildEvents($order),
            'progress' => $svc->progressPercentage($order),
        ];
    }
```

- [ ] **Step 2: Sanity check**

Run: `php artisan optimize:clear`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/merchant/penerimaan.blade.php
git commit -m "feat(merchant): tambah stats & tracking computed pada Volt penerimaan"
```

---

## Task 11: Merchant view redesign — full rewrite (emerald theme)

**Files:**
- Modify: `resources/views/livewire/merchant/penerimaan.blade.php` (Blade body, below the `?>`)

- [ ] **Step 1: Locate the boundary**

In `resources/views/livewire/merchant/penerimaan.blade.php`, the Blade body starts after `?>` (around line ~100). Keep the `?>` line and replace **everything from the opening `<div class="py-8 px-6 …">` down to but NOT including the `{{-- MODAL POP-UP KONFIRMASI ... --}}` block**. The modal block at the bottom stays verbatim.

- [ ] **Step 2: Replace the markup**

Paste the following replacement:

```blade
<div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-6 lg:py-8">

    {{-- ===== Hero header (emerald) ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-lime-500 p-6 sm:p-8 mb-6 shadow-[0_20px_50px_-20px_rgba(16,185,129,0.5)]">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-10 -bottom-20 h-56 w-56 rounded-full bg-lime-300/30 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="text-white">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-100/90">Merchant · Penerimaan Logistik</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-black tracking-tight">Halo, {{ Auth::user()->name }} 👋</h1>
                <p class="mt-1 text-sm text-emerald-50/90 font-medium">Pantau pesanan, lacak posisi kurir, dan konfirmasi saat barang tiba di kantin Anda.</p>
            </div>
            <div class="relative w-full md:w-80">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor order / resi…"
                       class="w-full rounded-2xl border border-white/40 bg-white/95 backdrop-blur pl-11 pr-4 py-3 text-sm font-bold text-gray-700 placeholder:font-medium placeholder:text-gray-400 shadow-lg shadow-emerald-900/20 outline-none focus:ring-2 focus:ring-white">
                <svg class="absolute left-3.5 top-3.5 h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- ===== Stats row ===== --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <x-tracking.stat-tile
            label="Pesanan Aktif"
            :value="$this->stats['aktif']"
            caption="sedang diproses"
            accent="emerald"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Sedang Dikirim"
            :value="$this->stats['sedang_dikirim']"
            caption="kurir di jalan"
            accent="amber"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 4m0 0v3a1 1 0 01-1 1h-2M14 8h4l3 4\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Diterima Bulan Ini"
            :value="$this->stats['diterima_bulan_ini']"
            caption="masuk etalase"
            accent="lime"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2.5\' d=\'M5 13l4 4L19 7\'/></svg>'"
        />
        <x-tracking.stat-tile
            label="Nilai Aktif"
            :value="'Rp ' . number_format($this->stats['nilai_aktif'], 0, ',', '.')"
            caption="modal titipan"
            accent="sky"
            :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1\'/></svg>'"
        />
    </div>

    {{-- ===== Flash ===== --}}
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 backdrop-blur px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm scfs-fade-in-up">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 backdrop-blur px-4 py-3 text-sm font-bold text-rose-700 shadow-sm scfs-fade-in-up">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ===== Tabs ===== --}}
    <div class="mb-6 flex w-full overflow-x-auto rounded-2xl border border-white/60 bg-white/80 p-1.5 shadow-sm backdrop-blur-xl sm:w-max scrollbar-hide">
        <button wire:click="$set('statusFilter', 'aktif')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200' => $statusFilter === 'aktif',
            'text-gray-500 hover:bg-gray-50' => $statusFilter !== 'aktif',
        ])>Sedang Proses</button>
        <button wire:click="$set('statusFilter', 'selesai')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-emerald-600 to-emerald-500 text-white shadow-md shadow-emerald-200' => $statusFilter === 'selesai',
            'text-gray-500 hover:bg-gray-50' => $statusFilter !== 'selesai',
        ])>Telah Diterima</button>
        <button wire:click="$set('statusFilter', 'ditolak')" @class([
            'flex-none rounded-xl px-5 py-2.5 text-sm font-bold transition-all',
            'bg-gradient-to-r from-rose-500 to-rose-600 text-white shadow-md shadow-rose-200' => $statusFilter === 'ditolak',
            'text-gray-500 hover:bg-gray-50' => $statusFilter !== 'ditolak',
        ])>Ditolak / Batal</button>
    </div>

    {{-- ===== Order list ===== --}}
    <div class="space-y-4">
        @forelse($this->supplyOrders as $i => $order)
            @php $tracking = $this->trackingFor($order); @endphp
            <article wire:key="order-{{ $order->id }}" class="scfs-fade-in-up relative overflow-hidden rounded-3xl border border-white/60 bg-white/85 backdrop-blur-xl p-5 sm:p-6 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)]" style="animation-delay: {{ $i * 60 }}ms;">

                {{-- Header --}}
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 mb-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700">{{ $order->nomor_order }}</span>
                        <span class="text-xs font-bold text-gray-400">Dipesan {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Nilai LKBB</p>
                        <p class="text-base font-black text-emerald-600">Rp {{ number_format($order->total_estimasi, 0, ',', '.') }}</p>
                    </div>
                </header>

                <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    {{-- Kiri: pengirim + timeline + barang --}}
                    <div class="space-y-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-lime-500 text-white shadow-md shadow-emerald-200 ring-2 ring-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M5 9h14M5 13h14M5 17h14"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Dikirim oleh</p>
                                <h3 class="text-base font-black leading-tight text-gray-900 truncate">{{ $order->pemasok->pemasokProfile->nama_perusahaan ?? $order->pemasok->name ?? 'Pemasok SCFS' }}</h3>
                                <p class="mt-0.5 text-xs font-medium text-gray-500">Untuk tanggal kebutuhan {{ \Carbon\Carbon::parse($order->tanggal_kebutuhan)->format('d M Y') }}</p>
                            </div>
                        </div>

                        {{-- Progress + timeline --}}
                        <div>
                            <x-tracking.progress-track :percentage="$tracking['progress']" variant="merchant" :label="'Progres Pengiriman'" :sublabel="$tracking['progress'] . '%'" />
                            <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                                <x-tracking.status-timeline :events="$tracking['events']" variant="merchant" />
                            </div>
                        </div>

                        {{-- Barang --}}
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Barang yang Diterima ({{ $order->details->count() }} item)</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach($order->details as $detail)
                                    <div class="flex items-center gap-2 rounded-xl border border-gray-100 bg-white/70 px-3 py-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-[11px] font-black text-emerald-700">{{ $detail->qty }}</div>
                                        <p class="truncate text-xs font-bold text-gray-800" title="{{ $detail->nama_produk_snapshot }}">{{ $detail->nama_produk_snapshot }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Kanan: courier card + aksi sesuai status --}}
                    <aside class="flex flex-col gap-3 border-t border-gray-100 pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        @if($order->nama_kurir)
                            <x-tracking.courier-card
                                :name="$order->nama_kurir"
                                :phone="$order->no_hp_kurir"
                                :resi="$order->no_resi"
                                :status="$order->status"
                                variant="merchant"
                            />
                        @endif

                        @if(in_array($order->status, ['menunggu_pemasok', 'menunggu_lkbb']))
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 text-center">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-600">⏳ Menunggu Persetujuan</p>
                                <p class="mt-1 text-[10px] font-medium text-gray-500">Pemasok & LKBB sedang meninjau.</p>
                            </div>
                        @elseif($order->status === 'diproses_pemasok')
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-center">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">📦 Disiapkan Pemasok</p>
                                <p class="mt-1 text-[10px] font-medium text-amber-700">Barang sedang dikemas.</p>
                            </div>
                        @elseif($order->status === 'dikirim')
                            <button wire:click="openConfirmModal({{ $order->id }})" class="w-full rounded-2xl bg-gradient-to-r from-emerald-500 to-lime-500 px-4 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 transition hover:shadow-xl hover:shadow-emerald-300 active:scale-[0.98] flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Barang Telah Diterima
                            </button>
                            @if($order->has_active_return)
                                <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-xs font-bold text-amber-700 transition hover:bg-amber-100">⏳ Ada Return Aktif — Lihat Status</a>
                            @else
                                <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-2.5 text-center text-xs font-bold text-rose-600 transition hover:bg-rose-50">Fisik Bermasalah? Ajukan Return</a>
                            @endif
                        @elseif($order->status === 'selesai')
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-lime-50 border border-emerald-200 px-4 py-3 flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow shadow-emerald-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Selesai</p>
                                    <p class="text-[10px] font-bold text-emerald-600">Stok masuk etalase.</p>
                                </div>
                            </div>
                            @if($order->updated_at && $order->updated_at->diffInHours(now()) < 24)
                                @if($order->has_active_return)
                                    <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-center text-[11px] font-bold text-amber-700 hover:bg-amber-100">⏳ Return Aktif — Lihat Status</a>
                                @else
                                    <a href="{{ route('merchant.form-return', $order->id) }}" class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-2 text-center text-[11px] font-bold text-rose-600 hover:bg-rose-50">⚠ Masalah Setelah Cek? Ajukan Return</a>
                                @endif
                            @endif
                        @endif
                    </aside>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white/60 py-20 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 to-lime-100 text-emerald-500">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-800">Kategori Kosong</h3>
                <p class="mt-1 text-sm font-medium text-gray-500">Belum ada aktivitas logistik di tab ini.</p>
            </div>
        @endforelse
    </div>
```

**Important:** Keep the `{{-- MODAL POP-UP KONFIRMASI ... --}}` block and the outermost closing `</div>` unchanged.

- [ ] **Step 3: Visual verification**

Run: `php artisan view:clear` then load `/merchant/penerimaan` while logged in as a merchant account.
Expected: emerald hero, 4 stat tiles, restyled tabs, order cards with timeline + courier card + items list. Click "Barang Telah Diterima" → modal still appears.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/merchant/penerimaan.blade.php
git commit -m "feat(merchant): redesign halaman penerimaan premium emerald dengan timeline + stats"
```

---

## Task 12: Full QA + regression test sweep

**Files:** none modified.

- [ ] **Step 1: Run the full test suite**

Run: `vendor\bin\pest`
Expected: all tests green, including the new `TrackingTimelineServiceTest`.

- [ ] **Step 2: Manual happy-path check on both pages**

While logged in:
- As **pemasok**: visit `/pemasok/pengiriman`. Switch all 3 tabs. Open "Detail", "Atur Kurir & Kirim", "Cetak Surat Jalan" modals. Submit the kirim form on a `diproses_pemasok` order and confirm it transitions to `dikirim` and the courier card now shows in the new "Sedang Jalan" tab with WA button working.
- As **merchant**: visit `/merchant/penerimaan`. Switch tabs. On a `dikirim` order, click "Barang Telah Diterima" → confirm modal, click "Ya, Barang Sesuai", confirm status transitions to `selesai` and the order moves to "Telah Diterima" tab with kurir card showing "Telah tiba".

- [ ] **Step 3: Mobile responsive spot-check**

Open Chrome DevTools device toolbar (iPhone 14 Pro, 393px). Confirm both pages: hero stacks vertically, stat tiles become 2-column, order cards are single-column (timeline + sidebar stacked), kurir card readable, action buttons full width.

- [ ] **Step 4: Print sanity**

On pemasok page, open "Cetak Surat Jalan" modal for any `dikirim` order. Press `Ctrl+P`. Confirm only the surat jalan area prints (header + items + kurir block + signature), nothing else.

- [ ] **Step 5: Final no-op commit if anything got cleared**

If `php artisan view:clear` or `npm run build` produced any tracked file changes (compiled assets), commit them:

```bash
git status
# If clean, skip. If there are changes, commit them:
git add -A
git commit -m "chore: rebuild assets setelah redesign tracking"
```

---

## Notes for the engineer

- **Bahasa Indonesia comments** are required by `CLAUDE.md`. All inline comments in new PHP/Blade files should be Indonesian.
- **Eager loading**: existing pemasok `render()` and merchant `supplyOrders()` already eager-load `details`, `merchant.merchantProfile` / `pemasok.pemasokProfile`. Do not introduce N+1 — the per-row `trackingFor($order)` call on merchant side is pure PHP with no extra queries.
- **No DB::transaction** required for new code (read-only timeline derivation). The existing `simpanPengiriman` and `konfirmasiTerima` already handle their writes; leave them alone.
- **Tailwind dynamic classes**: the `accent` and `variant` props use a PHP map to keep class strings literal. Don't refactor to interpolated `bg-{{ $color }}-50` — those won't get compiled by Tailwind JIT.
- **WhatsApp link**: opens `wa.me/62…` in a new tab. Caller is responsible for URL-escaping any pre-filled text if they ever extend this; current implementation only normalizes the phone.

