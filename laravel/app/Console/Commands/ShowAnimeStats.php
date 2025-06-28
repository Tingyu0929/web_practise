<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\AnimePlatform;
use Illuminate\Console\Command;

class ShowAnimeStats extends Command
{
    protected $signature = 'anime:stats {--sample=5 : 顯示動漫範例數量}';
    protected $description = '顯示動漫資料統計和範例';

    public function handle()
    {
        $this->info('📊 動漫資料統計');
        $this->info('================');

        // 基本統計
        $totalAnimes = Anime::count();
        $totalPlatforms = AnimePlatform::count();
        $animesWithTitle = Anime::whereNotNull('title')->where('title', '!=', '')->count();

        $this->table(['項目', '數量'], [
            ['總動漫數量', $totalAnimes],
            ['有標題的動漫', $animesWithTitle],
            ['總平台關聯', $totalPlatforms],
            ['平均每個動漫的平台數', $totalAnimes > 0 ? round($totalPlatforms / $totalAnimes, 2) : 0],
        ]);

        // 顯示範例動漫
        $sampleCount = $this->option('sample');
        $this->info("\n🎬 動漫範例 (前 {$sampleCount} 個):");
        
        $sampleAnimes = Anime::whereNotNull('title')
            ->where('title', '!=', '')
            ->with('platforms')
            ->limit($sampleCount)
            ->get();

        foreach ($sampleAnimes as $anime) {
            $platforms = $anime->platforms->pluck('platform')->unique()->implode(', ');
            $regions = $anime->platforms->pluck('region')->unique()->implode(', ');
            
            $this->line("ID {$anime->id}: {$anime->title}");
            $this->line("  平台: {$platforms}");
            $this->line("  地區: {$regions}");
            $this->line("");
        }

        // 地區統計
        $regionStats = AnimePlatform::select('region')
            ->selectRaw('count(*) as count')
            ->groupBy('region')
            ->get();

        $this->info("📍 按地區統計:");
        foreach ($regionStats as $stat) {
            $this->line("  {$stat->region}: {$stat->count}");
        }

        // 平台統計
        $platformStats = AnimePlatform::select('platform')
            ->selectRaw('count(*) as count')
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->get();

        $this->info("\n📺 按平台統計:");
        foreach ($platformStats as $stat) {
            $this->line("  {$stat->platform}: {$stat->count}");
        }

        return 0;
    }
}
