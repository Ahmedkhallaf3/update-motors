<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleAuthCodeToUserauthsTable extends Migration
{
    public function up()
    {
        Schema::table('userauths', function (Blueprint $table) {
            $table->string('google_auth_code')->nullable()->after('google_id');
        });
    }

    public function down()
    {
        Schema::table('userauths', function (Blueprint $table) {
            $table->dropColumn('google_auth_code');
        });
    }
}
