<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReelLikesLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReelController extends Controller
{
    public function toggleLike(Request $request, $reelId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized: You must be logged in to react.'], 401);
        }

        $reaction = $request->input('reaction'); // 'like' or 'dislike'
        if (!in_array($reaction, ['like', 'dislike'])) {
            return response()->json(['message' => 'Invalid reaction type. Allowed values: like, dislike.'], 422);
        }

        $reel = \DB::table('reels')->where('reels_id', $reelId)->first();
        if (!$reel) {
            return response()->json(['message' => 'Reel not found. Invalid reel ID provided.'], 404);
        }

        $existing = ReelLikesLog::where('reel_id', $reelId)
            ->where('user_id', $user->id)
            ->first();

        // دالة لمساعدة تحديث العداد
        $updateLikeCount = function ($increment) use ($reelId) {
            \DB::table('reels')->where('reels_id', $reelId)->increment('reels_like_count', $increment);
        };

        if ($existing) {
            if ($existing->reaction === $reaction) {
                // حذف التفاعل الحالي
                $existing->delete();
                if ($reaction === 'like') {
                    $updateLikeCount(-1);
                }
                // لو عايز تتعامل مع dislike count تضيف منطق هنا كمان
                return response()->json(['message' => 'Your reaction has been removed.']);
            } else {
                // تغيير التفاعل (مثلاً dislike -> like)
                if ($existing->reaction === 'like' && $reaction === 'dislike') {
                    $updateLikeCount(-1);
                } elseif ($existing->reaction === 'dislike' && $reaction === 'like') {
                    $updateLikeCount(1);
                }
                $existing->reaction = $reaction;
                $existing->save();
                return response()->json(['message' => 'Your reaction has been updated.']);
            }
        } else {
            // إضافة تفاعل جديد
            ReelLikesLog::create([
                'reel_id' => $reelId,
                'user_id' => $user->id,
                'reaction' => $reaction,
            ]);
            if ($reaction === 'like') {
                $updateLikeCount(1);
            }
            // لو عايز تضيف منطق للديسلايك برضه هنا
            return response()->json(['message' => 'Your reaction has been added.']);
        }
    }

    public function getAllReels()
    {
        $baseUrl = url('/');

        $reelsQuery = DB::table('reels')
            ->leftJoin('ads', 'reels.reels_ad_id', '=', 'ads.id')
            ->leftJoin('userauths', 'ads.user_id', '=', 'userauths.id')
            ->select(
                'reels.reels_id as reel_id',
                'reels.reels_ad_id as ad_id',
                'reels.reels_video_url',
                'reels.reels_thumbnail_url',
                'reels.reels_like_count',
                'reels.reels_view_count',
                'reels.reels_share_count',
                'reels.created_at',
                'ads.title as ad_title',
                'ads.main_image as ad_main_image',
                'userauths.id as user_id',
                DB::raw("CONCAT(userauths.first_name, ' ', userauths.last_name) as user_name"),
                'userauths.profile_image'
            );

        if (request()->has('country_id')) {
            $reelsQuery->where('ads.country_id', request('country_id'));
        }

        $reels = $reelsQuery->paginate(10);


        foreach ($reels as $reel) {
            $followersCount = DB::table('followers')
                ->where('following_id', $reel->user_id)
                ->count();

            $createdAt = \Carbon\Carbon::parse($reel->created_at);
            $timeSinceCreated = $createdAt->diffForHumans();

            $likedUsers = DB::table('reel_likes_logs')
                ->join('userauths', 'reel_likes_logs.user_id', '=', 'userauths.id')
                ->where('reel_likes_logs.reel_id', $reel->reel_id)
                ->where('reel_likes_logs.reaction', 'like')
                ->select(
                    'userauths.id as user_id',
                    DB::raw("CONCAT(userauths.first_name, ' ', userauths.last_name) as name"),
                    'userauths.profile_image'
                )
                ->get()
                ->map(function ($user) use ($baseUrl) {
                    $user->profile_image = $user->profile_image
                        ? $baseUrl . '/profile_images/' . ltrim($user->profile_image, '/')
                        : null;
                    return $user;
                });

            $reel->data = [ 
                'reel_id' => $reel->reel_id,  
                'follow_count' => $followersCount,
                'views_count' => $reel->reels_view_count,
                'likes_count' => $reel->reels_like_count,
                'share_count' => $reel->reels_share_count,
                'reel_title' => $reel->ad_title,
                'user' => [
                    'id' => $reel->user_id,
                    'name' => $reel->user_name,
                    'followers_count' => $followersCount,
                    'avatar' => $reel->profile_image
                        ? $baseUrl . '/profile_images/' . ltrim($reel->profile_image, '/')
                        : null,
                ],
                'time_since_created' => $timeSinceCreated,
                'ad' => $reel->ad_id ? [
                    'id' => $reel->ad_id,
                    'title' => $reel->ad_title,
                    'main_image' => $reel->ad_main_image
                        ? $baseUrl . '/' . ltrim($reel->ad_main_image, '/')
                        : null,
                ] : null,
            ];

            $reel->liked_users = $likedUsers;

            unset(
                $reel->reel_id,
                $reel->ad_id,
                $reel->user_id,
                $reel->user_name,
                $reel->profile_image,
                $reel->reels_like_count,
                $reel->reels_view_count,
                $reel->reels_share_count,
                $reel->ad_title,
                $reel->ad_main_image,
                $reel->created_at
            );
        }

        return response()->json($reels);
    }



    public function getReelById(Request $request, $id)
    {
        $user = Auth::user();
        $baseUrl = url('/');

        $reelQuery = DB::table('reels')
            ->leftJoin('ads', 'reels.reels_ad_id', '=', 'ads.id')
            ->leftJoin('userauths', 'ads.user_id', '=', 'userauths.id')
            ->where('reels.reels_id', $id)
            ->select(
                'reels.reels_id as reel_id',
                'reels.reels_ad_id as ad_id',
                'reels.reels_video_url',
                'reels.reels_thumbnail_url',
                'reels.reels_like_count',
                'reels.reels_view_count',
                'reels.reels_share_count',
                'reels.created_at',
                'ads.title as ad_title',
                'ads.main_image as ad_main_image',
                'userauths.id as user_id',
                DB::raw("CONCAT(userauths.first_name, ' ', userauths.last_name) as user_name"),
                'userauths.profile_image'
            );
        if ($request->has('country_id')) {
            $reelQuery->where('ads.country_id', $request->country_id);
        }

        $reel = $reelQuery->first();


        if (!$reel) {
            return response()->json(['message' => 'Reel not found'], 404);
        }

        // عدد المتابعين لصاحب الريل
        $followersCount = DB::table('followers')
            ->where('following_id', $reel->user_id)
            ->count();

        // هل المستخدم الحالي عمل لايك؟
        $isLiked = false;
        if ($user) {
            $isLiked = DB::table('reel_likes_logs')
                ->where('reel_id', $id)
                ->where('user_id', $user->id)
                ->where('reaction', 'like')
                ->exists();
        }

        // المستخدمين اللي عملوا لايك
        $likedUsers = DB::table('reel_likes_logs')
            ->join('userauths', 'reel_likes_logs.user_id', '=', 'userauths.id')
            ->where('reel_likes_logs.reel_id', $id)
            ->where('reel_likes_logs.reaction', 'like')
            ->select(
                'userauths.id as user_id',
                DB::raw("CONCAT(userauths.first_name, ' ', userauths.last_name) as name"),
                'userauths.profile_image'
            )
            ->get()
            ->map(function ($user) use ($baseUrl) {
                $user->profile_image = $user->profile_image
                    ? $baseUrl . '/profile_images/' . ltrim($user->profile_image, '/')
                    : null;
                return $user;
            });

        // حساب الوقت منذ الإنشاء بشكل مبسط (مثلاً بالساعات أو الأيام)
        $createdAt = \Carbon\Carbon::parse($reel->created_at);
        $timeSinceCreated = $createdAt->diffForHumans();

        return response()->json([
            // Get reel by id
            'id' => $reel->reel_id,

            // Video & Thumbnail URLs
            'video_url' => $reel->reels_video_url
                ? $baseUrl . '/' . ltrim($reel->reels_video_url, '/')
                : null,
            'thumbnail_url' => $reel->reels_thumbnail_url
                ? $baseUrl . '/' . ltrim($reel->reels_thumbnail_url, '/')
                : null,

            // Follow count
            'follow_count' => $followersCount,

            // Views count
            'views_count' => $reel->reels_view_count,

            // Like count
            'likes_count' => $reel->reels_like_count,

            // Share count
            'share_count' => $reel->reels_share_count,

            // Reel title
            'reel_title' => $reel->ad_title,

            // User info
            'user' => [
                'id' => $reel->user_id,
                'name' => $reel->user_name,
                'followers_count' => $followersCount,
                'avatar' => $reel->profile_image
                    ? $baseUrl . '/profile_images/' . ltrim($reel->profile_image, '/')
                    : null,
            ],

            // Time count for how much it has been created
            'time_since_created' => $timeSinceCreated,

            // Ad details
            'ad' => $reel->ad_id ? [
                'id' => $reel->ad_id,
                'title' => $reel->ad_title,
                'main_image' => $reel->ad_main_image
                    ? $baseUrl . '/' . ltrim($reel->ad_main_image, '/')
                    : null,
            ] : null,

            // Users who liked
            'liked_users' => $likedUsers,
        ]);
    }



    public function incrementView($reelId)
    {
        $updated = DB::table('reels')->where('reels_id', $reelId)->increment('reels_view_count');
        if ($updated) {
            return response()->json(['message' => 'View count incremented successfully']);
        }
        return response()->json(['message' => 'Reel not found'], 404);
    }

    public function incrementShare($reelId)
    {
        $updated = DB::table('reels')->where('reels_id', $reelId)->increment('reels_share_count');
        if ($updated) {
            return response()->json(['message' => 'Share count incremented successfully']);
        }
        return response()->json(['message' => 'Reel not found'], 404);
    }
}
