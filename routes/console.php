<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire transaksi POS QR pending > 15 menit + kembalikan stok kantin.
Schedule::command('pos:expire-pending --minutes=15')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
