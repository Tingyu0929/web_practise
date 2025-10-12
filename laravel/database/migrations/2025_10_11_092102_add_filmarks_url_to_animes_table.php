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
            $table->string('filmarks_url', 500)->nullable()->after('source_url')->comment('Filmarks 動畫頁面 URL');
            $table->index('filmarks_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animes', function (Blueprint $table) {
            $table->dropIndex(['filmarks_url']);
            $table->dropColumn('filmarks_url');
        });
    }
};
