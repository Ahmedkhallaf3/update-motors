<?php

namespace App\Http\Middleware;

use Closure;

class CheckIfUserBlocked
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        // لو المستخدم غير موثق، نمشي عادي (مثلاً تسجيل دخول)
        if (!$user) {
            return $next($request);
        }

        // لو محظور، نرجع رسالة خطأ
        if ($user->is_blocked) {
            return response()->json([
                'message_en' => 'Your account is blocked',
                'message_ar' => 'حسابك محظور',
            ], 403);
        }

        return $next($request);
    }
}
