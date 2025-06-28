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
        Schema::create('anime_platforms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anime_id');
            $table->string('region', 50); // 地區：香港、台灣、中國大陸
            $table->string('platform', 100); // 播放平台：Netflix、Disney+、Bilibili 等
            $table->string('availability_status', 20)->default('available'); // 可觀看狀態
            $table->text('notes')->nullable(); // 備註
            $table->timestamps();

            // 外鍵約束
            $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
            
            // 索引
            $table->index(['anime_id', 'region', 'platform']);
            $table->index('region');
            $table->index('platform');
            
            // 唯一約束 - 防止同一動漫在同一地區同一平台重複
            $table->unique(['anime_id', 'region', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anime_platforms');
    }
};
