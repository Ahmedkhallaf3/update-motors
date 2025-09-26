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
        // Check if the 'blogs' table already exists, if not, create it
        if (!Schema::hasTable('blogs')) {
            Schema::create('blogs', function (Blueprint $table) {
                $table->id();
                $table->string('title_en');
                $table->string('title_ar');
                $table->text('description_en');
                $table->text('description_ar');
                $table->string('image');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the 'blogs' table if it exists
        Schema::dropIfExists('blogs');
    }
};
