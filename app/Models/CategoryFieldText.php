<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryFieldText extends Model
{
    use HasFactory;

    protected $fillable = ['category_field_id', 'ad_id', 'value_ar', 'value_en'];

    public function categoryField()
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id');
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
