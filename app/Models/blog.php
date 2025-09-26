<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class blog extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable = ['title_ar', 'title_en', 'description_en', 'description_ar', 'image'];
}

