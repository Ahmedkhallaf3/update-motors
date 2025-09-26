<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use App\Models\userauth;
use Illuminate\Http\Request;
use App\Models\Notification;

class FollowController extends Controller
{
   public function follow(Request $request, $id)
{
    $user = auth()->user(); // المستخدم الحالي
    $following = Userauth::findOrFail($id); // المستخدم المراد متابعته

    // تحقق مما إذا كان المستخدم يتابع بالفعل
    if (Follower::where('follower_id', $user->id)->where('following_id', $id)->exists()) {
        return response()->json(['message' => 'You are already following this user'], 400);
    }

    // إضافة المتابعة
    Follower::create([
        'follower_id' => $user->id,
        'following_id' => $id
    ]);

    // إرسال إشعار للمستخدم المتابع
    Notification::create([
        'user_id' => $id,
        'from_user_id' => $user->id,
        'type' => 'follow',
'message_ar' => "{$user->first_name} بدأ يتابعك!",
    'message_en' => "{$user->first_name} started following you!",    ]);

    return response()->json(['message' => 'Followed successfully']);
}


    public function unfollow(Request $request, $id)
    {
        $user = auth()->user();
        $following = userauth::findOrFail($id);

        $deleted = Follower::where('follower_id', $user->id)->where('following_id', $id)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Unfollowed successfully']);
        }

        return response()->json(['message' => 'You are not following this user'], 400);
    }

    public function countFollowers($id)
    {
        $user = Userauth::findOrFail($id);
        return response()->json([
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ]);
    }
}
