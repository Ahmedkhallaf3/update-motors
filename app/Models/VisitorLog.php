<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use HasFactory;

    protected $table = 'visitor_logs';  // اسم الجدول

    public $timestamps = true;  // لأن في created_at و updated_at

    protected $fillable = [
        'ip_address',
        'country_id',
        'category_id',
        'general_visitor',
        'visited_at',
        'left_at', // ← أضف هذا السطر

    ];

    protected $casts = [
        'general_visitor' => 'boolean',
        'visited_at' => 'datetime',
    ];

    // لو حبيت تضيف علاقات مع جداول الدول أو التصنيفات تقدر تضيفها هنا لاحقاً
}
