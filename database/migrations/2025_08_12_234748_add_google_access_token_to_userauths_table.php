<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleAccessTokenToUserauthsTable extends Migration
{
    public function up()
    {
        Schema::table('userauths', function (Blueprint $table) {
            $table->text('google_access_token')->nullable()->after('google_auth_code');
            $table->text('google_refresh_token')->nullable()->after('google_access_token'); // لو بتخزن refresh token
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token'); // تاريخ انتهاء التوكن
        });
    }

    public function down()
    {
        Schema::table('userauths', function (Blueprint $table) {
            $table->dropColumn('google_access_token');
            $table->dropColumn('google_refresh_token');
            $table->dropColumn('google_token_expires_at');
        });
    }
}
