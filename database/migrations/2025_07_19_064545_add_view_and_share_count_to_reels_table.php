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
        Schema::table('reels', function (Blueprint $table) {
            $table->integer('reels_view_count')->default(0)->after('reels_like_count');
            $table->integer('reels_share_count')->default(0)->after('reels_view_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->dropColumn(['reels_view_count', 'reels_share_count']);
        });
    }
};
