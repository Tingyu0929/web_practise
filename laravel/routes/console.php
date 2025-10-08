<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 每天中午12點執行爬蟲
Schedule::command('anime:scrape')
    ->dailyAt('12:00')
    ->timezone('Asia/Taipei')
    ->appendOutputTo(storage_path('logs/scheduler.log'));
