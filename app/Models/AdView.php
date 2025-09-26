<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdView extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'ad_id','identifier'];

    public function user()
    {
        return $this->belongsTo(userauth::class);
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
