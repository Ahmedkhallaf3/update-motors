<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdFeature extends Model
{
    protected $table = 'car_ad_features'; // تأكد من اسم الجدول

    protected $fillable = [
        'car_ad_id', 
        'feature_id',
    ];

    public function value()
{
    return $this->belongsTo(CategoryFieldValue::class, 'feature_id');
}

}
