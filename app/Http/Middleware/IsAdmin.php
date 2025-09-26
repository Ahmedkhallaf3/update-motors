<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'message' => [
                    'en' => 'Only admins can access this resource.',
                    'ar' => 'هذا المورد متاح للمسؤولين فقط.'
                ]
            ], 403);
        }

        return $next($request);
    }
}
