<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class FilmarksScraper
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ja,en-US;q=0.8,en;q=0.5',
            ]
        ]);
    }

    /**
     * 從 Filmarks 抓取配音員和製作人員
     *
     * @param string $filmarksUrl Filmarks 動畫頁面 URL
     * @return array ['voice_actors' => array, 'staff' => array]
     */
    public function fetchStaffAndActors(string $filmarksUrl): array
    {
        try {
            Log::info("Fetching Filmarks data from: {$filmarksUrl}");

            $response = $this->client->get($filmarksUrl);
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);

            $voiceActors = $this->extractVoiceActors($crawler);
            $staff = $this->extractStaff($crawler);
            Log::info("Filmarks data fetched", [
                'url' => $filmarksUrl,
                'voice_actors_count' => count($voiceActors),
                'staff_count' => count($staff),
            ]);

            return [
                'voice_actors' => $voiceActors,
                'staff'        => $staff,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to fetch Filmarks data", [
                'url' => $filmarksUrl,
                'error' => $e->getMessage()
            ]);

            return [
                'voice_actors' => [],
                'staff' => []
            ];
        }
    }

    /**
     * 提取配音員資料
     *
     * @param Crawler $crawler
     * @return array
     */
    private function extractVoiceActors(Crawler $crawler): array
    {
        $voiceActors = [];

        try {
            $castSection = $crawler->filter('.p-people-list__casts');

            if ($castSection->count() > 0) {
                $castItems = $castSection->filter('h4.p-people-list__item');

                $castItems->each(function (Crawler $item) use (&$voiceActors) {
                    $actorNode = $item->filter('.c2-button-tertiary-s-multi-text__text');
                    $characterNode = $item->filter('.c2-button-tertiary-s-multi-text__subtext');

                    if ($actorNode->count() > 0 && $characterNode->count() > 0) {
                        $actor = trim($actorNode->text());
                        $character = trim($characterNode->text());

                        $voiceActors[] = [
                            'character' => $character,
                            'actor' => $actor
                        ];
                    }
                });
            }
        } catch (\Exception $e) {
            Log::warning("Failed to extract voice actors", ['error' => $e->getMessage()]);
        }

        return $voiceActors;
    }

    /**
     * 提取製作人員資料
     *
     * @param Crawler $crawler
     * @return array
     */
    private function extractStaff(Crawler $crawler): array
    {
        $staff = [];

        try {
            $staffSections = $crawler->filter('.p-content-detail__people-list-others-inner');

            if ($staffSections->count() > 0) {
                $staffSections->each(function (Crawler $section) use (&$staff) {
                    $positionNode = $section->filter('h3.p-content-detail__people-list-term');

                    if ($positionNode->count() > 0) {
                        $position = trim($positionNode->text());
                        $peopleItems = $section->filter('li.p-people-list__item');

                        $peopleItems->each(function (Crawler $item) use ($position, &$staff) {
                            $nameNode = $item->filter('.c2-button-tertiary-s__text');

                            if ($nameNode->count() > 0) {
                                $name = trim($nameNode->text());

                                $staff[] = [
                                    'position' => $position,
                                    'name' => $name
                                ];
                            }
                        });
                    }
                });
            }
        } catch (\Exception $e) {
            Log::warning("Failed to extract staff", ['error' => $e->getMessage()]);
        }

        return $staff;
    }

    /**
     * 檢查是否為有效的 Filmarks URL
     *
     * @param string|null $url
     * @return bool
     */
    public static function isValidFilmarksUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        return str_starts_with($url, 'https://filmarks.com/animes/');
    }
}
