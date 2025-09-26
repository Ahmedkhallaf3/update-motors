<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Faker\Factory as Faker;

class NotificationController extends Controller
{
    public function getUserNotifications(Request $request)
    {

        $perPage = $request->input('per_page', 15);  // لو مش مرسل تستخدم 10
        $page = $request->input('page', 1);         // لو مش مرسل تستخدم 1


        $user = auth()->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->with(['ad', 'fromUser'])
            // ->get();
            ->paginate($perPage, ['*'], 'page', $page);
        // تحديث الإشعارات اللي لم تُقرأ إلى مقروءة
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);


        $notifications->transform(function ($notification) use ($user) {
            $fromUser = optional($notification->fromUser);
            $fromUserName = trim($fromUser->first_name . ' ' . $fromUser->last_name);

            if ($notification->type === 'follow') {
                $message_ar = "$fromUserName بدأ بمتابعتك";
                $message_en = "$fromUserName started following you";
            } elseif ($notification->type === 'new_ad') {
                $message_ar = "$fromUserName نشر إعلان جديد!";
                $message_en = "$fromUserName posted a new ad!";
            } else {
                $message_ar = $notification->message_ar;
                $message_en = $notification->message_en;
            }

            // 🟡 ترجمة حالات الإعلان للعربية
            $statusTranslations = [
                'pending' => 'قيد الانتظار',
                'approved' => 'مقبول',
                'rejected' => 'مرفوض',
                'expired' => 'منتهي',
            ];

            // ✅ إضافة حالة الإعلان فقط لو المستخدم أدمن
            if ($notification->ad_id && $notification->ad && $user->role === 'admin') {
                $status = $notification->ad->status;
                $status_ar = $statusTranslations[$status] ?? $status;

                $message_en .= " and the status of the ad is $status";
                $message_ar .= "، وحالة الإعلان حاليا هي $status_ar";
            }

            $image = null;
            if ($notification->type === 'follow') {
                $image = $fromUser->profile_image ? url('profile_images/' . $fromUser->profile_image) : null;
            } elseif (in_array($notification->type, ['ad_status', 'new_ad'])) {
                $image = optional($notification->ad)->main_image ? url($notification->ad->main_image) : null;
            }

            return [
                'id' => $notification->id,
                'user_id' => $notification->user_id,
                'from_user_id' => $notification->from_user_id,
                'type' => $notification->type,
                'ad_id' => $notification->ad_id,
                'message_ar' => $message_ar,
                'message_en' => $message_en,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at,
                'updated_at' => $notification->updated_at,
                'image' => $image,
            ];
        });

        return response()->json($notifications);
    }


    public function markAsRead(Request $request)
    {
        $user = auth()->user();

        // التأكد من أن الطلب يحتوي على مصفوفة صحيحة
        $notificationIds = $request->input('notification_ids', []);



        // جلب الإشعارات الخاصة بالمستخدم فقط
        $notifications = Notification::whereIn('id', $notificationIds)
            ->where('user_id', $user->id)
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json(['message' => 'No valid notifications found.'], 404);
        }

        // تحديث الإشعارات لتكون مقروءة
        Notification::whereIn('id', $notifications->pluck('id'))->update(['is_read' => 1]);

        return response()->json(['message' => 'Notifications marked as read']);
    }

  public function testNotification()
{
   $user = auth()->user();
    if (!$user) {
        return response()->json(['message' => 'يجب تسجيل الدخول أولاً'], 401);
    }
    $faker = Faker::create();

    $notifications = [];

    for ($i = 1; $i <= 10; $i++) {
        $randomName = $faker->firstName;
        $randomNumber = $faker->randomNumber(5, true);

        $notifications[] = Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'message_ar' => "هذا إشعار اختبار باسم $randomName ورقم $randomNumber",
            'message_en' => "This is a test notification with name $randomName and number $randomNumber",
            'is_read' => false,
        ]);
    }

    return response()->json([
        'message' => '10 test notifications created',
        'notifications' => $notifications,
    ], 201);
}

}
