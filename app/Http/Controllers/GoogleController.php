<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Userauth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Follower;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
use Exception;


class GoogleController extends Controller
{
    public function googlepage()
    {
        return Socialite::driver('google')->redirect();
    }


    public function googleCallback(Request $request)
    {
        try {
            $googleCode = $request->query('code');
            // جلب بيانات المستخدم من جوجل
            $googleUser = Socialite::driver('google')->stateless()->user();

            // البحث عن المستخدم في قاعدة البيانات
            $user = Userauth::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // حفظ كود جوجل
                $user->update(['google_auth_code' => $googleCode]);
            } else {
                // إنشاء مستخدم جديد مع حفظ كود جوجل
                $fullName = $googleUser->getName();
                $nameParts = explode(' ', $fullName, 2);

                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '-';

                $user = Userauth::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleUser->getEmail(),
                    'google_auth_code' => $googleCode,
                    'phone_number' => mt_rand(500000000, 599999999),
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'user',
                ]);

                // إرسال إشعار للـ Admins
                $adminUsers = Userauth::where('role', 'admin')->get();
                foreach ($adminUsers as $admin) {
                    DB::table('notifications')->insert([
                        'user_id' => $admin->id,
                        'from_user_id' => $user->id,
                        'type' => 'new_user_registered',
                        'message_ar' => 'تم تسجيل مستخدم جديد: ' . $user->first_name . ' ' . $user->last_name,
                        'message_en' => 'New user registered: ' . $user->first_name . ' ' . $user->last_name,
                        'is_read' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // إنشاء توكن JWT وحفظه
            $token = JWTAuth::fromUser($user);
            $user->update(['google_access_token' => $token]);

            // إعادة التوجيه مع الكود فقط (بدون التوكن)
            return redirect()->away('https://motorssooq.com/en/auth/google/callback?code=' . urlencode($googleCode));
        } catch (Exception $e) {
            return redirect()->away('https://motorssooq.com/en/auth/google/callback?error=' . urlencode($e->getMessage()));
        }
    }



    public function getLogindataUsingGoogleCode(Request $request)
    {
        try {
            $googleCode = $request->input('code');
            if (!$googleCode) {
                return response()->json(['message' => 'Google code is required'], 400);
            }

            // البحث عن المستخدم في قاعدة البيانات باستخدام google_id (الكود اللي جاي من الفرو نت)
            $user = Userauth::where('google_auth_code', $googleCode)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found with this Google code'], 404);
            }

            // إنشاء توكن JWT
            $token = JWTAuth::fromUser($user);

            // جلب المتابعين والمتابَعين
            $followers = Follower::where('following_id', $user->id)
                ->with('follower')
                ->get()
                ->map(function ($follow) use ($user) {
                    $follower = $follow->follower;
                    $isFollowing = Follower::where('follower_id', $user->id)
                        ->where('following_id', $follower->id)
                        ->exists();

                    return [
                        'id' => $follower->id,
                        'first_name' => $follower->first_name,
                        'last_name' => $follower->last_name,
                        'email' => $follower->email,
                        'phone_number' => $follower->phone_number,
                        'profile_image' => $follower->profile_image ? asset('profile_images/' . $follower->profile_image) : null,
                        'created_at' => $follower->created_at,
                        'is_following' => $isFollowing,
                    ];
                });

            $followers_count = Follower::where('following_id', $user->id)->count();
            $following_count = Follower::where('follower_id', $user->id)->count();

            $following = Follower::where('follower_id', $user->id)
                ->pluck('following_id')
                ->toArray();

            // إرجاع البيانات بتنسيق JSON
            return response()->json([
                'message' => 'Login successful | تم تسجيل الدخول بنجاح',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role,
                    'profile_image' => $user->profile_image ? asset('profile_images/' . $user->profile_image) : null,
                    'cover_image' => $user->cover_image ? asset('cover_images/' . $user->cover_image) : null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'followers_count' => $followers_count,
                    'following_count' => $following_count,
                    'is_blocked' => (bool) $user->is_blocked,
                ],
                'followers' => $followers,
                'following' => $following,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}
