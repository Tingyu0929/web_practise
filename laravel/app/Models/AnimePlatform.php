<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimePlatform extends Model
{
    use HasFactory;

    protected $fillable = [
        'anime_id',
        'region',
        'platform',
        'availability_status',
        'notes'
    ];

    /**
     * 關聯動漫
     */
    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * 地區列表
     */
    public static function getRegions()
    {
        return [
            '香港' => '香港',
            '台灣' => '台灣', 
            '中國大陸' => '中國大陸',
        ];
    }

    /**
     * 平台列表
     */
    public static function getPlatforms()
    {
        return [
            'Netflix' => 'Netflix',
            'Disney+' => 'Disney+',
            'Bilibili' => 'Bilibili',
            '巴哈姆特動畫瘋' => '巴哈姆特動畫瘋',
            'myTV SUPER' => 'myTV SUPER',
            'Crunchyroll' => 'Crunchyroll',
            'Funimation' => 'Funimation',
            'Hulu' => 'Hulu',
            'Amazon Prime Video' => 'Amazon Prime Video',
        ];
    }

    /**
     * 取得該平台在特定地區的所有動漫
     */
    public static function getAnimesByPlatformAndRegion($platform, $region)
    {
        return self::where('platform', $platform)
                  ->where('region', $region)
                  ->with('anime')
                  ->get();
    }

    /**
     * 取得某個地區的所有平台統計
     */
    public static function getPlatformStatsForRegion($region)
    {
        return self::where('region', $region)
                  ->selectRaw('platform, COUNT(*) as anime_count')
                  ->groupBy('platform')
                  ->orderBy('anime_count', 'desc')
                  ->get();
    }
}
