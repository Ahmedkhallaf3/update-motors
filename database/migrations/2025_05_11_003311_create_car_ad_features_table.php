<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarAdFeaturesTable extends Migration
{
    public function up()
    {
        // التحقق إذا كان الجدول موجود
        if (!Schema::hasTable('car_ad_features')) {
            Schema::create('car_ad_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('car_ad_id')->constrained()->onDelete('cascade');
                $table->foreignId('feature_id')->constrained('category_field_values')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('car_ad_features');
    }
}
