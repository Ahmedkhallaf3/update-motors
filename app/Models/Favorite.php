<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'ad_id'];

    public function user()
    {
        return $this->belongsTo(Userauth::class);
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
