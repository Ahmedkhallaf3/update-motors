<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Notification;
use App\Models\Follower;

use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $query = Ad::with([
            'user',
            'subImages',
            'fieldValues.field', // ربط الحقل
            'fieldValues.fieldValue' // ربط قيمة الحقل
        ])->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $ads = $query->get();

        return view('pages.ads.ads-management', compact('ads'));
    }

  public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:pending,approved,rejected',
    ]);

    $ad = Ad::findOrFail($id);

    $ad->status = $request->status;
    $ad->save();

    $countryName = $ad->country ? $ad->country->name_ar ?? $ad->country->name_en : 'بدون دولة / No Country';
    $categoryName = $ad->category ? $ad->category->name_ar ?? $ad->category->name_en : 'بدون فئة / No Category';
    $status = $request->status;

    $messages = [
        'approved' => [
            'ar' => "إعلانك في $categoryName - $countryName تم قبوله!",
            'en' => "Your ad in $categoryName - $countryName has been approved!",
        ],
        'rejected' => [
            'ar' => "إعلانك في $categoryName - $countryName تم رفضه!",
            'en' => "Your ad in $categoryName - $countryName has been rejected!",
        ],
        'pending' => [
            'ar' => "إعلانك في $categoryName - $countryName قيد المراجعة!",
            'en' => "Your ad in $categoryName - $countryName is under review!",
        ],
    ];

    Notification::create([
        'user_id' => $ad->user_id,
        'ad_id' => $ad->id,
        'type' => 'ad_status',
        'message_ar' => $messages[$status]['ar'],
        'message_en' => $messages[$status]['en'],
        'is_read' => false,
    ]);

    if ($status === 'approved') {
        $user = $ad->user; // صاحب الإعلان
        $followers = Follower::where('following_id', $user->id)->pluck('follower_id');

        foreach ($followers as $followerId) {
            Notification::create([
                'user_id' => $followerId,
                'from_user_id' => $user->id,
                'ad_id' => $ad->id,
                'type' => 'new_ad',
                'message_ar' => "{$user->first_name} نشر إعلان جديد في $categoryName - $countryName!",
                'message_en' => "{$user->first_name} posted a new ad in $categoryName - $countryName!",
                'is_read' => false,
            ]);
        }
    }

    return redirect()->back()->with('success', 'تم تحديث حالة الإعلان بنجاح');
}




public function destroy($id)
{
    $ad = Ad::findOrFail($id);

    // حذف الصور الفرعية المرتبطة
    foreach ($ad->subImages as $image) {
        $imagePath = public_path($image->image);
        if (file_exists($imagePath)) {
            unlink($imagePath); // حذف الصورة من السيرفر
        }
        $image->delete(); // حذف السجل من قاعدة البيانات
    }

    // حذف الصورة الرئيسية إذا كانت موجودة
    $mainImagePath = public_path($ad->main_image);
    if (file_exists($mainImagePath)) {
        unlink($mainImagePath);
    }

    // حذف القيم المرتبطة بالحقول
    $ad->fieldValues()->delete();

    // حذف الإعلان
    $ad->delete();

    return redirect()->back()->with('success', 'تم حذف الإعلان بنجاح');
}


}
