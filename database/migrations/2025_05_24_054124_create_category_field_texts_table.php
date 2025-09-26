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
        Schema::create('category_field_texts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_field_id');
            $table->unsignedBigInteger('ad_id');
            $table->text('value_ar')->nullable();
            $table->text('value_en')->nullable();
            $table->timestamps();

            // علاقات المفتاح الخارجي
            $table->foreign('category_field_id')->references('id')->on('category_fields')->onDelete('cascade');
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_texts');
    }
};
