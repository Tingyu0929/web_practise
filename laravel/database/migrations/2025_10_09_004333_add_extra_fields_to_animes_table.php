<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('animes', function (Blueprint $table) {
            $table->string('weekly_schedule')->nullable()->after('release_date'); // 每周更新時間，例如："每週三 23:30"
            $table->json('voice_actors')->nullable()->after('weekly_schedule'); // 配音員列表
            $table->string('copyright')->nullable()->after('voice_actors'); // 版權所屬
            $table->text('trailer_url')->nullable()->after('copyright'); // 預告片連結
            $table->json('video_links')->nullable()->after('trailer_url'); // 影片連結（多個）
            $table->json('staff')->nullable()->after('video_links'); // 製作人員
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animes', function (Blueprint $table) {
            $table->dropColumn([
                'weekly_schedule',
                'voice_actors',
                'copyright',
                'trailer_url',
                'video_links',
                'staff'
            ]);
        });
    }
};
