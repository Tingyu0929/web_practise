<?php

namespace App\Console\Commands;

use App\Services\AnimeScraper;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Console\Command;

class TestScrapingFixes extends Command
{
    protected $signature = 'anime:test-fixes {--url=https://acgsecrets.hk/bangumi/202504/} {--sample=3}';
    protected $description = '測試修正後的爬蟲功能';

    public function handle()
    {
        $this->info('🧪 測試修正後的爬蟲功能...');

        try {
            $client = new Client([
                'timeout' => 30,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);

            $url = $this->option('url');
            $this->info("📡 測試網址: {$url}");

            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // 找到動漫容器
            $animeContainers = $crawler->filter('.acgs-anime');

            if ($animeContainers->count() === 0) {
                $this->warn('沒有找到 .acgs-anime 容器，嘗試使用圖片模式...');
                $animeContainers = $crawler->filter('img[src*="static.acgsecrets.hk"]')->each(function(Crawler $img) {
                    return $img->closest('div')->first();
                });
                $animeContainers = collect($animeContainers)->filter(function($container) {
                    return $container && $container->count() > 0;
                })->slice(0, $this->option('sample'));
            } else {
                $animeContainers = $animeContainers->slice(0, $this->option('sample'));
            }

            $this->info("找到 {$animeContainers->count()} 個動漫容器進行測試");

            // 測試提取功能
            $results = [];
            $animeContainers->each(function (Crawler $container, $i) use (&$results) {
                $this->line("\n" . str_repeat('=', 60));
                $this->info("測試動漫 " . ($i + 1));
                $this->line(str_repeat('=', 60));

                $result = $this->testExtraction($container);
                $results[] = $result;

                // 顯示結果
                $this->displayTestResult($result);
            });

            // 顯示總結
            $this->displaySummary($results);

        } catch (\Exception $e) {
            $this->error('測試失敗: ' . $e->getMessage());
        }

        return 0;
    }

    private function testExtraction(Crawler $container)
    {
        $result = [
            'title' => null,
            'type' => null,
            'categories' => [],
            'description' => null,
            'release_date' => null,
            'platforms' => [],
            'image_url' => null,
            'errors' => []
        ];

        try {
            // 測試圖片提取
            $imageNode = $container->filter('img[src*="static.acgsecrets.hk"]')->first();
            if ($imageNode->count() > 0) {
                $result['image_url'] = $imageNode->attr('src');
            } else {
                $result['errors'][] = '找不到動漫圖片';
            }

            // 測試標題提取
            $result['title'] = $this->testTitleExtraction($container);
            if (!$result['title']) {
                $result['errors'][] = '找不到標題';
            }

            // 測試類型提取
            $result['type'] = $this->testTypeExtraction($container);

            // 測試分類提取
            $result['categories'] = $this->testCategoriesExtraction($container);

            // 測試描述提取
            $result['description'] = $this->testDescriptionExtraction($container);

            // 測試日期提取
            $result['release_date'] = $this->testDateExtraction($container);

            // 測試平台提取
            $result['platforms'] = $this->testPlatformsExtraction($container);

        } catch (\Exception $e) {
            $result['errors'][] = '提取時發生異常: ' . $e->getMessage();
        }

        return $result;
    }

    private function testTitleExtraction(Crawler $container)
    {
        // 多種標題提取方法
        $methods = [
            'img_alt' => function($container) {
                $img = $container->filter('img')->first();
                return $img->count() > 0 ? trim($img->attr('alt')) : null;
            },
            'img_title' => function($container) {
                $img = $container->filter('img')->first();
                return $img->count() > 0 ? trim($img->attr('title')) : null;
            },
            'headings' => function($container) {
                $headings = $container->filter('h1, h2, h3, h4, h5');
                return $headings->count() > 0 ? trim($headings->first()->text()) : null;
            },
            'title_class' => function($container) {
                $titleEl = $container->filter('.title, .anime-title, [data-title]');
                return $titleEl->count() > 0 ? trim($titleEl->first()->text()) : null;
            }
        ];

        foreach ($methods as $method => $func) {
            $title = $func($container);
            if (!empty($title) && mb_strlen($title) > 2 && mb_strlen($title) < 100) {
                $this->line("  ✅ 標題 ({$method}): {$title}");
                return $title;
            }
        }

        $this->line("  ❌ 無法提取標題");
        return null;
    }

    private function testCategoriesExtraction(Crawler $container)
    {
        $categories = [];
        $classNames = $container->attr('class') ?: '';
        $text = $container->text();

        $this->line("  🏷️ 分類分析:");

        // 檢查常見分類關鍵字
        $genreKeywords = [
            'Action', 'Comedy', 'Drama', 'Romance', 'Fantasy', 'Sci-Fi',
            'Adventure', 'Mystery', 'Sports', 'Music', 'School', 'Magic',
            '動作', '喜劇', '劇情', '戀愛', '奇幻', '科幻', '校園'
        ];

        foreach ($genreKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $categories[] = $keyword;
                $this->line("    ✅ 找到分類: {$keyword}");
            }
        }

        if (empty($categories)) {
            $this->line("    ❌ 沒有找到分類");
        }

        return $categories;
    }

    private function testDescriptionExtraction(Crawler $container)
    {
        $this->line("  📝 描述分析:");

        try {
            $text = $container->text();

            // 方法1: 檢查"故事大綱"
            if (preg_match('/故事大綱(.+?)(?:主題曲|宣傳片|外部鏈接|配音員|製作人員|OP|ED|Cast|Staff)/us', $text, $matches)) {
                $description = trim($matches[1]);

                // 清理描述
                $description = $this->cleanDescriptionForTest($description);

                if (mb_strlen($description) > 20 && mb_strlen($description) < 1000) {
                    $preview = mb_substr($description, 0, 100) . '...';
                    $this->line("    ✅ 找到故事大綱: {$preview}");
                    return $description;
                }
            }

            // 方法2: 簡化匹配
            if (preg_match('/故事大綱(.{20,500}?)(?:[A-Z]{2,}|主題曲|配音員)/us', $text, $matches)) {
                $description = trim($matches[1]);
                $description = $this->cleanDescriptionForTest($description);

                if (mb_strlen($description) > 20) {
                    $preview = mb_substr($description, 0, 100) . '...';
                    $this->line("    ✅ 找到簡化故事大綱: {$preview}");
                    return $description;
                }
            }

            // 方法3: 尋找關鍵字
            $storyKeywords = ['故事大綱', '故事', '劇情', '簡介'];
            foreach ($storyKeywords as $keyword) {
                $pos = mb_strpos($text, $keyword);
                if ($pos !== false) {
                    $afterKeyword = mb_substr($text, $pos + mb_strlen($keyword));

                    $stopWords = ['主題曲', 'OP', 'ED', '配音員', '製作人員', '宣傳片'];
                    $description = $afterKeyword;

                    foreach ($stopWords as $stopWord) {
                        $stopPos = mb_strpos($afterKeyword, $stopWord);
                        if ($stopPos !== false) {
                            $description = mb_substr($afterKeyword, 0, $stopPos);
                            break;
                        }
                    }

                    $description = trim($description);
                    $description = $this->cleanDescriptionForTest($description);

                    if (mb_strlen($description) > 20 && mb_strlen($description) < 800) {
                        $preview = mb_substr($description, 0, 100) . '...';
                        $this->line("    ✅ 從 '{$keyword}' 找到描述: {$preview}");
                        return $description;
                    }
                }
            }

            // 方法4: 檢查原始文字中是否包含故事大綱
            if (mb_strpos($text, '故事大綱') !== false) {
                $this->line("    🔍 找到'故事大綱'關鍵字，但無法正確提取內容");

                // 顯示故事大綱周圍的文字以供調試
                $pos = mb_strpos($text, '故事大綱');
                $context = mb_substr($text, $pos, 200);
                $this->line("    📋 故事大綱周圍內容: " . $context);
            } else {
                $this->line("    ❌ 未找到'故事大綱'關鍵字");
            }

            $this->line("    ❌ 沒有找到描述");
            return null;

        } catch (\Exception $e) {
            $this->line("    ❌ 描述提取異常: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 測試用的描述清理方法
     */
    private function cleanDescriptionForTest($description)
    {
        $description = trim($description);
        $description = preg_replace('/^[^\p{L}]*/', '', $description);
        $description = preg_replace('/[A-Z]{3,}.*$/', '', $description);
        $description = preg_replace('/\s+/', ' ', $description);

        return trim($description);
    }

    private function testDateExtraction(Crawler $container)
    {
        $this->line("  📅 日期分析:");

        $text = $container->text();

        // 日期模式
        $patterns = [
            '/(\d{4})年(\d{1,2})月/' => 'YYYY年MM月',
            '/(\d{4})-(\d{1,2})-(\d{1,2})/' => 'YYYY-MM-DD',
            '/(\d{4})年/' => 'YYYY年'
        ];

        foreach ($patterns as $pattern => $format) {
            if (preg_match($pattern, $text, $matches)) {
                $this->line("    ✅ 找到日期模式 ({$format}): {$matches[0]}");
                return $matches[0];
            }
        }

        $this->line("    ❌ 沒有找到日期");
        return null;
    }

    private function testPlatformsExtraction(Crawler $container)
    {
        $platforms = [];
        $text = $container->text();

        $this->line("  📺 平台分析:");
        $this->line("    文字內容長度: " . mb_strlen($text));

        // 詳細的平台映射
        $platformMapping = [
            'Amazon Prime Video' => '香港',
            'Netflix' => '香港',
            'Disney+' => '香港',
            'Disney＋' => '香港',
            'Bilibili' => '中國大陸',
            '巴哈姆特動畫瘋' => '台灣',
            'myTV SUPER' => '香港',
            'Crunchyroll' => '香港',
            'Funimation' => '香港',
            'Hulu' => '香港',
        ];

        // 檢查每個平台，並顯示詳細結果
        foreach ($platformMapping as $platformText => $defaultRegion) {
            $found = mb_strpos($text, $platformText) !== false;

            if ($found) {
                $platforms[] = $platformText;

                // 獲取平台名稱周圍的上下文
                $pos = mb_strpos($text, $platformText);
                $contextStart = max(0, $pos - 30);
                $contextEnd = min(mb_strlen($text), $pos + mb_strlen($platformText) + 30);
                $context = mb_substr($text, $contextStart, $contextEnd - $contextStart);

                $this->line("    ✅ 找到平台: {$platformText} (預設地區: {$defaultRegion})");
                $this->line("    📍 上下文: ...{$context}...");
            } else {
                $this->line("    ❌ 未找到: {$platformText}");
            }
        }

        if (empty($platforms)) {
            $this->line("    ❌ 沒有找到任何平台");

            // 顯示一些文字內容以供調試
            $preview = mb_substr($text, 0, 300);
            $this->line("    📋 文字預覽: {$preview}...");
        } else {
            $this->line("    📊 找到 " . count($platforms) . " 個平台");
        }

        return $platforms;
    }

    private function displayTestResult($result)
    {
        $this->table(['項目', '結果'], [
            ['標題', $result['title'] ?: '❌ 未找到'],
            ['類型', $result['type'] ?: '❌ 未找到'],
            ['分類數量', count($result['categories'])],
            ['分類', implode(', ', $result['categories']) ?: '❌ 未找到'],
            ['描述', $result['description'] ? '✅ 已找到' : '❌ 未找到'],
            ['發布日期', $result['release_date'] ?: '❌ 未找到'],
            ['平台數量', count($result['platforms'])],
            ['平台', implode(', ', $result['platforms']) ?: '❌ 未找到'],
            ['圖片', $result['image_url'] ? '✅ 已找到' : '❌ 未找到'],
        ]);

        if (!empty($result['errors'])) {
            $this->warn('❌ 錯誤:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }
    }

    private function displaySummary($results)
    {
        $this->info("\n📊 測試總結:");

        $stats = [
            'total' => count($results),
            'has_title' => 0,
            'has_type' => 0,
            'has_categories' => 0,
            'has_description' => 0,
            'has_date' => 0,
            'has_platforms' => 0,
        ];

        foreach ($results as $result) {
            if ($result['title']) $stats['has_title']++;
            if ($result['type'] && $result['type'] !== 'unknown') $stats['has_type']++;
            if (!empty($result['categories'])) $stats['has_categories']++;
            if ($result['description']) $stats['has_description']++;
            if ($result['release_date']) $stats['has_date']++;
            if (!empty($result['platforms'])) $stats['has_platforms']++;
        }

        $this->table(['項目', '成功率'], [
            ['標題提取', $stats['has_title'] . '/' . $stats['total'] . ' (' . round($stats['has_title']/$stats['total']*100, 1) . '%)'],
            ['類型提取', $stats['has_type'] . '/' . $stats['total'] . ' (' . round($stats['has_type']/$stats['total']*100, 1) . '%)'],
            ['分類提取', $stats['has_categories'] . '/' . $stats['total'] . ' (' . round($stats['has_categories']/$stats['total']*100, 1) . '%)'],
            ['描述提取', $stats['has_description'] . '/' . $stats['total'] . ' (' . round($stats['has_description']/$stats['total']*100, 1) . '%)'],
            ['日期提取', $stats['has_date'] . '/' . $stats['total'] . ' (' . round($stats['has_date']/$stats['total']*100, 1) . '%)'],
            ['平台提取', $stats['has_platforms'] . '/' . $stats['total'] . ' (' . round($stats['has_platforms']/$stats['total']*100, 1) . '%)'],
        ]);

        if ($stats['has_description'] == 0) {
            $this->warn("\n⚠️ 描述提取失敗 - 可能需要分析網站的HTML結構");
        }

        if ($stats['has_date'] == 0) {
            $this->warn("⚠️ 日期提取失敗 - 可能需要調整日期模式");
        }

        if ($stats['has_type'] < $stats['total']) {
            $this->warn("⚠️ 部分類型提取失敗 - 檢查類型關鍵字匹配");
        }
    }

    private function testTypeExtraction(Crawler $container)
    {
        $classNames = $container->attr('class') ?: '';
        $text = $container->text();

        $this->line("  🎭 類型分析:");
        $this->line("    Class: " . substr($classNames, 0, 100));

        // 詳細的類型檢查
        $typeMapping = [
            '原創作品' => 'original',
            '原創' => 'original',
            '新作' => 'new',
            '新番' => 'new',
            '第二季' => 'continue',
            '第三季' => 'continue',
            'S2' => 'continue',
            'S3' => 'continue',
            '劇場版' => 'movie',
            'OVA' => 'ova'
        ];

        $foundType = null;
        foreach ($typeMapping as $keyword => $type) {
            if (mb_strpos($text, $keyword) !== false) {
                $this->line("    ✅ 從文字找到類型關鍵字: '{$keyword}' -> {$type}");
                $foundType = $type;

                // 顯示關鍵字的上下文
                $pos = mb_strpos($text, $keyword);
                $context = mb_substr($text, max(0, $pos - 20), 60);
                $this->line("    📍 上下文: ...{$context}...");
                break;
            }
        }

        if (!$foundType) {
            // 檢查特殊線索
            if (mb_strpos($text, '高期待') !== false) {
                $this->line("    ⚠️ 根據'高期待'推測為新作: new");
                $foundType = 'new';
            } else {
                $this->line("    ⚠️ 無法確定類型，使用預設: tv");
                $foundType = 'tv';
            }
        }

        return $foundType;
    }
}
