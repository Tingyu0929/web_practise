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
        Schema::create('animes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('image_url')->nullable();
            $table->json('categories')->nullable(); // 分類
            $table->string('type', 50)->default('unknown'); // 動畫類型
            $table->text('description')->nullable(); // 動畫描述
            $table->string('status', 20)->default('active'); // 動畫狀態
            $table->date('release_date')->nullable(); // 發布日期
            $table->text('source_url')->nullable(); // 來源網址
            $table->timestamps();

            // 索引
            $table->index('title');
            $table->index('type');
            $table->index('status');
            $table->index('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animes');
    }
};
