<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
   protected $fillable = ['sender_id', 'receiver_id', 'message', 'is_read','chat_id','attachments',  
];
  
  protected $casts = [
        'attachments' => 'array',
    ];

    public function sender() { return $this->belongsTo(Userauth::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(Userauth::class, 'receiver_id'); }
  
  public function chat()
{
    return $this->belongsTo(Chat::class);
}

}
