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
        // Check if the table already exists
        if (!Schema::hasTable('ad_views')) {
            Schema::create('ad_views', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade'); // الإعلان
                $table->foreignId('user_id')->nullable()->constrained('userauths')->onDelete('cascade'); // المستخدم الذي شاهد الإعلان
                $table->timestamps();
                $table->string('identifier'); // يمكن أن يكون user_id أو IP أو session ID

                $table->unique(['ad_id', 'user_id']); // ضمان عدم تكرار المستخدم لنفس الإعلان
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_views');
    }
};
