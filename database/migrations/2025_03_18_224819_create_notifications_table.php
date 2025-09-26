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
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('userauths')->onDelete('cascade'); // المستخدم المستلم
                $table->foreignId('sender_id')->nullable()->constrained('userauths')->onDelete('set null'); // المستخدم المرسل
                $table->foreignId('ad_id')->nullable()->constrained('ads')->onDelete('set null'); // المستخدم المرسل

                $table->string('type'); // نوع الإشعار (follow, ad_status)
                $table->text('message_ar'); // 
                $table->text('message_en'); // نص الإشعار
                $table->boolean('is_read')->default(false); // حالة قراءة الإشعار
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
