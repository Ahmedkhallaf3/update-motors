<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_field_values', function (Blueprint $table) {
            $table->string('field_type')->nullable()->after('value_en');
        });
    }

    public function down(): void
    {
        Schema::table('category_field_values', function (Blueprint $table) {
            $table->dropColumn('field_type');
        });
    }
};
