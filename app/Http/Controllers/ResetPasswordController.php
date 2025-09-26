<?php

namespace App\Http\Controllers;

use App\Models\Userauth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
class ResetPasswordController extends Controller
{
    /**
     * إرسال كود إعادة تعيين كلمة المرور عبر البريد الإلكتروني
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:userauths,email',
        ]);

        try {
            $code = rand(100000, 999999);

            // إرسال الكود عبر البريد الإلكتروني
            Mail::raw("Your password reset code is: $code", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Password Reset Code');
            });

            // تخزين الكود في الكاش لمدة 10 دقائق
            Cache::put('reset_code_' . $request->email, $code, now()->addMinutes(10));

            return response()->json(['message' => 'Reset code sent to email'], 200);
        } catch (\Exception $e) {
            Log::error('Error sending reset code: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send reset code'], 500);
        }
    }

    /**
     * التحقق من صحة كود إعادة التعيين
     */
  public function verifyCode(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:userauths,email',
        'code' => 'required|numeric'
    ]);

    $storedCode = Cache::get('reset_code_' . $request->email);

    if (!$storedCode) {
        return response()->json(['error' => 'Code expired or not found'], 400);
    }

    if ($request->code != $storedCode) {
        return response()->json(['error' => 'Invalid code'], 400);
    }

    // الكود صحيح، جيب المستخدم وارجع توكن
    $user = Userauth::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $token = JWTAuth::fromUser($user);

    // إضافة سجل التوكن لمزيد من التحقق
    Log::info('Generated token:', ['token' => $token]);

    return response()->json([
        'message' => 'Code verified successfully',
        'token' => $token,
    ], 200);
}



    /**
     * إعادة تعيين كلمة المرور بعد التحقق من الكود
     */
  public function resetPassword(Request $request)
{
    $request->validate([
        'password' => 'required|min:6|confirmed',
    ]);

    try {
        // التحقق من التوكن
        $token = JWTAuth::parseToken();
        $payload = $token->getPayload(); // للحصول على بيانات التوكن

        Log::info('Token Payload:', ['payload' => $payload]);

        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User not found or token invalid'], 404);
        }

        // تحديث كلمة المرور
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully'], 200);
    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 400);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['error' => 'Token error'], 400);
    } catch (\Exception $e) {
        Log::error('Error resetting password: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to reset password'], 500);
    }
}



}
