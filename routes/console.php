<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat Cut-Off SPKO Otomatis Setiap Hari Pukul 08:00 WIB
Schedule::command('spko:check-deadline')->dailyAt('08:00');
