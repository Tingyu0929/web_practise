<?php

namespace App\Console\Commands;

use App\Services\AnimeScraper;
use Illuminate\Console\Command;

class ScrapeAnimes extends Command
{
    protected $signature = 'anime:scrape
                           {--url=https://acgsecrets.hk/bangumi/202504/ : 要爬取的網頁URL}
                           {--clean-old : 清除舊資料}
                           {--detail : 顯示詳細統計}';

    protected $description = '爬取動漫資料並儲存到資料庫';

    private $scraper;

    public function __construct(AnimeScraper $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    public function handle()
    {
        $this->info('🎬 開始抓取動漫資料...');

        $url = $this->option('url');
        $this->line("📡 目標網址: {$url}");

        if ($this->option('clean-old')) {
            $this->warn('⚠️  清除舊資料...');
            if ($this->confirm('確定要清除所有現有的動漫資料嗎？')) {
                try {
                    // 正確的刪除順序：先刪除有外鍵的表，再刪除主表
                    \App\Models\AnimePlatform::query()->delete();
                    \App\Models\Anime::query()->delete();
                    $this->info('✅ 舊資料已清除');
                } catch (\Exception $e) {
                    $this->error('❌ 清除舊資料失敗: ' . $e->getMessage());
                    return 1;
                }
            }
        }

        try {
            $bar = $this->output->createProgressBar(1);
            $bar->start();

            $result = $this->scraper->scrapeAnimes($url);

            $bar->finish();
            $this->newLine(2);

            if (isset($result['error'])) {
                $this->error('❌ ' . $result['error']);
                return 1;
            }

            $this->info('✅ 抓取完成！');

            // 顯示結果表格
            $this->table([
                '項目', '數量'
            ], [
                ['新增動漫', $result['new_animes']],
                ['更新動漫', $result['updated_animes']],
                ['新增平台關聯', $result['new_platforms']],
                ['總處理數量', $result['total_processed']],
            ]);

            // 使用 Laravel 內建的 verbose 選項或自定義的 detail 選項
            if ($this->option('detail') || $this->option('verbose')) {
                $this->showDetailedStats();
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ 爬取失敗: ' . $e->getMessage());

            // 使用 Laravel 內建的 verbose 選項顯示詳細錯誤
            if ($this->option('verbose')) {
                $this->error('詳細錯誤：');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    private function showDetailedStats()
    {
        $this->info("\n📊 詳細統計:");

        $totalAnimes = \App\Models\Anime::count();
        $totalPlatforms = \App\Models\AnimePlatform::count();

        $this->line("總動漫數量: {$totalAnimes}");
        $this->line("總平台關聯: {$totalPlatforms}");

        // 按地區統計
        $regionStats = \App\Models\AnimePlatform::select('region')
            ->selectRaw('count(*) as count')
            ->groupBy('region')
            ->get();

        $this->line("\n按地區統計:");
        foreach ($regionStats as $stat) {
            $this->line("  {$stat->region}: {$stat->count}");
        }

        // 按平台統計
        $platformStats = \App\Models\AnimePlatform::select('platform')
            ->selectRaw('count(*) as count')
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->get();

        $this->line("\n按平台統計:");
        foreach ($platformStats as $stat) {
            $this->line("  {$stat->platform}: {$stat->count}");
        }
    }
}
