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
        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_field_id')->constrained('category_field_values')->onDelete('cascade'); // ربط الموديل بالقيمة من جدول category_field_values
            $table->string('value_ar');  // اسم الموديل بالعربية
            $table->string('value_en');  // اسم الموديل بالإنجليزية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_models');
    }
};
