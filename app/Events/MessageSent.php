<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
      public Message $message; // <<< لازم تكون PUBLIC

   public function __construct(Message $message)
    {
        $this->message = $message;
    }

     public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->message->chat_id);
    }
  
 

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    // البيانات اللي هتوصل للـ frontend
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message' => $this->message->message,
                  'attachments' => $this->message->attachments ?? [],

            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}
