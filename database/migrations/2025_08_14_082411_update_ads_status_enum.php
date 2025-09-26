<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تعديل العمود status لإضافة القيمة 'inactive'
        DB::statement("ALTER TABLE `ads` MODIFY `status` ENUM('pending','approved','rejected','inactive') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // ارجاع العمود للقيم الأصلية
        DB::statement("ALTER TABLE `ads` MODIFY `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    }
};
