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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('userauths')->onDelete('cascade');
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->timestamps();

            // التأكد من عدم تكرار نفس الإعلان للمستخدم
            $table->unique(['user_id', 'ad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
