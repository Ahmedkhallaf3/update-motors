<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class city extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable = ['name_ar', 'name_en', 'country_id',];

    public function country()
    {
        return $this->belongsTo(country::class);
    }
}
