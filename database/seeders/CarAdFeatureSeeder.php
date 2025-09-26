<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CarAdFeatureSeeder extends Seeder
{
    public function run()
{
    $carAd = CarAd::first(); // أو استبدالها باستعلام لجلب إعلان معين

    // فرضًا أننا نريد إضافة مجموعة من المميزات إلى الإعلان
    $features = [362, 380, 407]; // مثال على IDs من جدول category_field_values

    foreach ($features as $featureId) {
        CarAdFeature::create([
            'car_ad_id' => $carAd->id,
            'feature_id' => $featureId,
        ]);
    }
}

}
