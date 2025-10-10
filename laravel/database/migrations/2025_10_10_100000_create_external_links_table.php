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
        Schema::create('external_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anime_id')->constrained()->onDelete('cascade');
            $table->string('type', 50); // official, wikipedia, twitter, mal, anilist, anidb, bangumi, youtube, etc.
            $table->string('name', 200); // 顯示名稱
            $table->text('url'); // 連結URL
            $table->string('language', 10)->nullable(); // zh, ja, en (for wikipedia)
            $table->integer('order')->default(0); // 排序
            $table->timestamps();

            // 索引
            $table->index('anime_id');
            $table->index('type');
            $table->unique(['anime_id', 'url']); // 防止重複連結
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_links');
    }
};
