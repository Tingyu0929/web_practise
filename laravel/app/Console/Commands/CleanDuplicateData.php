<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\AnimePlatform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateData extends Command
{
    protected $signature = 'anime:clean-duplicates 
                           {--force : 強制清理不詢問}
                           {--dry-run : 只顯示要清理的資料，不實際刪除}';

    protected $description = '清理重複的動漫和平台資料';

    public function handle()
    {
        $this->info('🧹 開始清理重複資料...');

        if ($this->option('dry-run')) {
            $this->warn('🔍 僅預覽模式 - 不會實際刪除資料');
        }

        // 1. 清理重複的平台關聯
        $this->cleanDuplicatePlatforms();

        // 2. 清理重複的動漫
        $this->cleanDuplicateAnimes();

        // 3. 清理無效的動漫（沒有標題的）
        $this->cleanInvalidAnimes();

        $this->info('✅ 清理完成！');

        // 顯示統計
        $this->showStatistics();

        return 0;
    }

    private function cleanDuplicatePlatforms()
    {
        $this->info("\n📺 清理重複的平台關聯...");

        // 找出重複的 anime_id + region + platform 組合
        $duplicates = DB::select("
            SELECT anime_id, region, platform, COUNT(*) as count, 
                   GROUP_CONCAT(id ORDER BY id) as ids
            FROM anime_platforms 
            GROUP BY anime_id, region, platform 
            HAVING COUNT(*) > 1
        ");

        if (empty($duplicates)) {
            $this->line('沒有找到重複的平台關聯');
            return;
        }

        $this->table(['動漫ID', '地區', '平台', '重複數量', 'IDs'], array_map(function($dup) {
            return [$dup->anime_id, $dup->region, $dup->platform, $dup->count, $dup->ids];
        }, $duplicates));

        if ($this->option('dry-run')) {
            $this->line("預覽：將刪除 " . count($duplicates) . " 組重複的平台關聯");
            return;
        }

        if (!$this->option('force') && !$this->confirm('確定要刪除這些重複的平台關聯嗎？')) {
            return;
        }

        $deletedCount = 0;
        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            // 保留第一個，刪除其他的
            $idsToDelete = array_slice($ids, 1);
            
            $deletedCount += AnimePlatform::whereIn('id', $idsToDelete)->delete();
        }

        $this->info("✅ 已刪除 {$deletedCount} 個重複的平台關聯");
    }

    private function cleanDuplicateAnimes()
    {
        $this->info("\n🎬 清理重複的動漫...");

        // 找出重複標題的動漫
        $duplicates = DB::select("
            SELECT title, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
            FROM animes 
            WHERE title IS NOT NULL AND title != ''
            GROUP BY title 
            HAVING COUNT(*) > 1
        ");

        if (empty($duplicates)) {
            $this->line('沒有找到重複的動漫');
            return;
        }

        $this->table(['標題', '重複數量', 'IDs'], array_map(function($dup) {
            return [$dup->title, $dup->count, $dup->ids];
        }, $duplicates));

        if ($this->option('dry-run')) {
            $this->line("預覽：將合併 " . count($duplicates) . " 組重複的動漫");
            return;
        }

        if (!$this->option('force') && !$this->confirm('確定要合併這些重複的動漫嗎？')) {
            return;
        }

        $mergedCount = 0;
        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            $mainAnimeId = $ids[0]; // 保留第一個
            $duplicateIds = array_slice($ids, 1);

            DB::transaction(function() use ($mainAnimeId, $duplicateIds, &$mergedCount) {
                // 將重複動漫的平台關聯轉移到主動漫
                AnimePlatform::whereIn('anime_id', $duplicateIds)
                    ->update(['anime_id' => $mainAnimeId]);

                // 刪除重複的動漫
                $deleted = Anime::whereIn('id', $duplicateIds)->delete();
                $mergedCount += $deleted;
            });
        }

        $this->info("✅ 已合併 {$mergedCount} 個重複的動漫");
    }

    private function cleanInvalidAnimes()
    {
        $this->info("\n🗑️ 清理無效的動漫（無標題）...");

        $invalidAnimes = Anime::where(function($query) {
            $query->whereNull('title')
                  ->orWhere('title', '')
                  ->orWhere('title', 'LIKE', '%NULL%');
        })->get();

        if ($invalidAnimes->count() === 0) {
            $this->line('沒有找到無效的動漫');
            return;
        }

        $this->table(['ID', '標題', '圖片URL'], $invalidAnimes->map(function($anime) {
            return [$anime->id, $anime->title ?: '(空)', substr($anime->image_url ?: '', 0, 50)];
        })->toArray());

        if ($this->option('dry-run')) {
            $this->line("預覽：將刪除 " . $invalidAnimes->count() . " 個無效動漫");
            return;
        }

        if (!$this->option('force') && !$this->confirm('確定要刪除這些無效的動漫嗎？')) {
            return;
        }

        $deletedCount = 0;
        foreach ($invalidAnimes as $anime) {
            // 先刪除相關的平台關聯
            $anime->platforms()->delete();
            // 再刪除動漫本身
            $anime->delete();
            $deletedCount++;
        }

        $this->info("✅ 已刪除 {$deletedCount} 個無效的動漫");
    }

    private function showStatistics()
    {
        $this->info("\n📊 當前統計:");

        $totalAnimes = Anime::count();
        $totalPlatforms = AnimePlatform::count();
        $animesWithTitle = Anime::whereNotNull('title')->where('title', '!=', '')->count();
        
        $this->table(['項目', '數量'], [
            ['總動漫數量', $totalAnimes],
            ['有標題的動漫', $animesWithTitle],
            ['總平台關聯', $totalPlatforms],
            ['平均每個動漫的平台數', $totalAnimes > 0 ? round($totalPlatforms / $totalAnimes, 2) : 0],
        ]);

        // 按地區統計
        $regionStats = AnimePlatform::select('region')
            ->selectRaw('count(*) as count')
            ->groupBy('region')
            ->get();

        if ($regionStats->count() > 0) {
            $this->line("\n按地區統計:");
            foreach ($regionStats as $stat) {
                $this->line("  {$stat->region}: {$stat->count}");
            }
        }

        // 按平台統計
        $platformStats = AnimePlatform::select('platform')
            ->selectRaw('count(*) as count')
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->get();

        if ($platformStats->count() > 0) {
            $this->line("\n按平台統計:");
            foreach ($platformStats as $stat) {
                $this->line("  {$stat->platform}: {$stat->count}");
            }
        }
    }
}
