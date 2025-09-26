<?php

namespace App\Http\Controllers;

use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VisitorLogController extends Controller
{
 

    public function track(Request $request)
{
    $ip = $request->ip();
    $now = now();

    $countryId = $request->input('country_id');
    $categoryId = $request->input('category_id');

    $responses = [];

    // نبحث عن جلسة مفتوحة لنفس IP
    $existingSession = VisitorLog::where('ip_address', $ip)
        ->whereNull('left_at')
        ->first();

    if ($existingSession) {
        // تحقق هل القيم المرسلة متطابقة مع الجلسة المفتوحة (مع مراعاة null)
        $sameCountry = (is_null($countryId) && is_null($existingSession->country_id)) 
                        || ($existingSession->country_id == $countryId);
        $sameCategory = (is_null($categoryId) && is_null($existingSession->category_id)) 
                        || ($existingSession->category_id == $categoryId);

        if ($sameCountry && $sameCategory) {
            // نفس الجلسة موجودة مفتوحة → لا نفعل شيء جديد
            $responses[] = [
                'message_en' => 'An open visitor session already exists for this IP and location.',
                'message_ar' => 'يوجد جلسة زائر مفتوحة مسبقاً لهذا الـ IP ولنفس المكان.',
            ];
        } else {
            // مختلف الموقع أو التصنيف → نغلق القديمة أولاً
            $existingSession->update(['left_at' => $now]);

            // ثم ننشئ جلسة جديدة بنفس القيم الجديدة فقط إذا لم توجد مسبقاً
            $newSessionExists = VisitorLog::where('ip_address', $ip)
                ->where(function($q) use ($countryId) {
                    if (is_null($countryId)) {
                        $q->whereNull('country_id');
                    } else {
                        $q->where('country_id', $countryId);
                    }
                })
                ->where(function($q) use ($categoryId) {
                    if (is_null($categoryId)) {
                        $q->whereNull('category_id');
                    } else {
                        $q->where('category_id', $categoryId);
                    }
                })
                ->whereNull('left_at')
                ->exists();

            if (!$newSessionExists) {
                VisitorLog::create([
                    'ip_address' => $ip,
                    'country_id' => $countryId,
                    'category_id' => $categoryId,
                    'general_visitor' => true,
                    'visited_at' => $now,
                ]);

                $responses[] = [
                    'message_en' => 'Previous session closed due to location change. New session started.',
                    'message_ar' => 'تم إغلاق الجلسة السابقة بسبب تغيير المكان، وتم بدء جلسة جديدة.',
                ];
            } else {
                $responses[] = [
                    'message_en' => 'Previous session closed. A new session with the same details already exists.',
                    'message_ar' => 'تم إغلاق الجلسة السابقة، ولكن توجد جلسة جديدة بنفس التفاصيل حالياً.',
                ];
            }
        }
    } else {
        // لا توجد جلسة مفتوحة → ننشئ واحدة جديدة فقط إذا لم تكن موجودة
        $newSessionExists = VisitorLog::where('ip_address', $ip)
            ->where(function($q) use ($countryId) {
                if (is_null($countryId)) {
                    $q->whereNull('country_id');
                } else {
                    $q->where('country_id', $countryId);
                }
            })
            ->where(function($q) use ($categoryId) {
                if (is_null($categoryId)) {
                    $q->whereNull('category_id');
                } else {
                    $q->where('category_id', $categoryId);
                }
            })
            ->whereNull('left_at')
            ->exists();

        if (!$newSessionExists) {
            VisitorLog::create([
                'ip_address' => $ip,
                'country_id' => $countryId,
                'category_id' => $categoryId,
                'general_visitor' => true,
                'visited_at' => $now,
            ]);

            $responses[] = [
                'message_en' => 'New visitor session started.',
                'message_ar' => 'تم بدء جلسة زائر جديدة.',
            ];
        } else {
            $responses[] = [
                'message_en' => 'An open visitor session already exists for this IP and location.',
                'message_ar' => 'يوجد جلسة زائر مفتوحة مسبقاً لهذا الـ IP ولنفس المكان.',
            ];
        }
    }

    // إحصائيات الزوار
    $statisticsData = app(\App\Http\Controllers\VisitorLogController::class)->statistics($request)->getData(true);

    return response()->json([
        'messages' => $responses,
        'statistics' => $statisticsData,
    ]);
}


 
    public function statistics(Request $request)
    {
        $countryId = $request->input('country_id');
        $categoryId = $request->input('category_id');

        if ($categoryId) {
            // إحصائيات الزيارات حسب category_id فقط
            $liveCount = \App\Models\VisitorLog::where('category_id', $categoryId)
                ->whereNull('left_at')
                ->count();

            $endedCount = \App\Models\VisitorLog::where('category_id', $categoryId)
                ->whereNotNull('left_at')
                ->count();

            return response()->json([
                'category_id' => (int)$categoryId,
                'live_visitors' => $liveCount,
                'ended_visitors' => $endedCount,
                'total_visitors' => $liveCount + $endedCount,
            ]);
        }

        if ($countryId) {
            // إحصائيات الزيارات حسب country_id فقط
            $liveCount = \App\Models\VisitorLog::where('country_id', $countryId)
                ->whereNull('left_at')
                ->count();

            $endedCount = \App\Models\VisitorLog::where('country_id', $countryId)
                ->whereNotNull('left_at')
                ->count();

            return response()->json([
                'country_id' => (int)$countryId,
                'live_visitors' => $liveCount,
                'ended_visitors' => $endedCount,
                'total_visitors' => $liveCount + $endedCount,
            ]);
        }

        // لو ما فيش فلتر، احسب كل الإحصائيات بدون فلتر

        $total = \App\Models\VisitorLog::count();

        $generalVisitors = \App\Models\VisitorLog::where('general_visitor', true)->count();

        $liveGeneralVisitors = \App\Models\VisitorLog::where('general_visitor', true)
            ->whereNull('left_at')
            ->count();

        $endedGeneralVisitors = \App\Models\VisitorLog::where('general_visitor', true)
            ->whereNotNull('left_at')
            ->count();

        $leftVisitors = \App\Models\VisitorLog::whereNotNull('left_at')->count();
        $liveVisitors = \App\Models\VisitorLog::whereNull('left_at')->count();

        $liveCategoryCounts = \App\Models\VisitorLog::whereNull('left_at')
            ->whereNotNull('category_id')
            ->select('category_id', \DB::raw('COUNT(*) as visitors'))
            ->groupBy('category_id')
            ->get();

        $endedCategoryCounts = \App\Models\VisitorLog::whereNotNull('left_at')
            ->whereNotNull('category_id')
            ->select('category_id', \DB::raw('COUNT(*) as visitors'))
            ->groupBy('category_id')
            ->get();

        $liveCountryCounts = \App\Models\VisitorLog::whereNull('left_at')
            ->whereNotNull('country_id')
            ->select('country_id', \DB::raw('COUNT(*) as visitors'))
            ->groupBy('country_id')
            ->get();

        $endedCountryCounts = \App\Models\VisitorLog::whereNotNull('left_at')
            ->whereNotNull('country_id')
            ->select('country_id', \DB::raw('COUNT(*) as visitors'))
            ->groupBy('country_id')
            ->get();

        return response()->json([
            'total' => $total,
            'general_visitors' => $generalVisitors,

            // live first
            'live_general_visitors' => $liveGeneralVisitors,
            'live_visitors' => $liveVisitors,
            'live_categories' => $liveCategoryCounts,
            'live_countries' => $liveCountryCounts,

            // ended next
            'ended_general_visitors' => $endedGeneralVisitors,
            'left_visitors' => $leftVisitors,
            'ended_categories' => $endedCategoryCounts,
            'ended_countries' => $endedCountryCounts,
        ]);
    }



    public function trackExit(Request $request)
    {
        $ip = $request->ip();
        $now = now()->format('Y-m-d H:i:s'); // الصيغة المتوافقة مع MySQL

        $visitorLog = VisitorLog::where('ip_address', $ip)
            ->whereNull('left_at')
            ->orderByDesc('visited_at')
            ->first();

        if ($visitorLog) {
            $visitorLog->update(['left_at' => $now]);

            return response()->json([
                'message_en' => 'Visitor exit tracked successfully.',
                'message_ar' => 'تم تسجيل خروج الزائر بنجاح.',
                'left_at' => $now,
            ]);
        } else {
            return response()->json([
                'message_en' => 'No open visitor session found.',
                'message_ar' => 'لم يتم العثور على جلسة مفتوحة للزائر.',
            ], 404);
        }
    }
}
