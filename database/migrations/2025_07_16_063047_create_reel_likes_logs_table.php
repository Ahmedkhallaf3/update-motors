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
        Schema::create('reel_likes_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reel_id');        // معرف الريل
            $table->unsignedBigInteger('user_id');        // معرف المستخدم
            $table->enum('reaction', ['like', 'dislike']); // نوع التفاعل: لايك أو ديسلايك
            $table->timestamps();

            // المفتاح الأجنبي يشير إلى reels.reels_id
            $table->foreign('reel_id')->references('reels_id')->on('reels')->onDelete('cascade');
            // المفتاح الأجنبي يشير إلى userauths.id
            $table->foreign('user_id')->references('id')->on('userauths')->onDelete('cascade');

            // عدم السماح بتكرار التفاعل لنفس المستخدم على نفس الريل
            $table->unique(['reel_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_likes_logs');
    }
};
