<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Chat;
use Intervention\Image\Facades\Image;

use App\Models\Userauth;
use App\Events\MessageSent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
public function sendMessage(Request $request)
{
    $data = $request->validate([
        'message' => 'nullable|string',
        'receiver_id' => 'nullable|exists:userauths,id',
        'chat_id' => 'nullable|string',
        'attachments.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // صور
    ]);

    $senderId = auth()->id();
    $receiverId = null;
    $chat = null;

    // جلب أو إنشاء المحادثة بناء على chat_id أو receiver_id (كما هو عندك)
    if (!empty($data['chat_id'])) {
        $chat = Chat::findOrFail($data['chat_id']);
        $receiverId = $senderId == $chat->user_one_id ? $chat->user_two_id : $chat->user_one_id;
    } elseif (!empty($data['receiver_id'])) {
        $receiverId = $data['receiver_id'];
        $userOne = min($senderId, $receiverId);
        $userTwo = max($senderId, $receiverId);
        $chat = Chat::firstOrCreate([
            'user_one_id' => $userOne,
            'user_two_id' => $userTwo,
        ]);
    } else {
        return response()->json(['error' => 'Either receiver_id or chat_id is required'], 422);
    }

    // رفع الصور إن وجدت
    $attachments = [];
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
    $filename = uniqid() . '.' . $file->getClientOriginalExtension();
    $path = public_path('chat_attachments/' . $filename);

    // ضغط الصورة بنسبة جودة 75 (تقدر تزود أو تنقص حسب الحاجة)
    $image = Image::make($file)->encode($file->getClientOriginalExtension(), 75);
    $image->save($path);

    $attachments[] = asset('chat_attachments/' . $filename);
}

    }

    // إنشاء الرسالة
    $message = Message::create([
        'chat_id' => $chat->id,
        'sender_id' => $senderId,
        'receiver_id' => $receiverId,
        'message' => $data['message'] ?? '',
        'attachments' => $attachments,
    ]);

    event(new MessageSent($message));

    return response()->json([
        'message' => $message,
        'chat_id' => $chat->id
    ]);
}

public function fetchMessages($chatId)
{
    $userId = auth()->id();

    // نتحقق إذا كان فيه رسائل جديدة لليوزر قبل ما نحدثها
    $hasNewMessages = Message::where('receiver_id', $userId)
        ->where('is_read', false)
        ->exists();

    // جلب المحادثات بناءً على chat_id
    $messages = Message::where('chat_id', $chatId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($msg) {
            return [
                'id' => $msg->id,
                'chat_id' => $msg->chat_id,
                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,
                'message' => $msg->message,
                'attachments' => $msg->attachments ?? [],
                'is_read' => $msg->is_read,
'created_at' => $msg->created_at->addHours(2)->toDateTimeString(),
            ];
        });

    // تحديث جميع الرسائل إلى "مقروءة"
    Message::where('chat_id', $chatId)
        ->where('receiver_id', $userId)
        ->update(['is_read' => true]);

    return response()->json([
        'messages' => $messages,
        'has_new_messages' => $hasNewMessages,
    ]);
}



public function markAsRead($userId)
{
    Message::where('sender_id', $userId)
        ->where('receiver_id', auth()->id())
        ->update(['is_read' => true]);

    return response()->json(['status' => 'read']);
}

public function conversations()
{
    $userId = auth()->id();

    // Get conversations based on user_id and group by chat_id
    $messages = Message::selectRaw('
        IF(sender_id = ?, receiver_id, sender_id) as user_id,
        MAX(created_at) as last_message_time,
        MAX(id) as last_message_id,
        SUM(IF(receiver_id = ? AND is_read = 0, 1, 0)) as unread_count,
        chat_id,
        sender_id,
        receiver_id
    ', [$userId, $userId])
    ->where(function ($query) use ($userId) {
        $query->where('sender_id', $userId)->orWhere('receiver_id', $userId);
    })
    ->groupBy(DB::raw('chat_id, LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id), sender_id, receiver_id')) // Add sender_id and receiver_id to GROUP BY
    ->orderByDesc('last_message_time')
    ->get();

    // Map the result to avoid duplication and format the data
    $result = $messages->map(function ($item) {
        // Get the user data
        $user = Userauth::find($item->user_id);

        // Get the last message for each conversation
        $message = Message::find($item->last_message_id);

        // Format the timestamp for the message
$created = $message->created_at->addHours(2);
        $time = $created->isToday() ? $created->format('h:i A') : ($created->isYesterday() ? 'Yesterday' : $created->format('d/m/Y'));

        return [
            'chat_id' => (int) $item->chat_id,
            'user_id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
'profile_image' => $user->profile_image ? asset('profile_images/' . $user->profile_image) : null,
            'last_message' => $message->message,
            'time_en' => $created->format('g:i A'),
            'time_ar' => $created->format('g:i') . ($created->format('A') === 'AM' ? ' صباحًا' : ' مساءً'),
            'unread' => $item->unread_count,
        ];
    });

    return response()->json($result);
}
  
  public function hasNewMessages()
{
    $userId = auth()->id();

    $hasNewMessages = Message::where('receiver_id', $userId)
        ->where('is_read', false)
        ->exists();

    return response()->json([
        'has_new_messages' => $hasNewMessages
    ]);
}



public function deleteChat($chatId)
{
    // حذف جميع الرسائل المرتبطة بـ chat_id
    Message::where('chat_id', $chatId)->delete();

    return response()->json(['status' => 'deleted']);
}


}
