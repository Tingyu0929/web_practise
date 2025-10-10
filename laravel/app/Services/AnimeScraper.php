<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\AnimePlatform;
use App\Models\ExternalLink;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnimeScraper
{
    private $client;
    private $baseUrl = 'https://acgsecrets.hk';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'zh-TW,zh;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ],
            'curl' => [
                CURLOPT_ENCODING => '',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]
        ]);
    }

    /**
     * 獲取 HTML 內容，包含多種備用方法
     */
    private function fetchHtml($url)
    {
        // 方法 1: 使用 Guzzle
        try {
            Log::info('嘗試使用 Guzzle 爬取', ['url' => $url]);

            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();

            // 確保正確的 UTF-8 編碼
            if (!mb_check_encoding($html, 'UTF-8')) {
                $html = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'], true));
            }

            return $html;

        } catch (\Exception $e) {
            Log::warning('Guzzle 請求失敗', ['error' => $e->getMessage()]);
        }

        // 方法 2: 使用更簡單的 Guzzle 設定
        try {
            Log::info('嘗試使用簡化 Guzzle 設定');

            $simpleClient = new Client([
                'timeout' => 30,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Laravel/10.0)'
                ]
            ]);

            $response = $simpleClient->get($url);
            $html = $response->getBody()->getContents();

            // 確保正確的 UTF-8 編碼
            if (!mb_check_encoding($html, 'UTF-8')) {
                $html = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'], true));
            }

            return $html;

        } catch (\Exception $e) {
            Log::warning('簡化 Guzzle 請求失敗', ['error' => $e->getMessage()]);
        }

        // 方法 3: 使用 PHP 的 file_get_contents
        try {
            Log::info('嘗試使用 file_get_contents');

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: Mozilla/5.0 (compatible; Laravel/10.0)',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ],
                    'timeout' => 30,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $html = file_get_contents($url, false, $context);

            if ($html !== false) {
                // 確保正確的 UTF-8 編碼
                if (!mb_check_encoding($html, 'UTF-8')) {
                    $html = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'], true));
                }
                return $html;
            }

        } catch (\Exception $e) {
            Log::warning('file_get_contents 失敗', ['error' => $e->getMessage()]);
        }

        // 方法 4: 使用 cURL 直接調用
        try {
            Log::info('嘗試使用 cURL 直接調用');

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Laravel/10.0)',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: zh-TW,zh;q=0.8,en-US;q=0.5,en;q=0.3',
                ],
            ]);

            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($html !== false && $httpCode === 200) {
                // 確保正確的 UTF-8 編碼
                if (!mb_check_encoding($html, 'UTF-8')) {
                    $html = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'ASCII', 'Windows-1252'], true));
                }
                return $html;
            }

            if ($error) {
                Log::warning('cURL 直接調用錯誤', ['error' => $error, 'http_code' => $httpCode]);
            }

        } catch (\Exception $e) {
            Log::warning('cURL 直接調用失敗', ['error' => $e->getMessage()]);
        }

        return false;
    }

    public function scrapeAnimes($url)
    {
        try {
            Log::info('開始爬取動漫資料', ['url' => $url]);

            // 嘗試主要請求方法
            $html = $this->fetchHtml($url);

            if (!$html) {
                throw new \Exception('無法獲取網頁內容');
            }

            // 儲存 HTML 以供調試
            $debugPath = storage_path('app/debug_anime_' . date('Y-m-d_H-i-s') . '.html');
            file_put_contents($debugPath, $html);
            Log::info('HTML 已儲存', ['path' => $debugPath]);

            $crawler = new Crawler($html);

            // 提取動漫資料
            $animeData = $this->extractAnimeData($crawler);

            if (empty($animeData)) {
                Log::warning('沒有提取到動漫資料');
                return ['error' => '沒有找到動漫資料'];
            }

            // 儲存資料到資料庫
            $result = $this->saveAnimeData($animeData);

            Log::info('爬取完成', $result);
            return $result;

        } catch (\Exception $e) {
            Log::error('爬取失敗', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function extractAnimeData(Crawler $crawler)
    {
        $animeList = [];

        try {
            // 方法 1: 尋找 acgs-anime 容器
            $animeContainers = $crawler->filter('.acgs-anime');

            if ($animeContainers->count() > 0) {
                Log::info('找到 acgs-anime 容器', ['count' => $animeContainers->count()]);

                $animeContainers->each(function (Crawler $container, $i) use (&$animeList) {
                    $animeData = $this->extractAnimeFromContainer($container);
                    if ($animeData) {
                        $animeList[] = $animeData;
                    }
                });
            } else {
                // 方法 2: 使用更通用的方法
                Log::info('嘗試使用通用方法提取');
                $animeList = $this->extractAnimeByImagePattern($crawler);
            }

        } catch (\Exception $e) {
            Log::error('提取動漫資料失敗', ['message' => $e->getMessage()]);
        }

        return $animeList;
    }

    /**
     * 通過圖片模式提取動漫資料
     */
    private function extractAnimeByImagePattern(Crawler $crawler)
    {
        $animeList = [];

        try {
            // 找所有動漫圖片
            $images = $crawler->filter('img[src*="static.acgsecrets.hk"]');

            Log::info('找到動漫圖片', ['count' => $images->count()]);

            $images->each(function (Crawler $img, $i) use (&$animeList) {
                // 限制處理數量以避免超時
                if ($i >= 50) {
                    return false;
                }

                try {
                    $imageUrl = $img->attr('src');

                    // 找到包含此圖片的最近的容器
                    $container = $img->closest('div.acgs-anime, div.card-like, div[class*="anime"]')->first();

                    if ($container->count() === 0) {
                        $container = $img->closest('div')->first();
                    }

                    if ($container->count() > 0) {
                        $animeData = $this->extractAnimeFromContainer($container);
                        if ($animeData) {
                            $animeList[] = $animeData;
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('處理圖片失敗', [
                        'index' => $i,
                        'message' => $e->getMessage()
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('按圖片模式提取失敗', ['message' => $e->getMessage()]);
        }

        return $animeList;
    }

    private function extractAnimeFromContainer(Crawler $container)
{
    try {
        // 提取動漫圖片
        $imageNode = $container->filter('img[src*="static.acgsecrets.hk"]')->first();
        if ($imageNode->count() === 0) {
            return null;
        }

        $imageUrl = $imageNode->attr('src');

        // 提取標題
        $title = $this->extractTitle($container);
        if (!$title) {
            Log::warning('無法提取標題', ['image' => basename($imageUrl)]);
            return null;
        }

        // 提取分類和類型
        $categories = $this->extractCategories($container);
        $type = $this->extractType($container);

        // 修正：實作描述和發布日期提取
        $description = $this->extractDescription($container);
        $releaseDate = $this->extractReleaseDate($container);

        // 提取播放平台資訊
        $platforms = $this->extractPlatforms($container);

        // 嘗試找到詳細頁面連結
        $detailUrl = $this->extractDetailUrl($container);
        $detailData = null;

        if ($detailUrl) {
            Log::info('找到詳細頁面', ['url' => $detailUrl]);
            $detailData = $this->fetchDetailPageData($detailUrl);
        }

        // 新增：提取額外資訊（優先使用詳細頁面的資料）
        $weeklySchedule = $detailData['weekly_schedule'] ?? $this->extractWeeklySchedule($container);
        $voiceActors = $detailData['voice_actors'] ?? $this->extractVoiceActors($container);
        $copyright = $detailData['copyright'] ?? $this->extractCopyright($container);
        $trailerUrl = $detailData['trailer_url'] ?? $this->extractTrailerUrl($container);
        $videoLinks = $detailData['video_links'] ?? $this->extractVideoLinks($container);
        $staff = $detailData['staff'] ?? $this->extractStaff($container);
        $externalLinks = $detailData['external_links'] ?? null;

        // 如果詳細頁面有更完整的平台資訊，使用詳細頁面的
        if (!empty($detailData['platforms'])) {
            $platforms = $detailData['platforms'];
        }

        Log::info('成功提取動漫資料', [
            'title' => $title,
            'type' => $type,
            'categories_count' => count($categories),
            'platforms_count' => count($platforms),
            'has_description' => !empty($description),
            'has_release_date' => !empty($releaseDate),
            'has_weekly_schedule' => !empty($weeklySchedule),
            'has_voice_actors' => !empty($voiceActors),
            'has_copyright' => !empty($copyright),
            'has_trailer' => !empty($trailerUrl),
            'has_external_links' => !empty($externalLinks)
        ]);

        return [
            'title' => $this->cleanUtf8($title),
            'image_url' => $imageUrl,
            'categories' => $categories,
            'type' => $type,
            'description' => $description ? $this->cleanUtf8($description) : null,
            'release_date' => $releaseDate,
            'platforms' => $platforms,
            'source_url' => $detailUrl ?: request()->url(),
            'weekly_schedule' => $weeklySchedule ? $this->cleanUtf8($weeklySchedule) : null,
            'voice_actors' => $voiceActors,
            'copyright' => $copyright ? $this->cleanUtf8($copyright) : null,
            'trailer_url' => $trailerUrl,
            'video_links' => $videoLinks,
            'staff' => $staff,
            'external_links' => $externalLinks,
        ];

    } catch (\Exception $e) {
        Log::error('從容器提取動漫資料失敗', ['message' => $e->getMessage()]);
        return null;
    }
}

    /**
     * 新增：提取描述資訊
     */
    private function extractDescription(Crawler $container)
    {
        try {
            $text = $container->text();

            Log::debug('描述提取開始', ['text_length' => strlen($text), 'text_preview' => substr($text, 0, 300)]);

            // 方法1: 更精確的"故事大綱"匹配
            if (preg_match('/故事大綱(.+?)(?:主題曲|宣傳片|外部鏈接|配音員|製作人員|OP|ED|Cast|Staff)/us', $text, $matches)) {
                $description = trim($matches[1]);

                Log::debug('故事大綱正則匹配', ['raw_match' => $description, 'length' => mb_strlen($description)]);

                // 清理描述
                $description = $this->cleanDescription($description);

                if (mb_strlen($description) > 20 && mb_strlen($description) < 1000) {
                    Log::info('從故事大綱提取描述', ['description' => $description, 'length' => mb_strlen($description)]);
                    return $description;
                }
            }

            // 方法2: 簡化的故事大綱匹配（如果上面的太嚴格）
            if (preg_match('/故事大綱(.{20,500}?)(?:[A-Z]{2,}|主題曲|配音員)/us', $text, $matches)) {
                $description = trim($matches[1]);
                $description = $this->cleanDescription($description);

                if (mb_strlen($description) > 20) {
                    Log::info('從簡化故事大綱提取描述', ['description' => $description]);
                    return $description;
                }
            }

            // 方法3: 尋找"故事"相關關鍵字後的長文字
            $storyKeywords = ['故事大綱', '故事簡介', '劇情', '簡介', '故事'];
            foreach ($storyKeywords as $keyword) {
                $pos = mb_strpos($text, $keyword);
                if ($pos !== false) {
                    // 從關鍵字後開始截取
                    $afterKeyword = mb_substr($text, $pos + mb_strlen($keyword));

                    // 尋找到下一個明顯的分隔標識
                    $stopWords = ['主題曲', 'OP', 'ED', '配音員', '製作人員', '宣傳片', '外部鏈接', 'Cast', 'Staff'];
                    $description = $afterKeyword;

                    foreach ($stopWords as $stopWord) {
                        $stopPos = mb_strpos($afterKeyword, $stopWord);
                        if ($stopPos !== false) {
                            $description = mb_substr($afterKeyword, 0, $stopPos);
                            break;
                        }
                    }

                    $description = trim($description);
                    $description = $this->cleanDescription($description);

                    if (mb_strlen($description) > 20 && mb_strlen($description) < 800) {
                        Log::info('從關鍵字提取描述', ['keyword' => $keyword, 'description' => $description]);
                        return $description;
                    }
                }
            }

            // 方法4: 智能提取包含故事關鍵字的長段落
            $lines = preg_split('/\n+/', $text);
            foreach ($lines as $line) {
                $line = trim($line);

                // 檢查是否是描述性的長句子
                if (mb_strlen($line) > 50 && mb_strlen($line) < 500) {
                    $storyIndicators = [
                        '女高中生', '少女', '少年', '主角', '故事', '世界', '學校',
                        '偶遇', '捲入', '駕駛', '戰鬥', '冒險', '魔法', '異世界',
                        '生活', '面前', '出現', '神秘'
                    ];

                    $hasStoryContent = false;
                    foreach ($storyIndicators as $indicator) {
                        if (mb_strpos($line, $indicator) !== false) {
                            $hasStoryContent = true;
                            break;
                        }
                    }

                    // 檢查是否有完整句子結構
                    if ($hasStoryContent && (mb_strpos($line, '。') !== false || mb_strpos($line, '...') !== false)) {
                        $description = $this->cleanDescription($line);
                        if (mb_strlen($description) > 20) {
                            Log::info('智能提取描述', ['description' => $description]);
                            return $description;
                        }
                    }
                }
            }

            Log::warning('所有描述提取方法都失敗');
            return null;

        } catch (\Exception $e) {
            Log::error('描述提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 新增：提取發布日期
     */
    private function extractReleaseDate(Crawler $container)
    {
        try {
            $text = $container->text();

            // 日期模式（按優先級排序）
            $datePatterns = [
                // 完整日期格式
                '/(\d{4})年(\d{1,2})月(\d{1,2})日/' => function($matches) {
                    return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
                },

                // 年月格式
                '/(\d{4})年(\d{1,2})月/' => function($matches) {
                    return sprintf('%04d-%02d-01', $matches[1], $matches[2]);
                },

                // 播放日期格式：4月9日起
                '/(\d{1,2})月(\d{1,2})日起/' => function($matches) {
                    $currentYear = date('Y');
                    return sprintf('%04d-%02d-%02d', $currentYear, $matches[1], $matches[2]);
                },

                // 只有月份：4月日起
                '/(\d{1,2})月.*?日起/' => function($matches) {
                    $currentYear = date('Y');
                    return sprintf('%04d-%02d-01', $currentYear, $matches[1]);
                },

                // 年份格式
                '/(\d{4})年/' => function($matches) {
                    return sprintf('%04d-01-01', $matches[1]);
                },

                // 標準格式
                '/(\d{4})-(\d{1,2})-(\d{1,2})/' => function($matches) {
                    return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
                },
            ];

            foreach ($datePatterns as $pattern => $formatter) {
                if (preg_match($pattern, $text, $matches)) {
                    $date = $formatter($matches);
                    Log::info('成功提取日期', ['pattern' => $pattern, 'date' => $date, 'original' => $matches[0]]);
                    return $date;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('日期提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 改進：類型提取方法
     */
    private function extractType(Crawler $container)
    {
        try {
            $text = $container->text();
            $classNames = $container->attr('class') ?: '';

            Log::debug('類型提取開始', [
                'text_preview' => mb_substr($text, 0, 300),
                'class' => $classNames
            ]);

            // 改進的類型映射，按優先級排序
            $typeMapping = [
                // 優先級1: 明確的續作標識
                '第二季' => 'continue',
                '第三季' => 'continue',
                '第四季' => 'continue',
                '第五季' => 'continue',
                '續作' => 'continue',
                'Season 2' => 'continue',
                'Season 3' => 'continue',
                'Season 4' => 'continue',
                'S2' => 'continue',
                'S3' => 'continue',
                'S4' => 'continue',

                // 優先級2: 原創作品（放在新作前面，因為更具體）
                '原創作品' => 'original',
                '原創' => 'original',
                'オリジナル' => 'original',
                'Original' => 'original',

                // 優先級3: 新作標識
                '新番' => 'new',
                '新作' => 'new',

                // 優先級4: 改編類型
                '漫畫改編' => 'comic',
                '小說改編' => 'novel',
                '遊戲改編' => 'game',
                '劇場版' => 'movie',
                '電影版' => 'movie',
                'Movie' => 'movie',
                'OVA' => 'ova',
                'OAD' => 'ova',
            ];

            // 詳細檢查每個關鍵字
            foreach ($typeMapping as $keyword => $type) {
                $foundInText = mb_strpos($text, $keyword) !== false;
                $foundInClass = stripos($classNames, $keyword) !== false;

                if ($foundInText || $foundInClass) {
                    $location = $foundInText ? '文字' : 'class';
                    Log::info('成功識別類型', [
                        'keyword' => $keyword,
                        'type' => $type,
                        'location' => $location,
                        'context' => $this->getKeywordContext($text, $keyword)
                    ]);
                    return $type;
                }
            }

            // 特殊檢查："高期待"通常表示新作
            if (mb_strpos($text, '高期待') !== false) {
                Log::info('根據"高期待"推測為新作');
                return 'new';
            }

            // 如果找到任何季數相關的標識，但不是明確的續作
            if (preg_match('/第[一二三四五六七八九十]季|Season\s*[1-9]|S[1-9]/u', $text)) {
                Log::info('找到季數標識，判斷為TV動畫');
                return 'tv';
            }

            // 檢查是否包含年份，可能是新作
            if (preg_match('/202[4-9]年/', $text)) {
                Log::info('包含近期年份，推測為新作');
                return 'new';
            }

            // 預設為TV動畫
            Log::info('使用預設類型TV', ['available_text' => mb_substr($text, 0, 200)]);
            return 'tv';

        } catch (\Exception $e) {
            Log::error('類型提取異常', ['message' => $e->getMessage()]);
            return 'unknown';
        }
    }

    /**
     * 獲取關鍵字的上下文
     */
    private function getKeywordContext($text, $keyword)
    {
        $pos = mb_strpos($text, $keyword);
        if ($pos !== false) {
            $start = max(0, $pos - 20);
            $length = min(60, mb_strlen($text) - $start);
            return mb_substr($text, $start, $length);
        }
        return '';
    }

    /**
     * 改進：分類提取方法
     */
    private function extractCategories(Crawler $container)
    {
        $categories = [];

        try {
            $classNames = $container->attr('class') ?: '';
            $text = $container->text();

            Log::debug('提取分類 - 開始分析', [
                'class' => $classNames,
                'text_snippet' => substr($text, 0, 200)
            ]);

            // 方法 1: 從class中提取分類 - 更寬鬆的模式
            $genrePatterns = [
                '/genre-(\w+)/i',
                '/category-(\w+)/i',
                '/tag-(\w+)/i',
                '/(action|comedy|drama|romance|fantasy|sci-fi|adventure|mystery|thriller|horror|sports|music|school|magic|mecha|historical)/i'
            ];

            foreach ($genrePatterns as $pattern) {
                if (preg_match_all($pattern, $classNames, $matches)) {
                    $categories = array_merge($categories, $matches[1]);
                }
            }

            // 方法 2: 從文字中提取分類 - 擴展中英文關鍵字
            $genreKeywords = [
                // 英文分類
                'Action' => 'Action', 'Comedy' => 'Comedy', 'Drama' => 'Drama',
                'Romance' => 'Romance', 'Fantasy' => 'Fantasy', 'Sci-Fi' => 'Sci-Fi',
                'Adventure' => 'Adventure', 'Mystery' => 'Mystery', 'Thriller' => 'Thriller',
                'Horror' => 'Horror', 'Sports' => 'Sports', 'Music' => 'Music',
                'School' => 'School', 'Magic' => 'Magic', 'Mecha' => 'Mecha',
                'Historical' => 'Historical', 'Slice of Life' => 'Slice of Life',
                'Supernatural' => 'Supernatural', 'Psychological' => 'Psychological',

                // 中文分類
                '動作' => 'Action', '喜劇' => 'Comedy', '劇情' => 'Drama',
                '戀愛' => 'Romance', '奇幻' => 'Fantasy', '科幻' => 'Sci-Fi',
                '冒險' => 'Adventure', '懸疑' => 'Mystery', '驚悚' => 'Thriller',
                '恐怖' => 'Horror', '運動' => 'Sports', '音樂' => 'Music',
                '校園' => 'School', '魔法' => 'Magic', '機甲' => 'Mecha',
                '歷史' => 'Historical', '日常' => 'Slice of Life', '治癒' => 'Healing',
                '超自然' => 'Supernatural', '心理' => 'Psychological'
            ];

            foreach ($genreKeywords as $keyword => $genre) {
                if (stripos($text, $keyword) !== false) {
                    $categories[] = $genre;
                    Log::debug('找到分類關鍵字', ['keyword' => $keyword, 'genre' => $genre]);
                }
            }

            // 去除重複並限制數量
            $categories = array_unique($categories);
            $categories = array_slice($categories, 0, 10); // 最多10個分類

            Log::info('成功提取分類', ['categories' => $categories]);

        } catch (\Exception $e) {
            Log::error('提取分類失敗', ['message' => $e->getMessage()]);
        }

        return $categories;
    }

    /**
     * 修正：儲存資料方法，包含所有欄位
     */
    private function saveAnimeData($animeData)
    {
        $newAnimes = 0;
        $updatedAnimes = 0;
        $newPlatforms = 0;

        DB::beginTransaction();

        try {
            foreach ($animeData as $data) {
                // 檢查動漫是否已存在
                $anime = Anime::where('title', $data['title'])
                    ->orWhere('image_url', $data['image_url'])
                    ->first();

                if (!$anime) {
                    // 創建新動漫 - 包含所有欄位
                    $anime = Anime::create([
                        'title' => $data['title'],
                        'image_url' => $data['image_url'],
                        'categories' => $data['categories'],
                        'type' => $data['type'],
                        'description' => $data['description'],
                        'release_date' => $data['release_date'],
                        'source_url' => $data['source_url'],
                        'weekly_schedule' => $data['weekly_schedule'] ?? null,
                        'voice_actors' => $data['voice_actors'] ?? null,
                        'copyright' => $data['copyright'] ?? null,
                        'trailer_url' => $data['trailer_url'] ?? null,
                        'video_links' => $data['video_links'] ?? null,
                        'staff' => $data['staff'] ?? null,
                        'external_links' => $data['external_links'] ?? null,
                        'status' => 'active'
                    ]);
                    $newAnimes++;

                    Log::info('創建新動漫', [
                        'id' => $anime->id,
                        'title' => $anime->title,
                        'type' => $anime->type,
                        'categories_count' => count($anime->categories ?? [])
                    ]);
                } else {
                    // 更新現有動漫 - 包含所有欄位
                    $anime->update([
                        'categories' => array_unique(array_merge(
                            $anime->categories ?? [],
                            $data['categories']
                        )),
                        'type' => $data['type'],
                        'description' => $data['description'] ?: $anime->description,
                        'release_date' => $data['release_date'] ?: $anime->release_date,
                        'source_url' => $data['source_url'],
                        'weekly_schedule' => $data['weekly_schedule'] ?? $anime->weekly_schedule,
                        'voice_actors' => $data['voice_actors'] ?? $anime->voice_actors,
                        'copyright' => $data['copyright'] ?? $anime->copyright,
                        'trailer_url' => $data['trailer_url'] ?? $anime->trailer_url,
                        'video_links' => $data['video_links'] ?? $anime->video_links,
                        'staff' => $data['staff'] ?? $anime->staff,
                        'external_links' => $data['external_links'] ?? $anime->external_links,
                    ]);
                    $updatedAnimes++;

                    Log::info('更新動漫', [
                        'id' => $anime->id,
                        'title' => $anime->title,
                        'type' => $anime->type
                    ]);
                }

                // 儲存平台資訊
                foreach ($data['platforms'] as $platformData) {
                    $exists = AnimePlatform::where('anime_id', $anime->id)
                        ->where('region', $platformData['region'])
                        ->where('platform', $platformData['platform'])
                        ->exists();

                    if (!$exists) {
                        AnimePlatform::create([
                            'anime_id' => $anime->id,
                            'region' => $platformData['region'],
                            'platform' => $platformData['platform'],
                            'availability_status' => $platformData['availability_status'],
                            'platform_url' => $platformData['notes'] ?? null
                        ]);
                        $newPlatforms++;
                    }
                }

                // 儲存外部連結到獨立表格
                if (!empty($data['external_links'])) {
                    // 先刪除舊的外部連結
                    ExternalLink::where('anime_id', $anime->id)->delete();

                    $order = 0;
                    foreach ($data['external_links'] as $linkData) {
                        $type = ExternalLink::identifyTypeFromUrl($linkData['url']);
                        $language = null;

                        // 識別維基百科語言
                        if ($type === 'wikipedia') {
                            if (strpos($linkData['url'], 'zh.wikipedia') !== false) {
                                $language = 'zh';
                            } elseif (strpos($linkData['url'], 'ja.wikipedia') !== false) {
                                $language = 'ja';
                            } elseif (strpos($linkData['url'], 'en.wikipedia') !== false) {
                                $language = 'en';
                            }
                        }

                        ExternalLink::create([
                            'anime_id' => $anime->id,
                            'type' => $type,
                            'name' => $linkData['name'],
                            'url' => $linkData['url'],
                            'language' => $language,
                            'order' => $order++
                        ]);
                    }

                    Log::info('成功儲存外部連結', [
                        'anime_id' => $anime->id,
                        'count' => count($data['external_links'])
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'new_animes' => $newAnimes,
                'updated_animes' => $updatedAnimes,
                'new_platforms' => $newPlatforms,
                'total_processed' => count($animeData)
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('儲存資料失敗', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function extractTitle(Crawler $container)
    {
        try {
            $text = $container->text();

            // 方法1: 從文字開頭提取標題（通常動漫標題會重複出現在開頭）
            $lines = explode("\n", trim($text));
            if (!empty($lines)) {
                $firstLine = trim($lines[0]);

                // 尋找重複的標題模式
                if (preg_match('/^(.+?)\1/', $firstLine, $matches)) {
                    $title = trim($matches[1]);
                    if (mb_strlen($title) > 2 && mb_strlen($title) < 50) {
                        Log::info('從重複模式提取標題', ['title' => $title]);
                        return $title;
                    }
                }

                // 如果沒有重複模式，嘗試從開頭提取合理長度的標題
                if (mb_strlen($firstLine) > 5 && mb_strlen($firstLine) < 100) {
                    // 在常見分隔符處截斷
                    $separators = ['原創作品', '新作', '高期待', '動作', '播放日', '其他名稱'];
                    foreach ($separators as $sep) {
                        $pos = mb_strpos($firstLine, $sep);
                        if ($pos !== false && $pos > 5) {
                            $title = trim(mb_substr($firstLine, 0, $pos));
                            if (mb_strlen($title) > 2) {
                                Log::info('從分隔符提取標題', ['title' => $title, 'separator' => $sep]);
                                return $title;
                            }
                        }
                    }

                    // 如果找不到分隔符，取前面合理長度的部分
                    if (mb_strlen($firstLine) > 20) {
                        $title = mb_substr($firstLine, 0, 30);
                        $title = preg_replace('/[^\p{L}\p{N}\s\-\(\)]/u', '', $title);
                        $title = trim($title);
                        if (mb_strlen($title) > 2) {
                            Log::info('從開頭截取標題', ['title' => $title]);
                            return $title;
                        }
                    }
                }
            }

            // 方法2: 從圖片alt屬性提取
            $img = $container->filter('img')->first();
            if ($img->count() > 0) {
                $alt = trim($img->attr('alt') ?: '');
                $title = trim($img->attr('title') ?: '');

                foreach ([$alt, $title] as $text) {
                    if (!empty($text) && mb_strlen($text) > 2 && mb_strlen($text) < 100) {
                        Log::info('從圖片屬性提取標題', ['title' => $text]);
                        return $text;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('標題提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 從文字中提取日期
     */
    private function extractDateFromText(Crawler $container)
    {
        try {
            $text = $container->text();

            // 日期正則模式
            $datePatterns = [
                '/(\d{4})年(\d{1,2})月(\d{1,2})日/',  // 2024年4月1日
                '/(\d{4})年(\d{1,2})月/',            // 2024年4月
                '/(\d{4})-(\d{1,2})-(\d{1,2})/',     // 2024-04-01
                '/(\d{4})\/(\d{1,2})\/(\d{1,2})/',   // 2024/04/01
                '/(\d{4})年/',                       // 2024年
            ];

            foreach ($datePatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    return $this->formatDateFromMatches($matches);
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('從文字提取日期失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 從周圍文字提取標題
     */
    private function extractTitleFromSurroundingText(Crawler $container)
    {
        try {
            $text = $container->text();
            $lines = explode("\n", $text);

            foreach ($lines as $line) {
                $line = trim($line);

                // 跳過太短或太長的行
                if (mb_strlen($line) < 3 || mb_strlen($line) > 80) {
                    continue;
                }

                // 跳過包含特定關鍵字的行（可能是平台或其他資訊）
                $skipPatterns = [
                    '/^(Netflix|Disney|Bilibili|巴哈姆特|myTV)/i',
                    '/^(香港|台灣|中國大陸)/i',
                    '/^(Action|Comedy|Drama|Romance)/i',
                    '/^\d+$/',
                    '/^[A-Z]{2,}$/',
                ];

                $shouldSkip = false;
                foreach ($skipPatterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $shouldSkip = true;
                        break;
                    }
                }

                if (!$shouldSkip) {
                    return $line;
                }
            }
        } catch (\Exception $e) {
            Log::error('從周圍文字提取標題失敗', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * 從文字中智能提取描述
     */
    private function extractDescriptionFromText(Crawler $container)
    {
        try {
            $text = $container->text();
            $lines = explode("\n", $text);

            foreach ($lines as $line) {
                $line = trim($line);

                // 找到可能是描述的行（中等長度，包含常見描述詞語）
                if (strlen($line) > 30 && strlen($line) < 500) {
                    // 檢查是否包含描述性詞語
                    $descriptionKeywords = [
                        '故事', '劇情', '講述', '描述', '關於', '以', '在',
                        '主角', '女主', '男主', '世界', '學校', '生活',
                        '冒險', '戰鬥', '魔法', '異世界'
                    ];

                    foreach ($descriptionKeywords as $keyword) {
                        if (mb_strpos($line, $keyword) !== false) {
                            return $line;
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('從文字提取描述失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 驗證平台和地區的合理性
     */
    private function isPlatformRegionValid($platform, $region)
    {
        $validCombinations = [
            'Netflix' => ['香港', '台灣'],
            'Disney+' => ['香港', '台灣'],
            'Bilibili' => ['中國大陸', '香港'], // Bilibili 也在香港有服務
            '巴哈姆特動畫瘋' => ['台灣'],
            'myTV SUPER' => ['香港'],
            '騰訊視頻' => ['中國大陸'],
            '愛奇藝' => ['中國大陸']
        ];

        return isset($validCombinations[$platform]) &&
            in_array($region, $validCombinations[$platform]);
    }

    private function extractPlatforms(Crawler $container)
    {
        try {
            $text = $container->text();
            $platforms = [];

            // 擴展的平台映射
            $platformMapping = [
                'Amazon Prime Video' => ['Amazon Prime Video', '香港'],
                'Netflix' => ['Netflix', '香港'],
                'Disney+' => ['Disney+', '香港'],
                'Disney＋' => ['Disney+', '香港'],
                'Bilibili' => ['Bilibili', '中國大陸'],
                '巴哈姆特動畫瘋' => ['巴哈姆特動畫瘋', '台灣'],
                'myTV SUPER' => ['myTV SUPER', '香港'],
                'Crunchyroll' => ['Crunchyroll', '香港'],
                'Funimation' => ['Funimation', '香港'],
                'Hulu' => ['Hulu', '香港'],
            ];

            // 檢查每個平台
            foreach ($platformMapping as $platformText => $platformInfo) {
                if (mb_strpos($text, $platformText) !== false) {
                    // 檢查是否有地區特定的提及
                    $region = $this->detectRegionContext($text, $platformText, $platformInfo[1]);

                    $platforms[] = [
                        'region' => $region,
                        'platform' => $platformInfo[0],
                        'availability_status' => 'available'
                    ];

                    Log::info('找到平台', [
                        'platform' => $platformInfo[0],
                        'region' => $region,
                        'original_text' => $platformText
                    ]);
                }
            }

            return $platforms;

        } catch (\Exception $e) {
            Log::error('平台提取失敗', ['message' => $e->getMessage()]);
            return [];
        }
    }

    private function detectRegionContext($text, $platformText, $defaultRegion)
    {
        // 在平台名稱前後查找地區關鍵字
        $regionKeywords = [
            '香港' => '香港',
            '台灣' => '台灣',
            '中國大陸' => '中國大陸',
            'HK' => '香港',
            'TW' => '台灣',
            'CN' => '中國大陸'
        ];

        $platformPos = mb_strpos($text, $platformText);
        if ($platformPos !== false) {
            // 檢查平台名稱前後50個字符
            $contextStart = max(0, $platformPos - 50);
            $contextEnd = min(mb_strlen($text), $platformPos + mb_strlen($platformText) + 50);
            $context = mb_substr($text, $contextStart, $contextEnd - $contextStart);

            foreach ($regionKeywords as $keyword => $region) {
                if (mb_strpos($context, $keyword) !== false) {
                    return $region;
                }
            }
        }

        return $defaultRegion;
    }

    /**
     * 清理標題文字
     */
    private function cleanTitle($title)
    {
        // 移除常見的無用詞語和符號
        $cleanPatterns = [
            '/\s*\[.*?\]\s*/',  // 移除方括號內容
            '/\s*\(.*?\)\s*/',  // 移除圓括號內容（但保留重要的）
            '/^\s*(海報|圖片|封面|poster|image)\s*:?\s*/i',  // 移除圖片相關前綴
            '/\s*(netflix|disney|bilibili|巴哈姆特|mytv)\s*/i',  // 移除平台名稱
            '/\s+/',  // 合併多個空格
        ];

        $cleaned = $title;
        foreach ($cleanPatterns as $pattern) {
            $cleaned = preg_replace($pattern, ' ', $cleaned);
        }

        $cleaned = trim($cleaned);

        // 如果清理後太短，返回原始標題
        if (mb_strlen($cleaned) < 3) {
            return trim($title);
        }

        return $cleaned;
    }

    /**
     * 解析日期字串
     */
    private function parseDateString($dateString)
    {
        try {
            // 清理日期字串
            $cleaned = trim($dateString);

            // 嘗試各種日期格式
            $formats = [
                'Y年n月j日',
                'Y年n月',
                'Y-m-d',
                'Y/m/d',
                'Y',
                'm/d/Y',
                'd/m/Y'
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $cleaned);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 從正則匹配結果格式化日期
     */
    private function formatDateFromMatches($matches)
    {
        try {
            if (count($matches) >= 4) {
                // 完整日期：年月日
                $year = $matches[1];
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                return "{$year}-{$month}-{$day}";
            } elseif (count($matches) >= 3) {
                // 年月
                $year = $matches[1];
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                return "{$year}-{$month}-01";
            } elseif (count($matches) >= 2) {
                // 只有年份
                $year = $matches[1];
                return "{$year}-01-01";
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 清理描述文字
     */
    private function cleanDescription($description)
    {
        // 移除多餘的重複字符和無用內容
        $description = trim($description);

        // 移除開頭的動漫標題重複
        $description = preg_replace('/^[^\p{L}]*/', '', $description);

        // 移除末尾的無關內容
        $description = preg_replace('/[A-Z]{3,}.*$/', '', $description);

        // 移除特殊符號和多餘空格
        $description = preg_replace('/\s+/', ' ', $description);

        // 確保以句號結尾（如果沒有標點符號）
        if (!preg_match('/[。！？.!?…]$/', $description)) {
            // 如果有明顯的截斷，加上省略號
            if (mb_strlen($description) > 100) {
                $description .= '...';
            }
        }

        return trim($description);
    }

    /**
     * 清理 UTF-8 編碼，移除不正確的字元
     */
    private function cleanUtf8($string)
    {
        if (empty($string)) {
            return $string;
        }

        // 方法1: 使用 mb_convert_encoding 清理
        $cleaned = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // 方法2: 移除非 UTF-8 字元
        $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $cleaned);

        // 方法3: 使用 preg_replace 移除無效字元
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $cleaned);

        return $cleaned;
    }

    /**
     * 提取每周更新時間
     */
    private function extractWeeklySchedule(Crawler $container)
    {
        try {
            $text = $container->text();

            // 匹配模式：每週X 時間, 星期X 時間, 週X 時間等
            $patterns = [
                '/每[週周]([一二三四五六日天])[\s]*([\d]{1,2}[:：][\d]{2})/',
                '/星期([一二三四五六日天])[\s]*([\d]{1,2}[:：][\d]{2})/',
                '/週([一二三四五六日])[\s]*([\d]{1,2}[:：][\d]{2})/',
                '/([一二三四五六日天])[\s]*([\d]{1,2}[:：][\d]{2})更新/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $day = $matches[1];
                    $time = str_replace('：', ':', $matches[2]);
                    return "每週{$day} {$time}";
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('每周更新時間提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 提取配音員
     */
    private function extractVoiceActors(Crawler $container)
    {
        try {
            $text = $container->text();
            $voiceActors = [];

            // 方法1: 尋找配音員或Cast標題，並在遇到製作人員關鍵字時停止
            $patterns = [
                '/配音員[：:\s]*\n?(.*?)(?=(?:製作人員|Staff|原作[：:]|導演[：:]|劇本統籌|主題曲|OP[：:]|ED[：:]))/us',
                '/Cast[：:\s]*\n?(.*?)(?=(?:Staff|製作人員|原作[：:]|導演[：:]))/us',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $actorsText = trim($matches[1]);

                    // 分行處理，每行格式：角色名：配音員名
                    $lines = preg_split('/\n+/', $actorsText);

                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        // 檢查是否包含製作關鍵字（表示已經進入製作人員區域）
                        $staffKeywords = ['原作', '導演', '劇本統籌', '劇本', '編劇', '監督', '設計', '音樂', '製作', '攝影', '剪接', '音效'];
                        $isStaffLine = false;
                        foreach ($staffKeywords as $keyword) {
                            if (mb_strpos($line, $keyword . '：') !== false || mb_strpos($line, $keyword . ':') !== false) {
                                $isStaffLine = true;
                                break;
                            }
                        }

                        if ($isStaffLine) {
                            break; // 遇到製作人員，停止處理
                        }

                        // 匹配格式：角色名：配音員名
                        if (preg_match('/^([^：:]+)[：:](.+)$/', $line, $match)) {
                            $character = trim($match[1]);
                            $actor = trim($match[2]);

                            // 確保不包含製作相關詞彙
                            $containsStaffKeyword = false;
                            foreach ($staffKeywords as $keyword) {
                                if (mb_strpos($character, $keyword) !== false || mb_strpos($actor, $keyword) !== false) {
                                    $containsStaffKeyword = true;
                                    break;
                                }
                            }

                            if (!$containsStaffKeyword && mb_strlen($character) > 0 && mb_strlen($actor) > 0) {
                                // 清理 UTF-8 編碼
                                $character = $this->cleanUtf8($character);
                                $actor = $this->cleanUtf8($actor);

                                $voiceActors[] = [
                                    'character' => $character,
                                    'actor' => $actor
                                ];
                            }
                        }
                    }

                    if (!empty($voiceActors)) {
                        Log::info('成功提取配音員', ['count' => count($voiceActors), 'actors' => $voiceActors]);
                        break;
                    }
                }
            }

            return !empty($voiceActors) ? $voiceActors : null;
        } catch (\Exception $e) {
            Log::error('配音員提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 提取版權所屬
     */
    private function extractCopyright(Crawler $container)
    {
        try {
            $text = $container->text();

            // 匹配模式
            $patterns = [
                '/©\s*(.+?)(?:\n|$)/',
                '/版權[所]?屬?[：:](.+?)(?:\n|$)/u',
                '/Copyright[：:]?\s*(.+?)(?:\n|$)/i',
                '/製作[：:](.+?)(?:\n|$)/u',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $copyright = trim($matches[1]);
                    if (mb_strlen($copyright) > 2 && mb_strlen($copyright) < 200) {
                        return $copyright;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('版權提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 提取預告片連結
     */
    private function extractTrailerUrl(Crawler $container)
    {
        try {
            $text = $container->text();

            // 方法1: 從HTML中尋找包含youtube, bilibili等影片平台的連結
            $videoPatterns = [
                'youtube.com/watch',
                'youtu.be/',
                'bilibili.com/video',
                'nicovideo.jp/watch'
            ];

            $html = $container->html();
            foreach ($videoPatterns as $pattern) {
                if (preg_match('/href=["\']([^"\']*' . preg_quote($pattern, '/') . '[^"\']*)["\']/', $html, $matches)) {
                    return $matches[1];
                }
            }

            // 方法2: 從文字中提取URL
            if (preg_match('/(https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be|bilibili\.com|nicovideo\.jp)[^\s]+)/', $text, $matches)) {
                return trim($matches[1]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('預告片連結提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 提取影片連結
     */
    private function extractVideoLinks(Crawler $container)
    {
        try {
            $html = $container->html();
            $videoLinks = [];

            // 尋找所有影片相關連結
            preg_match_all('/href=["\']([^"\']*(?:youtube\.com|youtu\.be|bilibili\.com|nicovideo\.jp)[^"\']*)["\']/', $html, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    if (!in_array($url, $videoLinks)) {
                        $videoLinks[] = $url;
                    }
                }
            }

            return !empty($videoLinks) ? $videoLinks : null;
        } catch (\Exception $e) {
            Log::error('影片連結提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 提取製作人員
     */
    private function extractStaff(Crawler $container)
    {
        try {
            $text = $container->text();
            $staff = [];

            // 尋找製作人員區域 - 從"原作"或"Staff"開始，到"主題曲"或其他分隔標記結束
            $patterns = [
                '/(?:製作人員|Staff)[：:\s]*\n?(.*?)(?=(?:主題曲|OP[：:]|ED[：:]|外部連結|播放平台|©))/us',
                '/(?:原作[：:])(.*?)(?=(?:主題曲|OP[：:]|ED[：:]|外部連結|播放平台|©))/us',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $staffText = trim($matches[1]);

                    // 如果使用第二個模式，需要包含"原作"這一行
                    if (strpos($pattern, '原作[：:]') !== false) {
                        // 找到原作的位置
                        $pos = mb_strpos($text, '原作');
                        if ($pos !== false) {
                            $endPatterns = ['主題曲', 'OP：', 'ED：', '外部連結', '播放平台', '©'];
                            $endPos = mb_strlen($text);
                            foreach ($endPatterns as $endPattern) {
                                $tempPos = mb_strpos($text, $endPattern, $pos);
                                if ($tempPos !== false && $tempPos < $endPos) {
                                    $endPos = $tempPos;
                                }
                            }
                            $staffText = mb_substr($text, $pos, $endPos - $pos);
                        }
                    }

                    // 分行處理
                    $lines = preg_split('/\n+/', $staffText);

                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        // 匹配格式：職位：人名
                        if (preg_match('/^([^：:]+)[：:](.+)$/', $line, $match)) {
                            $position = trim($match[1]);
                            $name = trim($match[2]);

                            // 檢查是否包含製作相關關鍵字
                            $staffKeywords = ['原作', '導演', '劇本統籌', '劇本', '編劇', '監督', '設計', '音樂', '製作', '攝影', '剪接', '音效', '統籌', '作畫', '3D'];
                            $isStaff = false;
                            foreach ($staffKeywords as $keyword) {
                                if (mb_strpos($position, $keyword) !== false) {
                                    $isStaff = true;
                                    break;
                                }
                            }

                            // 排除配音員（通常包含角色標記）
                            $characterKeywords = ['／', '/', '号'];
                            $hasCharacter = false;
                            foreach ($characterKeywords as $keyword) {
                                if (mb_strpos($position, $keyword) !== false) {
                                    $hasCharacter = true;
                                    break;
                                }
                            }

                            if ($isStaff && !$hasCharacter && mb_strlen($position) > 1 && mb_strlen($name) > 1) {
                                // 清理 UTF-8 編碼
                                $position = $this->cleanUtf8($position);
                                $name = $this->cleanUtf8($name);

                                $staff[] = [
                                    'position' => $position,
                                    'name' => $name
                                ];
                            }
                        }
                    }

                    if (!empty($staff)) {
                        Log::info('成功提取製作人員', ['count' => count($staff), 'staff' => $staff]);
                        break;
                    }
                }
            }

            return !empty($staff) ? $staff : null;
        } catch (\Exception $e) {
            Log::error('製作人員提取失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 從容器中提取詳細頁面URL
     */
    private function extractDetailUrl(Crawler $container)
    {
        try {
            // 方法1: 尋找所有連結
            $links = $container->filter('a[href]');

            if ($links->count() > 0) {
                foreach ($links as $linkNode) {
                    $link = new Crawler($linkNode);
                    $href = $link->attr('href');

                    // 檢查是否是動漫詳細頁面的連結
                    // 詳細頁面通常包含 /bangumi/ 或 /anime/ 路徑
                    if (strpos($href, '/bangumi/') !== false || strpos($href, '/anime/') !== false) {
                        // 排除列表頁面
                        if (preg_match('/\/bangumi\/\d{6}\/?$/', $href)) {
                            continue; // 這是列表頁面，跳過
                        }

                        // 如果是相對路徑，轉換為絕對路徑
                        if (strpos($href, 'http') !== 0) {
                            if (strpos($href, '/') === 0) {
                                $href = $this->baseUrl . $href;
                            } else {
                                $href = $this->baseUrl . '/' . $href;
                            }
                        }

                        Log::info('找到詳細頁面URL', ['url' => $href]);
                        return $href;
                    }
                }
            }

            // 方法2: 尋找圖片的父連結
            $imageLinks = $container->filter('img[src*="static.acgsecrets.hk"]');
            if ($imageLinks->count() > 0) {
                $imgParent = $imageLinks->first()->parents()->filter('a[href]');
                if ($imgParent->count() > 0) {
                    $href = $imgParent->first()->attr('href');

                    if (strpos($href, 'http') !== 0) {
                        if (strpos($href, '/') === 0) {
                            $href = $this->baseUrl . $href;
                        } else {
                            $href = $this->baseUrl . '/' . $href;
                        }
                    }

                    Log::info('從圖片父連結找到詳細頁面URL', ['url' => $href]);
                    return $href;
                }
            }

            Log::warning('未找到詳細頁面URL');
            return null;
        } catch (\Exception $e) {
            Log::error('提取詳細URL失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 抓取詳細頁面資料
     */
    private function fetchDetailPageData($url)
    {
        try {
            // 只爬取站內詳細頁面，跳過外部連結(如 MyAnimeList)
            if (strpos($url, 'acgsecrets.hk') === false) {
                Log::info('跳過外部連結', ['url' => $url]);
                return null;
            }

            Log::info('開始抓取詳細頁面', ['url' => $url]);

            $html = $this->fetchHtml($url);

            if (!$html) {
                Log::warning('無法獲取詳細頁面內容');
                return null;
            }

            $crawler = new Crawler($html);

            // 從詳細頁面提取資訊
            $data = [];

            // 提取每周更新時間（從詳細頁面更準確）
            $data['weekly_schedule'] = $this->extractWeeklyScheduleFromDetail($crawler);

            // 暫時禁用外部連結爬取 - 需要重新設計分類邏輯
            // $data['external_links'] = $this->extractExternalLinks($crawler);
            $data['external_links'] = null;

            // 提取播放平台（詳細頁面可能有更完整的資訊）
            $data['platforms'] = $this->extractPlatformsFromDetail($crawler);

            // 提取其他資訊
            $data['voice_actors'] = $this->extractVoiceActors($crawler);
            $data['copyright'] = $this->extractCopyright($crawler);
            $data['trailer_url'] = $this->extractTrailerUrl($crawler);
            $data['video_links'] = $this->extractVideoLinks($crawler);
            $data['staff'] = $this->extractStaff($crawler);

            Log::info('成功抓取詳細頁面資料', [
                'has_weekly_schedule' => !empty($data['weekly_schedule']),
                'has_external_links' => !empty($data['external_links']),
                'platforms_count' => count($data['platforms'] ?? [])
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('抓取詳細頁面失敗', ['message' => $e->getMessage(), 'url' => $url]);
            return null;
        }
    }

    /**
     * 從詳細頁面提取每周更新時間
     */
    private function extractWeeklyScheduleFromDetail(Crawler $crawler)
    {
        try {
            // 方法1: 從 time_today 或 main_time class 提取
            $timeToday = $crawler->filter('.time_today, .main_time');

            if ($timeToday->count() > 0) {
                $dayText = null;
                $timeText = null;

                // 提取星期
                $dayNode = $timeToday->filter('.day, [class*="day"]');
                if ($dayNode->count() > 0) {
                    $dayText = trim($dayNode->text());
                }

                // 提取時間
                $timeNode = $timeToday->filter('.time, [class*="time"]');
                if ($timeNode->count() > 0) {
                    $timeText = trim($timeNode->text());
                }

                // 如果沒有找到特定的 day/time class，嘗試直接從 time_today 獲取
                if (!$dayText || !$timeText) {
                    $fullText = trim($timeToday->text());
                    // 匹配格式: "星期X HH:MM" 或 "週X HH:MM"
                    if (preg_match('/(星期|週)([一二三四五六日天])\s*([\d]{1,2}[:：][\d]{2})/', $fullText, $matches)) {
                        $dayText = $matches[2];
                        $timeText = str_replace('：', ':', $matches[3]);
                    }
                }

                if ($dayText && $timeText) {
                    $schedule = "每週{$dayText} {$timeText}";
                    Log::info('從 time_today 提取每周更新時間', ['schedule' => $schedule]);
                    return $schedule;
                }
            }

            // 方法2: 從 oa-time 提取（在 anime_streams 中）
            $oaTime = $crawler->filter('.oa-time');
            if ($oaTime->count() > 0) {
                $timeText = trim($oaTime->first()->text());
                if ($timeText && $timeText !== '-') {
                    // 嘗試從周圍文字找到星期
                    $text = $crawler->text();
                    if (preg_match('/(星期|週|每週)([一二三四五六日天])[^\d]*' . preg_quote($timeText, '/') . '/', $text, $matches)) {
                        $dayText = $matches[2];
                        $schedule = "每週{$dayText} {$timeText}";
                        Log::info('從 oa-time 提取每周更新時間', ['schedule' => $schedule]);
                        return $schedule;
                    }
                }
            }

            // 方法3: 從純文字中提取（備用方法）
            $text = $crawler->text();
            $patterns = [
                '/每[週周]([一二三四五六日天])\s*([\d]{1,2}[:：][\d]{2})/',
                '/星期([一二三四五六日天])\s*([\d]{1,2}[:：][\d]{2})/',
                '/週([一二三四五六日])\s*([\d]{1,2}[:：][\d]{2})/',
                '/([一二三四五六日天])\s*([\d]{1,2}[:：][\d]{2})\s*更新/',
                '/更新時間[：:]\s*每[週周]([一二三四五六日天])\s*([\d]{1,2}[:：][\d]{2})/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $day = $matches[1];
                    $time = str_replace('：', ':', $matches[2]);
                    $schedule = "每週{$day} {$time}";
                    Log::info('從文字提取每周更新時間', ['schedule' => $schedule]);
                    return $schedule;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('從詳細頁面提取每周更新時間失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 提取外部連結（從 anime_link_group 結構中提取）
     */
    private function extractExternalLinks(Crawler $crawler)
    {
        try {
            $externalLinks = [];

            // 方法1: 從 anime_link_group 和 anime_links 結構中提取
            $linkGroups = $crawler->filter('.anime_link_group');

            if ($linkGroups->count() > 0) {
                $linkGroups->each(function (Crawler $linkGroup) use (&$externalLinks) {
                    try {
                        $links = $linkGroup->filter('.anime_links a');

                        if ($links->count() > 0) {
                            $links->each(function (Crawler $link) use (&$externalLinks) {
                                try {
                                    $url = $link->attr('href');
                                    $text = trim($link->text());

                                    // 移除 icon 標籤的文字
                                    $iconText = $link->filter('i')->count() > 0
                                        ? trim($link->filter('i')->text())
                                        : '';

                                    // 獲取連結的實際名稱（移除 icon 文字）
                                    $linkName = str_replace($iconText, '', $text);
                                    $linkName = trim($linkName);

                                    // 判斷連結類型
                                    $type = $this->identifyLinkType($url, $linkName);

                                    if ($url && $linkName) {
                                        // 使用 URL 作為 key 避免重複
                                        if (!isset($externalLinks[$url])) {
                                            $externalLinks[$url] = [
                                                'name' => $linkName,
                                                'url' => $url
                                            ];

                                            Log::info('從 anime_links 提取外部連結', [
                                                'name' => $linkName,
                                                'url' => $url,
                                                'type' => $type
                                            ]);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Log::error('提取單個外部連結失敗', ['message' => $e->getMessage()]);
                                }
                            });
                        }
                    } catch (\Exception $e) {
                        Log::error('處理 link_group 失敗', ['message' => $e->getMessage()]);
                    }
                });
            }

            // 方法2: 從所有 a 標籤中提取（備用方法）
            if (empty($externalLinks)) {
                Log::info('anime_link_group 未找到，使用備用方法');

                $linkPatterns = [
                    'official' => ['官方網站', 'official', 'hp'],
                    'wikipedia' => ['維基百科', 'Wikipedia', 'wikipedia.org'],
                    'twitter' => ['Twitter', 'X(Twitter)', 'twitter.com', 'x.com'],
                    'mal' => ['MyAnimeList', 'MAL', 'myanimelist.net'],
                    'anilist' => ['AniList', 'anilist.co'],
                    'anidb' => ['AniDB', 'anidb.net'],
                    'bangumi' => ['Bangumi', 'bangumi.tv', 'bgm.tv'],
                ];

                $crawler->filter('a[href]')->each(function (Crawler $link) use (&$externalLinks, $linkPatterns) {
                    $href = $link->attr('href');
                    $text = trim($link->text());

                    // 跳過站內連結和播放平台連結
                    if (strpos($href, 'acgsecrets.hk') !== false ||
                        strpos($href, 'viu.com') !== false ||
                        strpos($href, 'iq.com') !== false ||
                        strpos($href, 'gamer.com.tw') !== false) {
                        return;
                    }

                    foreach ($linkPatterns as $type => $keywords) {
                        foreach ($keywords as $keyword) {
                            if (stripos($href, $keyword) !== false || stripos($text, $keyword) !== false) {
                                if (!isset($externalLinks[$href])) {
                                    $externalLinks[$href] = [
                                        'name' => $text ?: $this->getExternalLinkName($type),
                                        'url' => $href
                                    ];
                                    Log::info('找到外部連結', ['type' => $type, 'name' => $text, 'url' => $href]);
                                }
                                break 2;
                            }
                        }
                    }
                });
            }

            return !empty($externalLinks) ? array_values($externalLinks) : null;
        } catch (\Exception $e) {
            Log::error('提取外部連結失敗', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 識別連結類型
     */
    private function identifyLinkType($url, $name)
    {
        $patterns = [
            'official' => ['官方', 'official', 'hp'],
            'wikipedia' => ['wikipedia', '維基'],
            'twitter' => ['twitter', 'x.com'],
            'mal' => ['myanimelist'],
            'anilist' => ['anilist'],
            'anidb' => ['anidb'],
            'bangumi' => ['bangumi', 'bgm.tv'],
        ];

        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($url, $keyword) !== false || stripos($name, $keyword) !== false) {
                    return $type;
                }
            }
        }

        return 'other';
    }

    /**
     * 獲取外部連結的顯示名稱
     */
    private function getExternalLinkName($type)
    {
        $names = [
            'official' => '官方網站',
            'wikipedia_zh' => '維基百科(中文)',
            'wikipedia_ja' => '維基百科(日文)',
            'wikipedia_en' => '維基百科(英文)',
            'twitter' => 'X (Twitter)',
            'mal' => 'MyAnimeList',
            'anilist' => 'AniList',
            'filmarks' => 'Filmarks',
            'ptt' => 'PTT (C_Chat)',
            'anidb' => 'AniDB',
            'bangumi' => 'Bangumi',
        ];

        return $names[$type] ?? ucfirst($type);
    }

    /**
     * 從詳細頁面提取播放平台（從 anime_streams 結構中提取）
     */
    private function extractPlatformsFromDetail(Crawler $crawler)
    {
        try {
            $platforms = [];

            // 方法1: 從 anime_streams 結構中提取
            $streamAreas = $crawler->filter('.anime_streams');

            if ($streamAreas->count() > 0) {
                $streamAreas->each(function (Crawler $streamArea) use (&$platforms) {
                    try {
                        // 提取地區
                        $region = $streamArea->filter('.stream-area')->count() > 0
                            ? trim($streamArea->filter('.stream-area')->text())
                            : null;

                        if (!$region) {
                            return;
                        }

                        // 提取播放時間
                        $streamTime = null;
                        $timeNodes = $streamArea->filter('.stream-time .oa-time');
                        if ($timeNodes->count() > 0) {
                            $streamTime = trim($timeNodes->first()->text());
                            if ($streamTime === '-') {
                                $streamTime = null;
                            }
                        }

                        // 提取平台連結
                        $streamSites = $streamArea->filter('.stream-sites a.stream-site');
                        if ($streamSites->count() > 0) {
                            $streamSites->each(function (Crawler $site) use (&$platforms, $region, $streamTime) {
                                try {
                                    $platformName = $site->filter('.steam-site-name')->count() > 0
                                        ? trim($site->filter('.steam-site-name')->text())
                                        : null;

                                    $platformUrl = $site->attr('href') ?: null;

                                    if ($platformName) {
                                        $platforms[] = [
                                            'region' => $region,
                                            'platform' => $platformName,
                                            'availability_status' => 'available',
                                            'notes' => $platformUrl // 暫時把 URL 存在 notes 欄位
                                        ];

                                        Log::info('從 anime_streams 提取平台', [
                                            'region' => $region,
                                            'platform' => $platformName,
                                            'url' => $platformUrl,
                                            'time' => $streamTime
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('提取單個平台失敗', ['message' => $e->getMessage()]);
                                }
                            });
                        }
                    } catch (\Exception $e) {
                        Log::error('處理 stream-area 失敗', ['message' => $e->getMessage()]);
                    }
                });
            }

            // 方法2: 備用方法 - 從文字中提取（如果方法1失敗）
            if (empty($platforms)) {
                Log::info('anime_streams 未找到，使用備用方法');
                $text = $crawler->text();

                $platformMapping = [
                    '巴哈姆特動畫瘋' => '巴哈姆特動畫瘋',
                    'viu.com' => 'viu.com',
                    '愛奇藝' => '愛奇藝',
                    '木棉花YouTube' => '木棉花YouTube',
                    'Netflix' => 'Netflix',
                    'Disney+' => 'Disney+',
                    'Bilibili' => 'Bilibili',
                    'Ani-One' => 'Ani-One YouTube',
                ];

                $regions = ['香港', '台灣', '中國大陸', '日本'];

                foreach ($regions as $region) {
                    if (mb_strpos($text, $region) !== false) {
                        foreach ($platformMapping as $keyword => $platformName) {
                            if (mb_strpos($text, $keyword) !== false) {
                                $platforms[] = [
                                    'region' => $region,
                                    'platform' => $platformName,
                                    'availability_status' => 'available',
                                    'notes' => null
                                ];
                            }
                        }
                    }
                }
            }

            return $platforms;
        } catch (\Exception $e) {
            Log::error('從詳細頁面提取平台失敗', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
