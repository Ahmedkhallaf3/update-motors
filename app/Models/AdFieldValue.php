<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdFieldValue extends Model
{
    use HasFactory;

    protected $fillable = ['ad_id', 'category_field_id', 'category_field_value_id'];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    public function field()
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id');
    }

    public function fieldValue()
    {
        return $this->belongsTo(CategoryFieldValue::class, 'category_field_value_id');
    }
}
