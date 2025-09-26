<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Userauth extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'userauths'; // تأكيد اسم الجدول إذا لم يكن افتراضيًا

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


   protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'phone_number',
    'password',
    'profile_image',
    'cover_image',
    'role',
    'is_blocked',
    'google_auth_code',      // اضف هذا
    'google_access_token',   // واضف هذا
];


    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function followers()
    {
        return $this->hasMany(Follower::class, 'following_id'); // الأشخاص الذين يتابعون هذا المستخدم
    }

    public function following()
    {
        return $this->hasMany(Follower::class, 'follower_id'); // الأشخاص الذين يتابعهم هذا المستخدم
    }

    public function ads()
    {
        return $this->hasMany(Ad::class, 'user_id');
    }
}
