<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryField extends Model
{
    use HasFactory;
    protected $fillable = ['category_id', 'field_ar', 'field_en', 'value_ar', 'value_en'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function values()
{
    return $this->hasMany(CategoryFieldValue::class);
}
}
