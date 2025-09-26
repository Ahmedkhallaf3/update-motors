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
        Schema::table('userauths', function (Blueprint $table) {
            $table->string('profile_image')->nullable()->after('password');
            $table->string('cover_image')->nullable()->after('profile_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('userauths', function (Blueprint $table) {
            $table->dropColumn(['profile_image', 'cover_image']);

        });
    }
};
