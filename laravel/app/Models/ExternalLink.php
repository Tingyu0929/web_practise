<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'anime_id',
        'type',
        'name',
        'url',
        'language',
        'order'
    ];

    /**
     * 關聯到動漫
     */
    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * 連結類型的顯示名稱
     */
    public static function getTypeName($type)
    {
        $types = [
            'official' => '官方網站',
            'wikipedia' => '維基百科',
            'twitter' => 'X (Twitter)',
            'mal' => 'MyAnimeList',
            'anilist' => 'AniList',
            'anidb' => 'AniDB',
            'bangumi' => 'Bangumi',
            'youtube' => 'YouTube',
            'bilibili' => 'Bilibili',
            'other' => '其他'
        ];

        return $types[$type] ?? $type;
    }

    /**
     * 從URL自動識別類型
     */
    public static function identifyTypeFromUrl($url)
    {
        $patterns = [
            'wikipedia.org' => 'wikipedia',
            'twitter.com' => 'twitter',
            'x.com' => 'twitter',
            'myanimelist.net' => 'mal',
            'anilist.co' => 'anilist',
            'anidb.net' => 'anidb',
            'bangumi.tv' => 'bangumi',
            'bgm.tv' => 'bangumi',
            'youtube.com' => 'youtube',
            'youtu.be' => 'youtube',
            'bilibili.com' => 'bilibili',
        ];

        foreach ($patterns as $pattern => $type) {
            if (stripos($url, $pattern) !== false) {
                return $type;
            }
        }

        return 'other';
    }
}
