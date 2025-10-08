<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        Commands\ScrapeAnimes::class,
        Commands\ShowAnimeStats::class,
        Commands\CleanDuplicateData::class,
        Commands\TestScrapingFixes::class,
        Commands\DebugHtmlForFixes::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 每天中午12點執行爬蟲
        $schedule->command('anime:scrape')
            ->dailyAt('12:00')
            ->timezone('Asia/Taipei')
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
