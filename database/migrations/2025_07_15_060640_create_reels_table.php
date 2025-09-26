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
        Schema::create('reels', function (Blueprint $table) {
            $table->id('reels_id');
            $table->unsignedBigInteger('reels_ad_id');
            $table->string('reels_video_url');
            $table->string('reels_thumbnail_url')->nullable(); // العمود الجديد للثُمبنييل (اختياري)
            $table->integer('reels_like_count')->default(0);
            $table->timestamps();

            // Foreign key linking to ads table
            $table->foreign('reels_ad_id')
                ->references('id')
                ->on('ads')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reels');
    }
};
