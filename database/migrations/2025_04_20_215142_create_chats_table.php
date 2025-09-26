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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
           $table->unsignedBigInteger('user_one_id');
        $table->unsignedBigInteger('user_two_id');
        $table->timestamps();

        $table->unique(['user_one_id', 'user_two_id']); // عشان كل شات يكون مميز
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
