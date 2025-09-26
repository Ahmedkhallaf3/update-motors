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
        Schema::create('category_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade'); // ربط بالحقل
            $table->string('field_ar'); // اسم الحقل بالعربي
            $table->string('field_en'); // اسم الحقل بالإنجليزي
            $table->text('value_ar')->nullable(); // قيمة الحقل بالعربي
            $table->text('value_en')->nullable(); // قيمة الحقل بالإنجليزي
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};
