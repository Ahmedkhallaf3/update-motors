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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // IPv4/IPv6 compatible
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('general_visitor')->default(false);
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->index('country_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
