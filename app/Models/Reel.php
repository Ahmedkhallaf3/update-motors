<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    use HasFactory;

    protected $primaryKey = 'reels_id';

    protected $fillable = [
        'reels_ad_id',
        'reels_video_url',
        'reels_thumbnail_url', // ✅ أضفنا عمود الثمبنيل هنا
        'reels_like_count',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'reels_ad_id');
    }
}
