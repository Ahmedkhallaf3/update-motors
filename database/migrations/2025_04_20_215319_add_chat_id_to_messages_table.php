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
        // Check if the 'chat_id' column exists in the 'messages' table
        if (!Schema::hasColumn('messages', 'chat_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->unsignedBigInteger('chat_id')->after('id');

                // Add foreign key constraint
                $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['chat_id']);
            // Then drop the column
            $table->dropColumn('chat_id');
        });
    }
};
