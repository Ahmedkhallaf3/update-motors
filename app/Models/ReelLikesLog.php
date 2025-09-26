<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReelLikesLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_id',
        'user_id',
        'reaction',
    ];

    // لو حابب تربطها بموديلات Reel و User:
    public function reel()
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
