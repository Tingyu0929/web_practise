<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use Illuminate\Http\Request;

class AnimeController extends Controller
{
    /**
     * 取得所有動漫列表
     */
    public function index(Request $request)
    {
        $query = Anime::with('platforms');

        // 搜尋功能
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        // 平台篩選
        if ($platform = $request->input('platform')) {
            $query->whereHas('platforms', function ($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        // 地區篩選
        if ($region = $request->input('region')) {
            $query->whereHas('platforms', function ($q) use ($region) {
                $q->where('region', $region);
            });
        }

        // 排序
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 24);
        $animes = $query->paginate($perPage);

        return response()->json($animes);
    }

    /**
     * 取得單一動漫詳細資訊
     */
    public function show($id)
    {
        $anime = Anime::with('platforms')->findOrFail($id);
        return response()->json($anime);
    }

    /**
     * 取得所有平台列表（去重）
     */
    public function platforms()
    {
        $platforms = \App\Models\AnimePlatform::select('platform')
            ->distinct()
            ->pluck('platform')
            ->map(function ($name, $index) {
                return [
                    'id' => $index + 1,
                    'name' => $name
                ];
            })
            ->values();

        return response()->json($platforms);
    }

    /**
     * 取得統計資訊
     */
    public function stats()
    {
        $stats = [
            'total_animes' => Anime::count(),
            'platforms_count' => \App\Models\AnimePlatform::distinct('platform')->count('platform'),
            'latest_update' => Anime::latest('updated_at')->first()?->updated_at,
        ];

        return response()->json($stats);
    }
}
