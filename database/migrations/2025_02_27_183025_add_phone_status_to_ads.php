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
        Schema::table('ads', function (Blueprint $table) {
            $table->string('phone_number')->after('price')->nullable(); // إضافة رقم الهاتف
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('phone_number'); // إضافة الحالة
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'status']);

        });
    }
};
