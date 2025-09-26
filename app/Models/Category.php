<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class category extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable = ['name_ar', 'name_en', 'image',];
    public function fields()
    {
        return $this->hasMany(CategoryField::class);
    }
  public function ads()
{
    return $this->hasMany(Ad::class);
}

}
