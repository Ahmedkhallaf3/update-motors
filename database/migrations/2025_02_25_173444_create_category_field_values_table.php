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
        Schema::create('category_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_field_id')->constrained()->onDelete('cascade'); // ربط بالقيم
            $table->text('value_ar'); // القيمة بالعربي
            $table->text('value_en'); // القيمة بالإنجليزي
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_values');
    }
};
