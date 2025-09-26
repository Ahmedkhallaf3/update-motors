<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'from_user_id', 'ad_id', 'type', 'message_ar','message_en', 'is_read'];

    public function user()
    {
        return $this->belongsTo(userauth::class, 'user_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(userauth::class, 'from_user_id');
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
