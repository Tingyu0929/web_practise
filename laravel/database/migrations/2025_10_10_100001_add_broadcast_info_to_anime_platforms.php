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
        Schema::table('anime_platforms', function (Blueprint $table) {
            // 檢查欄位是否已存在，避免重複添加
            if (!Schema::hasColumn('anime_platforms', 'broadcast_day')) {
                $table->string('broadcast_day')->nullable()->after('availability_status'); // 播出星期，例如："星期三", "週三"
            }
            if (!Schema::hasColumn('anime_platforms', 'broadcast_time')) {
                $table->time('broadcast_time')->nullable()->after('broadcast_day'); // 播出時間，例如："23:30"
            }
            if (!Schema::hasColumn('anime_platforms', 'broadcast_timezone')) {
                $table->string('broadcast_timezone', 10)->default('Asia/Hong_Kong')->after('broadcast_time'); // 時區
            }
            if (!Schema::hasColumn('anime_platforms', 'platform_url')) {
                $table->text('platform_url')->nullable()->after('broadcast_timezone'); // 平台連結
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anime_platforms', function (Blueprint $table) {
            $table->dropColumn([
                'broadcast_day',
                'broadcast_time',
                'broadcast_timezone',
                'platform_url'
            ]);
        });
    }
};
