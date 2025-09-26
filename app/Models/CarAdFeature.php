<?php
 namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarAdFeature extends Model
{
    protected $fillable = ['car_ad_id', 'feature_id'];

    public function carAd()
    {
        return $this->belongsTo(CarAd::class);
    }

    public function feature()
    {
        return $this->belongsTo(CategoryFieldValue::class, 'feature_id');
    }
}
