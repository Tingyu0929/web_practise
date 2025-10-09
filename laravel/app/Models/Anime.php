<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anime extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'categories',
        'type',
        'description',
        'status',
        'release_date',
        'source_url',
        'weekly_schedule',
        'voice_actors',
        'copyright',
        'trailer_url',
        'video_links',
        'staff'
    ];

    protected $casts = [
        'categories' => 'array',
        'release_date' => 'date',
        'voice_actors' => 'array',
        'video_links' => 'array',
        'staff' => 'array',
    ];

    /**
     * 關聯播放平台（一對多關聯）
     */
    public function platforms()
    {
        return $this->hasMany(AnimePlatform::class, 'anime_id');
    }

    /**
     * 取得平台列表（用於顯示）
     */
    public function getPlatformsListAttribute()
    {
        return $this->platforms()->distinct('platform')->pluck('platform')->toArray();
    }

    /**
     * 取得特定地區的播放平台
     */
    public function platformsInRegion($region)
    {
        return $this->platforms()->where('region', $region);
    }

    /**
     * 取得所有播放地區
     */
    public function getRegionsAttribute()
    {
        return $this->platforms()->distinct('region')->pluck('region')->toArray();
    }

    /**
     * 取得所有播放平台
     */
    public function getPlatformsNamesAttribute()
    {
        return $this->platforms()->distinct('platform')->pluck('platform')->toArray();
    }

    /**
     * 檢查是否在特定地區可觀看
     */
    public function isAvailableInRegion($region)
    {
        return $this->platforms()->where('region', $region)->exists();
    }

    /**
     * 取得動漫在特定平台的可用性
     */
    public function getAvailabilityOnPlatform($platform, $region = null)
    {
        $query = $this->platforms()->where('platform', $platform);

        if ($region) {
            $query->where('region', $region);
        }

        return $query->first();
    }
}
