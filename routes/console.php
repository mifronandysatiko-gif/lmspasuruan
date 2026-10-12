<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Pemrosesan berkala antrean email & pekerjaan latar belakang (Database Queue Worker).
 * Berjalan setiap menit, memproses pesan yang tertunda dan berhenti saat antrean kosong.
 */
Schedule::command('queue:work --stop-when-empty --tries=25 --delay=60 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

