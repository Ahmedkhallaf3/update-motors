<?php

namespace App\Http\Controllers\Api;

use App\Models\Ad;
use App\Models\Userauth;
use App\Models\Category;
use App\Models\country;
use App\Models\city;
use App\Models\CategoryFieldValue;
use App\Models\Follower;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use App\Models\AdImage;
use App\Models\AdView;
use App\Models\Notification;
use App\Models\CategoryField;
use Illuminate\Support\Facades\Storage;
use App\Models\Favorite;
use App\Models\AdFieldValue;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\AdFeature;
use Carbon\Carbon;
use App\Models\ReelLikesLog;
use App\Models\User;
use App\Models\Reel;


class AdController extends Controller
{
    public function store(Request $request)
    {
        // استرجاع المستخدم من التوكن
        $token = request()->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token absent'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // التحقق من البيانات المطلوبة
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'country_id' => 'required|exists:countries,id',
            //'city_id' => 'required|exists:cities,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string',
            'price' => 'required|numeric',
            'phone_number' => 'nullable|string|max:20',
            'kilometer' => 'nullable|string',
            'main_image' => 'required|image',
            'sub_images.*' => 'image',
            'fields' => 'required|array',
            'car_model' => 'nullable|string|max:255',
            'fields.*.category_field_id' => 'required|exists:category_fields,id',
            'fields.*.category_field_value_id' => 'required',
            //'reel_video' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:51200',
            'reel_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reelVideoUrl = null;
        if ($request->hasFile('reel_video')) {
            $reelVideo = $request->file('reel_video');

            // اسم فريد مع timestamp ورقم عشوائي
            $timestamp = time();
            $random = rand(1000, 9999);
            // $originalName = pathinfo($reelVideo->getClientOriginalName(), PATHINFO_FILENAME);
            // $finalBaseName = $originalName . '_' . $timestamp . '_' . $random;

            $originalName = pathinfo($reelVideo->getClientOriginalName(), PATHINFO_FILENAME);

            // تنظيف الاسم: حذف الرموز الخاصة، واستبدال المسافات والشرطات المتعددة بواحدة _
            $cleanedName = preg_replace('/[^a-zA-Z0-9\s_-]/', '', $originalName);  // إزالة كل الرموز غير المسموح بها
            $cleanedName = preg_replace('/[\s_-]+/', '_', $cleanedName);            // استبدال المسافات/شرطات بواحدة _
            $cleanedName = trim($cleanedName, '_');                                 // إزالة الشرطات من البداية والنهاية

            // إذا كان الاسم فارغًا بعد التنظيف (مثلاً كان اسمه رموز فقط)، نستخدم اسمًا افتراضيًا
            if ($cleanedName === '') {
                $cleanedName = 'video';
            }

            // توليد الاسم النهائي بدون امتداد (كما تستخدمه في $finalBaseName)
            $finalBaseName = $cleanedName . '_' . $timestamp . '_' . $random;

            $reelsDir = public_path('reels');
            if (!file_exists($reelsDir)) {
                mkdir($reelsDir, 0755, true);
            }

            // مسارات الفيديو
            $originalPath = $reelsDir . DIRECTORY_SEPARATOR . $finalBaseName . '_original.mp4';
            $tempPath = $reelsDir . DIRECTORY_SEPARATOR . $finalBaseName . '_temp.mp4';
            $compressedName = $finalBaseName . '_compressed.mp4';
            $compressedPath = $reelsDir . DIRECTORY_SEPARATOR . $compressedName;

            // حفظ الفيديو الأصلي
            $reelVideo->move($reelsDir, $finalBaseName . '_original.mp4');

            $ffmpegPath = public_path('../ffmpeg-7.0.2-amd64-static/ffmpeg');

            // ضغط آمن: خفض الدقة إلى 75%، CRF=28 (جودة معقولة)، صوت 64k (تقليل جودة الصوت)
            $cmd1 = escapeshellcmd($ffmpegPath) . ' -i ' . escapeshellarg($originalPath)
                . ' -vf "scale=iw*0.75:ih*0.75" -c:v libx264 -preset fast -crf 28 -c:a aac -b:a 64k '
                . escapeshellarg($tempPath);
            exec($cmd1, $out1, $ret1);

            // تحويل لتنسيق Apple مع faststart (تسريع بدء التشغيل)
            $cmd2 = escapeshellcmd($ffmpegPath) . ' -i ' . escapeshellarg($tempPath)
                . ' -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 64k -ar 48000 -movflags +faststart '
                . escapeshellarg($compressedPath);
            exec($cmd2, $out2, $ret2);

            // حذف الملفات المؤقتة
            if (file_exists($tempPath)) unlink($tempPath);
            if (file_exists($originalPath)) unlink($originalPath);

            // إنشاء صورة مصغرة (thumbnail) من الفيديو المضغوط عند الثانية 1
            $thumbnailName = $finalBaseName . '_thumbnail.jpg';
            $thumbnailPath = $reelsDir . DIRECTORY_SEPARATOR . $thumbnailName;
            $cmdThumb = escapeshellcmd($ffmpegPath)
                . ' -ss 00:00:01 -i ' . escapeshellarg($compressedPath)
                . ' -frames:v 1 -q:v 2 ' . escapeshellarg($thumbnailPath)
                . ' -y'; // -y لتجاوز تأكيد الكتابة
            exec($cmdThumb, $outThumb, $retThumb);

            $reelVideoUrl = null;
            $thumbnailUrl = null;

            if ($ret2 === 0 && file_exists($compressedPath)) {
                $reelVideoUrl = 'reels/' . $compressedName;
            }

            if ($retThumb === 0 && file_exists($thumbnailPath)) {
                $thumbnailUrl = 'reels/' . $thumbnailName;
            }
        }






        // if ($request->hasFile('reel_video')) {
        //     $reelVideo = $request->file('reel_video');
        //     $reelVideoName = time() . '_' . $reelVideo->getClientOriginalName();

        //     $reelsDir = public_path('reels');

        //     // إنشاء مجلد reels لو مش موجود
        //     if (!file_exists($reelsDir)) {
        //         mkdir($reelsDir, 0755, true);
        //     }

        //     // نقل الملف للمجلد
        //     $reelVideo->move($reelsDir, $reelVideoName);
        //     $reelVideoUrl = 'reels/' . $reelVideoName;

        //     $inputVideo = $reelsDir . DIRECTORY_SEPARATOR . $reelVideoName;
        //     $thumbnailPath = $reelsDir . DIRECTORY_SEPARATOR . pathinfo($reelVideoName, PATHINFO_FILENAME) . '.jpg';

        //     $ffmpegPath = public_path('../ffmpeg-7.0.2-amd64-static/ffmpeg');

        //     $cmd = escapeshellcmd($ffmpegPath)
        //         . ' -ss 00:00:01 -i ' . escapeshellarg($inputVideo)
        //         . ' -frames:v 1 -q:v 2 ' . escapeshellarg($thumbnailPath)
        //         . ' 2>&1';

        //     exec($cmd, $output, $returnVar);

        //     if ($returnVar === 0 && file_exists($thumbnailPath)) {
        //         // echo "Thumbnail created successfully: " . $reelVideoUrl . " → "
        //         //     . pathinfo($reelVideoName, PATHINFO_FILENAME) . '.jpg';
        //         $thumbnailUrl = 'reels/' . pathinfo($reelVideoName, PATHINFO_FILENAME) . '.jpg';
        //     } else {
        //         $thumbnailUrl = null;
        //         echo "Failed to create thumbnail.";
        //         echo "<pre>" . implode("\n", $output) . "</pre>";
        //     }
        // }

        $reelThumbnailUrl = null;

        if ($request->hasFile('reel_thumbnail')) {
            $thumbnail = $request->file('reel_thumbnail');
            $thumbnailName = 'thumb_' . time() . '_' . $thumbnail->getClientOriginalName();
            $thumbnailPath = public_path('reels/' . $thumbnailName);

            // تأكد المجلد موجود
            if (!file_exists(public_path('reels'))) {
                mkdir(public_path('reels'), 0755, true);
            }

            $thumbnail->move(public_path('reels'), $thumbnailName);
            $reelThumbnailUrl = 'reels/' . $thumbnailName;
        }


        // افتراضياً نستخدم ID المستخدم الحالي
        $adUserId = $user->id;

        // إذا تم تمرير user_id في الطلب
        if ($request->has('user_id')) {

            // فقط الإدمن يقدر يحدد user_id
            if ($user->role !== 'admin') {
                return response()->json([
                    'message_ar' => 'فقط المدراء يمكنهم تعيين الإعلانات لمستخدمين آخرين',
                    'message_en' => 'Only admins can assign ads to other users'
                ], 403);
            }

            // التحقق من أن user_id موجود
            $requestedUser = Userauth::find($request->user_id);
            if (!$requestedUser) {
                return response()->json([
                    'message_ar' => 'المستخدم المحدد غير موجود',
                    'message_en' => 'The provided user_id is invalid'
                ], 422);
            }

            // نستخدم الـ user_id المحدد من قبل الإدمن
            $adUserId = $requestedUser->id;
        }


        // حفظ الصورة الرئيسية مع العلامة المائية وتغيير الأبعاد
        $mainImage = $request->file('main_image');
        $mainImageName = time() . '_' . pathinfo($mainImage->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
        $mainImagePath = public_path('ads/' . $mainImageName);

        $image = Image::make($mainImage->getRealPath());
        $image->resize(2000, 1300, function ($constraint) {
            $constraint->aspectRatio(); // الحفاظ على نسبة العرض إلى الارتفاع
            // لا نستخدم $constraint->upsize() للسماح بالتكبير
        });
        $image->insert(public_path('watermark.png'), 'center'); // إضافة العلامة المائية
        $image->encode('webp', 85)->save($mainImagePath);


        $categoryName = Category::find($request->category_id)->name_ar ?? '';
        $countryName = Country::find($request->country_id)->name_ar ?? '';

        $encategoryName = Category::find($request->category_id)->name_en ?? '';
        $encountryName = Country::find($request->country_id)->name_en ?? '';

        // إنشاء الإعلان
        $ad = Ad::create([
            'user_id' => $adUserId,
            'category_id' => $request->category_id,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'title' => $request->title,
            'description' => $request->description,
            'address' => $request->address,
            'kilometer' => $request->kilometer,
            'price' => $request->price,
            'phone_number' => $request->phone_number,
            'car_model' => $request->car_model,
            'status' => 'pending',
            'main_image' => 'ads/' . $mainImageName,
        ]);
        $reel = null;
        if ($reelVideoUrl) {
            $reel = \App\Models\Reel::create([
                'reels_ad_id' => $ad->id,
                'reels_video_url' => $reelVideoUrl,
                'reels_like_count' => 0,
                'reels_thumbnail_url' => $thumbnailUrl ?? null, // لو ضفت thumbnail لاحقًا
            ]);
        }



        // إنشاء إشعار للمستخدم بأن الإعلان قيد المراجعة
        Notification::create([
            'user_id' => $adUserId,
            'from_user_id' => null,
            'type' => 'ad_status',
            'message_ar' => "إعلانك في قسم {$categoryName} ب{$countryName} قيد المراجعة!",
            'message_en' => "Your ad in {$encategoryName} at {$encountryName} is under review!",
            'ad_id' => $ad->id,
            'is_read' => false,
        ]);

        // إذا كانت حالة الإعلان "approved" نرسل إشعار للمتابعين
        if ($ad->status === 'approved') {
            $followers = Follower::where('following_id', $user->id)->pluck('follower_id');
            foreach ($followers as $followerId) {
                Notification::create([
                    'user_id' => $followerId,
                    'from_user_id' => $user->id,
                    'ad_id' => $ad->id,
                    'type' => 'new_ad',
                    'message_ar' => "{$user->first_name} نشر إعلان جديد في {$categoryName} ب{$countryName}!",
                    'message_en' => "{$user->first_name} posted a new ad in {$encategoryName} at {$encountryName}!",
                ]);
            }
        }


        if ($request->hasFile('sub_images')) {
            $subImages = $request->file('sub_images');

            if (is_array($subImages)) {
                foreach ($subImages as $subImage) {
                    $subImageName = 'sub_' . time() . '_' . pathinfo($subImage->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
                    $subImagePath = public_path('ads/' . $subImageName);

                    $subImg = Image::make($subImage->getRealPath());
                    $subImg->resize(2000, 1300, function ($constraint) {
                        $constraint->aspectRatio(); // الحفاظ على نسبة العرض إلى الارتفاع
                        // لا نستخدم $constraint->upsize() للسماح بالتكبير
                    });
                    $subImg->insert(public_path('watermark.png'), 'center'); // إضافة العلامة المائية
                    $subImg->encode('webp', 85)->save($subImagePath);

                    // حفظ الصورة في قاعدة البيانات
                    AdImage::create([
                        'ad_id' => $ad->id,
                        'image' => 'ads/' . $subImageName,
                    ]);
                }
            }
        }



        foreach ($request->fields as $field) {
            $categoryField = CategoryField::find($field['category_field_id']);

            // تأكد أن الحقل موجود
            if (!$categoryField) {
                continue;
            }

            // لو الحقل من نوع select أو dropdown → نتوقع ID موجود
            if ($categoryField->input_type === 'select') {
                $categoryFieldValue = CategoryFieldValue::find($field['category_field_value_id']);

                if (!$categoryFieldValue) {
                    return response()->json([
                        'message' => 'القيمة المحددة غير موجودة لهذا الحقل',
                        'field_id' => $field['category_field_id']
                    ], 422);
                }
            } else {
                // حقل نصي → خزّن القيمة كـ نص حتى لو كانت رقم
                $categoryFieldValue = CategoryFieldValue::firstOrCreate([
                    'category_field_id' => $field['category_field_id'],
                    'value_ar' => $field['category_field_value_id'],
                    'value_en' => $field['category_field_value_id'],
                    'field_type' => 'text',
                ]);
            }

            AdFieldValue::create([
                'ad_id' => $ad->id,
                'category_field_id' => $field['category_field_id'],
                'category_field_value_id' => $categoryFieldValue->id,
            ]);
        }




        // إضافة المميزات الخاصة بالإعلان إذا كانت موجودة
        if ($request->has('car_options') && !empty($request->car_options)) {
            // تحويل النص المفصول بفواصل إلى مصفوفة، وتأكد من إزالة أي مسافات أو أقواس
            $featureIds = explode(',', $request->car_options);

            // استعراض كل ID في المصفوفة
            foreach ($featureIds as $featureId) {
                // إزالة أي مسافات بيضاء أو أقواس حول الـ ID
                $featureId = trim($featureId, " \t\n\r\0\x0B[]");

                // التحقق من أن الـ ID هو قيمة صحيحة (عدد صحيح)
                if (is_numeric($featureId)) {
                    // إضافة الميزة للإعلان باستخدام الـ ID
                    AdFeature::create([
                        'car_ad_id' => $ad->id,
                        'feature_id' => $featureId,
                    ]);
                }
            }
        }


        // بعد إنشاء الإعلان
        $admins = Userauth::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'from_user_id' => $user->id,  // المستخدم اللي أنشأ الإعلان
                'ad_id' => $ad->id,
                'type' => 'admin_new_ad', // نوع جديد مخصص للإدمنز
                'message_ar' => "تم إنشاء إعلان جديد بعنوان: " . $ad->title . " في قسم {$categoryName} ب{$countryName}",
                'message_en' => "A new ad has been created titled: " . $ad->title . " in {$encategoryName} at {$encountryName}",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Ad created successfully',
            'ad' => $ad,
            'reel' => $reel
        ], 201);

        // return response()->json(['message' => 'Ad created successfully', 'ad' => $ad], 201);
    }
    public function update(Request $request, $id)
    {
        $token = request()->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token absent'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('reel_video')) {
            $request->request->remove('reel_video');
        }



        $ad = Ad::where('id', $id)->where('user_id', $user->id)->first();
        if (!$ad) {
            return response()->json([
                'message' => 'الإعلان غير موجود أو لا يخص هذا المستخدم. | Ad not found or does not belong to the authenticated user.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'country_id' => 'sometimes|exists:countries,id',
            // 'city_id' => 'sometimes|exists:cities,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'phone_number' => 'nullable|string|max:20',
            'kilometer' => 'nullable|string',
            'main_image' => 'nullable|image',
            'sub_images.*' => 'image',
            'car_model' => 'nullable|string|max:255', // Added validation for car_model
            'fields' => 'nullable|array',
            'fields.*.category_field_id' => 'required_with:fields|exists:category_fields,id',
            'fields.*.category_field_value_id' => 'required',
            'reel_video' => 'sometimes|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime|max:51200',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update ad with the car_model field included
        $ad->update($request->only([
            'category_id',
            'country_id',
            'city_id',
            'title',
            'description',
            'address',
            'kilometer',
            'price',
            'phone_number',
            'car_model',
        ]));

        // إذا قام المستخدم برفع صورة رئيسية جديدة
        if ($request->hasFile('main_image')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($ad->main_image && file_exists(public_path($ad->main_image))) {
                unlink(public_path($ad->main_image));
            }

            // رفع الصورة الجديدة
            $mainImage = $request->file('main_image');
            $mainImageName = time() . '_' . pathinfo($mainImage->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
            $mainImagePath = public_path('ads/' . $mainImageName);

            $image = Image::make($mainImage->getRealPath());
            $image->resize(2000, 1300, function ($constraint) {
                $constraint->aspectRatio(); // الحفاظ على نسبة العرض إلى الارتفاع
            });
            $image->insert(public_path('watermark.png'), 'center'); // إضافة العلامة المائية
            $image->encode('webp', 85)->save($mainImagePath);

            $ad->status = 'pending';
            $ad->main_image = 'ads/' . $mainImageName;
            $ad->save();
        }

        // إذا قام المستخدم برفع صور فرعية جديدة
        if ($request->hasFile('sub_images')) {
            // حذف الصور الفرعية القديمة
            foreach ($ad->subImages as $image) {
                if (file_exists(public_path($image->image))) {
                    unlink(public_path($image->image));
                }
                $image->delete();
            }

            // رفع الصور الفرعية الجديدة
            foreach ($request->file('sub_images') as $subImage) {
                $subImageName = 'sub_' . time() . '_' . pathinfo($subImage->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
                $subImagePath = public_path('ads/' . $subImageName);

                $subImg = Image::make($subImage->getRealPath());
                $subImg->resize(2000, 1300, function ($constraint) {
                    $constraint->aspectRatio(); // الحفاظ على نسبة العرض إلى الارتفاع
                });
                $subImg->insert(public_path('watermark.png'), 'center'); // إضافة العلامة المائية
                $subImg->encode('webp', 85)->save($subImagePath);

                AdImage::create([
                    'ad_id' => $ad->id,
                    'image' => 'ads/' . $subImageName,
                ]);
            }
        }

        // === تحديث فيديو الريلز ===
        $newReel = null;
        if ($request->hasFile('reel_video')) {
            $reelVideo = $request->file('reel_video');
            $videoName = time() . '_' . $reelVideo->getClientOriginalName();
            $videoPath = public_path('reels/' . $videoName);
            $reelVideo->move(public_path('reels'), $videoName);

            // حذف السجل القديم إن وجد
            $existingReel = DB::table('reels')->where('reels_ad_id', $ad->id)->first();
            if ($existingReel) {
                if ($existingReel->reels_video_url && file_exists(public_path($existingReel->reels_video_url))) {
                    @unlink(public_path($existingReel->reels_video_url));
                }
                if ($existingReel->reels_thumbnail_url && file_exists(public_path($existingReel->reels_thumbnail_url))) {
                    @unlink(public_path($existingReel->reels_thumbnail_url));
                }

                DB::table('reels')->where('reels_ad_id', $ad->id)->delete();
            }

            // توليد صورة الـ thumbnail من الفيديو باستخدام ffmpeg
            $thumbnailName = pathinfo($videoName, PATHINFO_FILENAME) . '.jpg';
            $thumbnailPath = public_path('reels/' . $thumbnailName);
            $ffmpegPath = public_path('../ffmpeg-7.0.2-amd64-static/ffmpeg'); // تأكد المسار صحيح
            $command = "$ffmpegPath -i " . escapeshellarg($videoPath) . " -ss 00:00:01 -vframes 1 " . escapeshellarg($thumbnailPath);
            shell_exec($command);

            $likeCount = $existingReel->reels_like_count ?? 0;

            // إدخال سجل جديد للريلز مع بيانات الفيديو والصورة واللايكات
            DB::table('reels')->insert([
                'reels_ad_id' => $ad->id,
                'reels_video_url' => 'reels/' . $videoName,
                'reels_thumbnail_url' => 'reels/' . $thumbnailName,
                'reels_like_count' => $likeCount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // جلب السجل الجديد للريلز لإرجاعه في الرد
            $newReel = DB::table('reels')->where('reels_ad_id', $ad->id)->first();
        }




        // إذا كانت car_options موجودة في الطلب وليست فارغة
        if ($request->has('car_options') && !empty($request->car_options)) {
            // تحويل النص المفصول بفواصل إلى مصفوفة
            $featureIds = explode(',', $request->car_options);

            // حذف المميزات القديمة المرتبطة بالإعلان
            DB::table('car_ad_features')->where('car_ad_id', $ad->id)->delete();

            // استعراض كل feature_id في المصفوفة
            foreach ($featureIds as $featureId) {
                // إزالة أي مسافات بيضاء أو أقواس حول الـ ID
                $featureId = trim($featureId, " \t\n\r\0\x0B[]");

                // التحقق من أن الـ ID هو قيمة صحيحة (عدد صحيح)
                if (is_numeric($featureId)) {
                    // إضافة الميزة للإعلان باستخدام الـ feature_id
                    DB::table('car_ad_features')->insert([
                        'car_ad_id' => $ad->id,
                        'feature_id' => $featureId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if ($request->has('fields')) {
            AdFieldValue::where('ad_id', $ad->id)->delete();

            foreach ($request->fields as $field) {
                $categoryField = CategoryField::find($field['category_field_id']);

                if (!$categoryField) {
                    continue; // إذا الحقل غير موجود، تجاهله
                }

                if ($categoryField->input_type === 'select') {
                    $categoryFieldValue = CategoryFieldValue::find($field['category_field_value_id']);

                    if (!$categoryFieldValue) {
                        return response()->json([
                            'message' => 'القيمة المحددة غير موجودة لهذا الحقل',
                            'field_id' => $field['category_field_id']
                        ], 422);
                    }
                } else {
                    $categoryFieldValue = CategoryFieldValue::firstOrCreate([
                        'category_field_id' => $field['category_field_id'],
                        'value_ar' => $field['category_field_value_id'],
                        'value_en' => $field['category_field_value_id'],
                        'field_type' => 'text',
                    ]);
                }

                AdFieldValue::create([
                    'ad_id' => $ad->id,
                    'category_field_id' => $field['category_field_id'],
                    'category_field_value_id' => $categoryFieldValue->id,
                ]);
            }
        }

        $categoryName = Category::find($request->category_id)?->name_ar ?? '';
        $countryName = Country::find($request->country_id)?->name_ar ?? '';

        $encategoryName = Category::find($request->category_id)?->name_en ?? '';
        $encountryName = Country::find($request->country_id)?->name_en ?? '';

        if ($ad->status === 'pending') {
            $admins = Userauth::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'from_user_id' => $user->id,
                    'type' => 'ad_review',
                    'message_ar' => "يوجد إعلان جديد في قسم '{$categoryName}' من دولة '{$countryName}' قيد المراجعة من المستخدم {$user->first_name}",
                    'message_en' => "A new ad in category '{$encategoryName}' from country '{$encountryName}' is pending review from user {$user->first_name}",
                    'ad_id' => $ad->id,
                    'is_read' => false,
                ]);
            }


            // إشعار المستخدم نفسه
            Notification::create([
                'user_id' => $user->id,
                'from_user_id' => null,
                'type' => 'ad_status',
                'message_ar' => "إعلانك في قسم '{$categoryName}' بدولة '{$countryName}' قيد المراجعة!",
                'message_en' => "Your ad in category '{$encategoryName}' from country '{$encountryName}' is under review!",
                'ad_id' => $ad->id,
                'is_read' => false,
            ]);
        }

        $responseData = [
            'message' => 'Ad updated successfully',
            'ad' => $ad,
            'reel' => $newReel ?: null,
        ];
        return response()->json($responseData, 200);
    }
    public function destroyadmin($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired | انتهت صلاحية التوكن'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token | التوكن غير صالح'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token not provided | لم يتم توفير التوكن'], 401);
        }

        // التحقق إن المستخدم أدمن
        // if ($user->role !== 'admin') {
        //     return response()->json(['message' => 'Access denied: admin only | الوصول مرفوض: الأدمن فقط'], 403);
        // }

        // البحث عن الإعلان
        $ad = Ad::find($id);
        if (!$ad) {
            return response()->json(['message' => 'Ad not found | الإعلان غير موجود'], 404);
        }

        // // حذف الصور الفرعية
        // AdImage::where('ad_id', $ad->id)->delete();

        // // حذف القيم المرتبطة بالحقول
        // AdFieldValue::where('ad_id', $ad->id)->delete();

        // // حذف الإعلان
        // $ad->delete();

        // بدل الحذف النهائي، نجعل الإعلان inactive
        $ad->status = 'inactive';
        $ad->save();

        return response()->json(['message' => 'Ad set to inactive successfully | تم تحويل الإعلان إلى inactive'], 200);
    }
    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Retrieve ads for the authenticated user, ordered by `created_at` (newest first)
        $ads = Ad::with(['subImages', 'fieldValues', 'views'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $ads->transform(function ($ad) {
            // Calculate unique views for the ad
            $ad->views_count = $ad->views()->distinct('user_id')->count();

            // Format main image as a direct URL
            $ad->main_image = $ad->main_image ? url($ad->main_image) : null;

            // Format sub-images as direct URLs
            $ad->subImages->transform(fn($image) => ['image' => url($image->image)]);

            // The Decoder Ring — معالجة تفاصيل الـ details بطريقة منظمة
            $adArray['details'] = $ad->fieldValues->map(function ($fieldValue) {
                $arVal = $fieldValue->fieldValue?->value_ar;
                $enVal = $fieldValue->fieldValue?->value_en;

                if ($fieldValue->fieldValue?->field_type === 'text' && is_numeric($arVal)) {
                    $textValue = \DB::table('category_field_values')->find($arVal);
                    if ($textValue && $textValue->category_field_id == $fieldValue->category_field_id) {
                        $arVal = $textValue->value_ar;
                        $enVal = $textValue->value_en;
                    }
                }

                return [
                    'field_id' => $fieldValue->category_field_id,
                    'field_name' => [
                        'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                        'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                    ],
                    'field_value' => [
                        'id' => $fieldValue->category_field_value_id,
                        'ar' => $arVal ?? 'غير معروف',
                        'en' => $enVal ?? 'Unknown',
                    ],
                ];
            });

            return [
                'id' => $ad->id,
                'user_id' => $ad->user_id,
                'title' => $ad->title,
                'description' => $ad->description,
                'address' => $ad->address,
                'price' => $ad->price,
                'phone_number' => $ad->phone_number,
                'kilometer' => $ad->kilometer,
                'status' => $ad->status,
                'main_image' => $ad->main_image,
                'sub_images' => $ad->subImages,
                'details' => $adArray['details'], // استخدم التفاصيل من The Decoder Ring
                'views_count' => $ad->views_count,
            ];
        });

        return response()->json(['ads' => $ads]);
    }
    public function destroy($id)
    {
        // استرجاع المستخدم من التوكن
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // البحث عن الإعلان
        $ad = Ad::with('subImages')->where('id', $id)->where('user_id', $user->id)->first();

        if (!$ad) {
            return response()->json(['message' => 'Ad not found or unauthorized'], 404);
        }

        // // حذف الصورة الرئيسية من السيرفر
        // if ($ad->main_image && file_exists(public_path($ad->main_image))) {
        //     unlink(public_path($ad->main_image));
        // }

        // // حذف الصور الفرعية من السيرفر
        // foreach ($ad->subImages as $image) {
        //     if (file_exists(public_path($image->image))) {
        //         unlink(public_path($image->image));
        //     }
        //     $image->delete();
        // }

        // // حذف الحقول المرتبطة
        // AdFieldValue::where('ad_id', $ad->id)->delete();
        // Reel::where('reels_ad_id', $ad->id)->delete();

        // // حذف الإعلان
        // $ad->delete();

        // بدل الحذف النهائي، نجعل الإعلان inactive
        $ad->status = 'inactive';
        $ad->save();

        return response()->json(['message' => 'Ad set to inactive successfully'], 200);
    }
    function getRealFieldValueIds($targetValueId, $fieldId)
    {
        $matchedIds = [];

        $allValues = \App\Models\CategoryFieldValue::where('category_field_id', $fieldId)->get();

        foreach ($allValues as $value) {
            $current = $value;
            $depth = 0;
            $maxDepth = 10;

            while (is_numeric($current->value_ar) && $depth < $maxDepth) {
                $next = $allValues->firstWhere('id', $current->value_ar);
                if (!$next) break;
                $current = $next;
                $depth++;
            }

            if ($current->id == $targetValueId || $current->value_ar == $targetValueId) {
                $matchedIds[] = $value->id;
            }
        }

        return $matchedIds;
    }
    public function indexadsusers(Request $request)
    {

        $query = Ad::with(['subImages', 'fieldValues.field', 'fieldValues.fieldValue', 'user'])
            ->withCount('views')
            ->orderBy('created_at', 'desc');

        // ✅ تحقق من الفلاتر المدخلة
        $validFilters = [
            'category_id' => fn() => Category::where('id', $request->category_id)->exists(),
            'country_id'  => fn() => Country::where('id', $request->country_id)->exists(),
            'city_id'     => fn() => City::where('id', $request->city_id)->exists(),
            'status'      => fn() => in_array($request->status, ['pending', 'approved', 'rejected']),
        ];

        foreach ($validFilters as $key => $check) {
            if ($request->has($key) && !$check()) {
                return response()->json(['ads' => [], 'pagination' => []], 200);
            }
        }

        // ✅ تطبيق الفلاتر الديناميكية
        $filterMap = [
            'category_id'   => '=',
            'country_id'    => '=',
            'city_id'       => '=',
            'status'        => '=',
            'min_price'     => '>=',
            'max_price'     => '<=',
            'min_kilometer' => '>=',
            'max_kilometer' => '<=',
        ];


        // ✅ تطبيق الفلاتر على الاستعلام
        foreach ($filterMap as $field => $operator) {
            if ($request->has($field)) {
                $column = str_replace(['min_', 'max_'], '', $field);
                $value = $request->$field;

                // $query->where(function ($q) use ($column, $operator, $value) {
                //     $q->where($column, $operator, $value)
                //         ->orWhereNull($column);
                // });

                $query->where(function ($q) use ($column, $operator, $value) {
                    $q->where($column, $operator, $value);

                    // فقط أضف orWhereNull إذا الحقل ليس kilometer
                    if (!in_array($column, ['kilometer'])) {
                        $q->orWhereNull($column);
                    }
                });
            }
        }
        if ($request->has('car_model')) {
            $query->where('car_model', $request->car_model);
        }



        // ✅ فلترة حسب الحقول المخصصة
        if ($request->has('fields')) {
            foreach ($request->fields as $fieldId => $valueId) {
                $isValidField = CategoryField::where('id', $fieldId)->exists();
                $isValidValue = CategoryFieldValue::where('id', $valueId)->exists();

                if (!$isValidField || !$isValidValue) {
                    return response()->json(['ads' => [], 'pagination' => []], 200);
                }

                $matchedValueIds = $this->getRealFieldValueIds($valueId, $fieldId);

                $query->where(function ($q) use ($fieldId, $matchedValueIds) {
                    $q->whereHas('fieldValues', function ($sub) use ($fieldId, $matchedValueIds) {
                        $sub->where('category_field_id', $fieldId)
                            ->whereIn('category_field_value_id', $matchedValueIds);
                    })
                        ->orWhereDoesntHave('fieldValues', function ($sub) use ($fieldId) {
                            $sub->where('category_field_id', $fieldId);
                        });
                });
            }
        }

        // ✅ تنفيذ الاستعلام مع أو بدون ترقيم صفحات
        $usePagination = $request->input('nopagination') != 1;
        $ads = $usePagination ? $query->paginate(15)->withQueryString() : $query->get();

        // ✅ دالة تحويل بيانات الإعلان
        $transformAd = function ($ad) {
            $ad->main_image = $ad->main_image ? url($ad->main_image) : null;
            $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();

            $subImages = $ad->subImages->map(function ($image) {
                $image->image = url($image->image);
                return $image;
            });

            $fieldValues =    $ad->fieldValues->transform(function ($fieldValue) {
                $field = optional($fieldValue->field);
                $fieldValueModel = optional($fieldValue->fieldValue);

                $fieldType = $fieldValueModel->field_type ?? 'Unknown';
                $valueAr = $fieldValueModel->value_ar ?? 'غير معروف';
                $valueEn = $fieldValueModel->value_en ?? 'Unknown';

                if ($fieldType === 'text') {
                    $currentValueId = $valueAr;
                    $maxDepth = 10; // لتجنب الحلقات اللامتناهية
                    $depth = 0;

                    while (is_numeric($currentValueId) && $depth < $maxDepth) {
                        $realValue = \App\Models\CategoryFieldValue::find($currentValueId);

                        if (!$realValue || $realValue->category_field_id != $fieldValue->category_field_id) {
                            break; // خروج إذا القيمة غير موجودة أو الحقل مختلف
                        }

                        $valueAr = $realValue->value_ar ?? $valueAr;
                        $valueEn = $realValue->value_en ?? $valueEn;

                        if (!is_numeric($valueAr)) {
                            break; // وجدنا النص الحقيقي
                        }

                        $currentValueId = $valueAr; // نكمل البحث
                        $depth++;
                    }
                }

                return [
                    'field_id' => $fieldValue->category_field_id,
                    'field_name' => [
                        'ar' => $field->field_ar ?? 'غير معروف',
                        'en' => $field->field_en ?? 'Unknown',
                    ],
                    'field_value_id' => $fieldValue->category_field_value_id,
                    'field_value' => [
                        'ar' => $valueAr,
                        'en' => $valueEn,
                    ],
                    'field_type' => $fieldType,
                ];
            });



            return [
                'id' => $ad->id,
                'user_id' => $ad->user_id,
                'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                'user_registered_at' => optional($ad->user)->created_at,
                'last_ad_posted_at' => optional($latestAd)->created_at,
                'title' => $ad->title,
                'description' => $ad->description,
                'address' => $ad->address,
                'price' => $ad->price,
                'phone_number' => $ad->phone_number,
                'kilometer' => $ad->kilometer,
                'status' => $ad->status,
                'main_image' => $ad->main_image,
                'sub_images' => $subImages,
                'details' => $fieldValues,
                'view_count' => $ad->views_count,
                'car_model_id' => $ad->car_model ?? null,
                'car_model_value_ar' => $ad->carModel?->value_ar ?? null,
                'car_model_value_en' => $ad->carModel?->value_en ?? null,
            ];
        };

        // ✅ التحويل والإرجاع
        if ($usePagination) {
            $ads->getCollection()->transform($transformAd);

            return response()->json([
                'ads' => $ads->items(),
                'pagination' => [
                    'current_page' => $ads->currentPage(),
                    'last_page' => $ads->lastPage(),
                    'per_page' => $ads->perPage(),
                    'total' => $ads->total(),
                ],
            ]);
        } else {
            $adsTransformed = $ads->map($transformAd);
            return response()->json([
                'ads' => $adsTransformed,
                'pagination' => null,
            ]);
        }
    }
    public function indexadsusersByViews(Request $request)
    {
        $query = Ad::with(['subImages', 'fieldValues', 'user'])
            ->withCount('views') // جلب عدد المشاهدات
            ->where('status', 'approved') // ✅ جلب الإعلانات الموافق عليها فقط
            ->orderByDesc('views_count'); // ترتيب تنازلي حسب عدد المشاهدات  

        // ✅ التحقق من القيم المدخلة
        $filters = [
            'category_id' => Category::where('id', $request->category_id)->exists(),
            'country_id' => Country::where('id', $request->country_id)->exists(),
        ];

        foreach ($filters as $key => $isValid) {
            if ($request->has($key) && !$isValid) {
                return response()->json(['ads' => []], 200);
            }
        }

        // ✅ تطبيق الفلاتر المطلوبة
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // ✅ جلب فقط أول 15 إعلان بدون pagination
        $ads = $query->take(15)->get();

        // ✅ تحويل البيانات
        $transformedAds = $ads->map(function ($ad) {
            // تحويل صورة الغلاف
            $ad->main_image = $ad->main_image ? url($ad->main_image) : null;

            // جلب أحدث إعلان للمستخدم  
            $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();

            // تحويل الصور الفرعية  
            $subImages = $ad->subImages->map(function ($image) {
                $image->image = url($image->image);
                return $image;
            });



            $details = $ad->fieldValues->map(function ($fieldValue) {
                $fieldType = optional($fieldValue->fieldValue)->field_type;
                $value_ar = optional($fieldValue->fieldValue)->value_ar;
                $value_en = optional($fieldValue->fieldValue)->value_en;

                if ($fieldType === 'text' && is_numeric($value_ar)) {
                    $dbValue = DB::table('category_field_values')->where('id', $value_ar)->first();

                    if ($dbValue && $dbValue->category_field_id == $fieldValue->category_field_id) {
                        $value_ar = $dbValue->value_ar ?? $value_ar;
                        $value_en = $dbValue->value_en ?? $value_en;
                    }
                }

                return [
                    'field_id' => $fieldValue->category_field_id,
                    'field_name' => [
                        'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                        'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                    ],
                    'field_value' => [
                        'id' => $fieldValue->category_field_value_id,
                        'ar' => $value_ar ?? 'غير معروف',
                        'en' => $value_en ?? 'Unknown',
                    ],
                ];
            });


            // إرجاع البيانات بشكل منسق
            return [
                'id' => $ad->id,
                'user_id' => $ad->user_id,
                'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                'user_registered_at' => optional($ad->user)->created_at ?? null,
                'last_ad_posted_at' => optional($latestAd)->created_at ?? null,
                'title' => $ad->title,
                'description' => $ad->description,
                'address' => $ad->address,
                'kilometer' => $ad->kilometer,
                'price' => $ad->price,
                'phone_number' => $ad->phone_number,
                'status' => $ad->status,
                'main_image' => $ad->main_image,
                'sub_images' => $subImages,
                'details' => $details,
                'view_count' => $ad->views_count,
            ];
        });

        return response()->json([
            'ads' => $transformedAds,
        ]);
    }
    public function indexAdsGroupedByCategory(Request $request)
    {
        $now = Carbon::now();

        $categories = Category::with(['ads' => function ($query) use ($request) {
            if ($request->has('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            $query->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->with(['subImages', 'fieldValues.field', 'fieldValues.fieldValue', 'user']) // تأكدنا من جلب الحقول
                ->withCount('views');
        }])->get();

        $result = $categories->map(function ($category) use ($now) {
            return [
                'category_id' => $category->id,
                'category_name_ar' => $category->name_ar,
                'category_name_en' => $category->name_en,

                'ads' => $category->ads
                    ->map(function ($ad) use ($now) {
                        $duration = $ad->approved_at ? $now->diffInSeconds($ad->approved_at) : 1;
                        $views_per_second = $duration > 0 ? $ad->views_count / $duration : $ad->views_count;

                        $ad->main_image = $ad->main_image ? url($ad->main_image) : null;

                        $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();

                        $ad->subImages->transform(function ($image) {
                            $image->image = url($image->image);
                            return $image;
                        });

                        $ad->fieldValues->transform(function ($fieldValue) {
                            $fieldType = optional($fieldValue->fieldValue)->field_type;
                            $value_ar = optional($fieldValue->fieldValue)->value_ar;
                            $value_en = optional($fieldValue->fieldValue)->value_en;

                            if ($fieldType === 'text' && is_numeric($value_ar)) {
                                $dbValue = DB::table('category_field_values')->where('id', $value_ar)->first();

                                if ($dbValue && $dbValue->category_field_id == $fieldValue->category_field_id) {
                                    $value_ar = $dbValue->value_ar ?? $value_ar;
                                    $value_en = $dbValue->value_en ?? $value_en;
                                }
                            }

                            return [
                                'field_id' => $fieldValue->category_field_id,
                                'field_name' => [
                                    'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                                    'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                                ],
                                'field_value' => [
                                    'id' => $fieldValue->category_field_value_id,
                                    'ar' => $value_ar ?? 'غير معروف',
                                    'en' => $value_en ?? 'Unknown',
                                ],
                            ];
                        });

                        return [
                            'id' => $ad->id,
                            'user_id' => $ad->user_id,
                            'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                            'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                            'user_registered_at' => optional($ad->user)->created_at ?? null,
                            'last_ad_posted_at' => optional($latestAd)->created_at ?? null,
                            'title' => $ad->title,
                            'description' => $ad->description,
                            'address' => $ad->address,
                            'kilometer' => $ad->kilometer,
                            'price' => $ad->price,
                            'phone_number' => $ad->phone_number,
                            'status' => $ad->status,
                            'main_image' => $ad->main_image,
                            'sub_images' => $ad->subImages,
                            'details' => $ad->fieldValues,
                            'view_count' => $ad->views_count,
                            'views_per_second' => $views_per_second,
                        ];
                    })
                    ->sortByDesc('views_per_second')
                    ->values(),
            ];
        });

        return response()->json([
            'categories' => $result,
        ]);
    }
    public function search(Request $request)
    {
        $query = Ad::with(['subImages', 'fieldValues', 'user', 'views']);

        // ✅ فلترة حسب الحالة إذا كانت موجودة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ فلترة حسب الدولة إذا موجودة
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }


        // ✅ فلترة حسب التصنيف إذا موجود
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 🔍 البحث في العنوان أو الوصف أو الحقول
        if ($request->has('query')) {
            $search = $request->query('query');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%")
                    ->orWhereHas('fieldValues', function ($q) use ($search) {
                        $q->whereHas('field', function ($subQuery) use ($search) {
                            $subQuery->where('field_ar', 'LIKE', "%$search%")
                                ->orWhere('field_en', 'LIKE', "%$search%");
                        })
                            ->orWhereHas('fieldValue', function ($subQuery) use ($search) {
                                $subQuery->where('value_ar', 'LIKE', "%$search%")
                                    ->orWhere('value_en', 'LIKE', "%$search%");
                            });
                    });
            });
        }

        $ads = $query->get();

        // تحويل البيانات
        $ads->transform(function ($ad) {
            $ad->main_image = $ad->main_image ? url($ad->main_image) : null;
            $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();
            $ad->views_count = $ad->views()->distinct('user_id')->count();

            $ad->subImages->transform(fn($image) => ['image' => url($image->image)]);
            $ad->fieldValues->transform(fn($fieldValue) => [
                'field_id' => $fieldValue->category_field_id,
                'field_name' => [
                    'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                    'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                ],
                'field_value' => [
                    'ar' => optional($fieldValue->fieldValue)->value_ar ?? 'غير معروف',
                    'en' => optional($fieldValue->fieldValue)->value_en ?? 'Unknown',
                ],
            ]);

            return [
                'id' => $ad->id,
                'category' => $ad->category->id,
                'user_id' => $ad->user_id,
                'country_id' => $ad->country_id,
                'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                'user_registered_at' => optional($ad->user)->created_at ?? null,
                'last_ad_posted_at' => optional($latestAd)->created_at ?? null,
                'title' => $ad->title,
                'description' => $ad->description,
                'address' => $ad->address,
                'kilometer' => $ad->kilometer,
                'price' => $ad->price,
                'phone_number' => $ad->phone_number,
                'status' => $ad->status,
                'main_image' => $ad->main_image,
                'sub_images' => $ad->subImages,
                'details' => $ad->fieldValues,
                'views_count' => $ad->views_count,
            ];
        });

        return response()->json(['ads' => $ads]);
    }
    public function indexbyuserid(Request $request)
    {
        $query = Ad::with(['subImages', 'fieldValues', 'user']);

        // تطبيق الفلتر بناءً على user_id فقط
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // تنفيذ الاستعلام وجلب النتائج
        $ads = $query->get();

        // تحويل البيانات
        $ads->transform(function ($ad) {
            $ad->main_image = $ad->main_image ? url($ad->main_image) : null;

            // جلب أحدث إعلان للمستخدم
            $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();

            // تحويل الصور الفرعية
            $ad->subImages->transform(function ($image) {
                $image->image = url($image->image);
                return $image;
            });

            // تحويل تفاصيل الحقول
            $ad->fieldValues->transform(function ($fieldValue) {
                return [
                    'field_id' => $fieldValue->category_field_id,
                    'field_name' => [
                        'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                        'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                    ],
                    'field_value' => [
                        'ar' => optional($fieldValue->fieldValue)->value_ar ?? 'غير معروف',
                        'en' => optional($fieldValue->fieldValue)->value_en ?? 'Unknown',
                    ],
                ];
            });

            return [
                'id' => $ad->id,
                'user_id' => $ad->user_id,
                'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                'user_registered_at' => optional($ad->user)->created_at ?? null, // أول تسجيل للمستخدم
                'last_ad_posted_at' => optional($latestAd)->created_at ?? null, // آخر إعلان تم نشره
                'title' => $ad->title,
                'description' => $ad->description,
                'address' => $ad->address,
                'kilometer' => $ad->kilometer,

                'price' => $ad->price,
                'phone_number' => $ad->phone_number,
                'status' => $ad->status,
                'main_image' => $ad->main_image,
                'sub_images' => $ad->subImages,
                'details' => $ad->fieldValues,
            ];
        });

        return response()->json(['ads' => $ads]);
    }
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'lang' => 'sometimes|in:ar,en',
            'rejection_reason' => 'nullable|string', // اختياري، نص لو موجود
        ]);

        $lang = $request->input('lang', 'ar'); // افتراضياً عربي

        // جلب الإعلان فقط بدون العلاقات
        $ad = Ad::findOrFail($id);

        $status = $request->status;
        $ad->status = $status;


        if ($status === 'approved') {
            $ad->accepted_at = now();
            $ad->rejection_reason = null; // لما يُوافق، امسح سبب الرفض
        } elseif ($status === 'rejected') {
            $ad->accepted_at = null;
            $ad->rejection_reason = $request->input('rejection_reason', null); // خزن سبب الرفض
        } else {
            $ad->accepted_at = null;
            $ad->rejection_reason = null;
        }



        // if ($status === 'approved') {
        //     $ad->accepted_at = now(); // تعيين وقت القبول
        // } else {
        //     $ad->accepted_at = null; // مسح وقت القبول لو لم يكن approved
        // }

        $saveResult = $ad->save();

        // جلب أسماء الدولة بالعربية والإنجليزية مباشرة من الجدول باستخدام id الدولة في الإعلان
        $countryNameAr = DB::table('countries')->where('id', $ad->country_id)->value('name_ar');
        $countryNameEn = DB::table('countries')->where('id', $ad->country_id)->value('name_en');
        $countryNameAr = $countryNameAr ?? 'بدون دولة';
        $countryNameEn = $countryNameEn ?? 'No Country';

        // جلب أسماء الفئة بالعربية والإنجليزية مباشرة من الجدول باستخدام id الفئة في الإعلان
        $categoryNameAr = DB::table('categories')->where('id', $ad->category_id)->value('name_ar');
        $categoryNameEn = DB::table('categories')->where('id', $ad->category_id)->value('name_en');
        $categoryNameAr = $categoryNameAr ?? 'بدون فئة';
        $categoryNameEn = $categoryNameEn ?? 'No Category';

        // لاستخدام في الرسائل حسب اللغة المطلوبة
        $categoryName = ($lang === 'ar') ? $categoryNameAr : $categoryNameEn;
        $countryName = ($lang === 'ar') ? $countryNameAr : $countryNameEn;

        $messages = [
            'approved' => [
                'ar' => "إعلانك في $categoryNameAr - $countryNameAr تم قبوله!",
                'en' => "Your ad in $categoryNameEn - $countryNameEn has been approved!",
            ],
            'rejected' => [
                'ar' => $ad->rejection_reason
                    ? "إعلانك في $categoryNameAr - $countryNameAr تم رفضه بسبب: " . $ad->rejection_reason
                    : "إعلانك في $categoryNameAr - $countryNameAr تم رفضه!",
                'en' => $ad->rejection_reason
                    ? "Your ad in $categoryNameEn - $countryNameEn has been rejected due to: " . $ad->rejection_reason
                    : "Your ad in $categoryNameEn - $countryNameEn has been rejected!",
            ],
            'pending' => [
                'ar' => "إعلانك في $categoryNameAr - $countryNameAr قيد المراجعة!",
                'en' => "Your ad in $categoryNameEn - $countryNameEn is under review!",
            ],
        ];

        // إنشاء إشعار لصاحب الإعلان
        $notificationAdStatus = Notification::create([
            'user_id' => $ad->user_id,
            'ad_id' => $ad->id,
            'type' => 'ad_status',
            'message_ar' => $messages[$status]['ar'],
            'message_en' => $messages[$status]['en'],
            'is_read' => false,
        ]);

        $followersNotificationsCount = 0;
        $followersIds = [];
        //\Log::info('Ad ID:', ['id' => $ad->id]);

        // إذا الإعلان تم قبوله، إرسال إشعارات للمتابعين
        if ($status === 'approved') {
            $followersIds = DB::table('followers')
                ->where('following_id', $ad->user_id)
                ->pluck('follower_id')
                ->toArray();

            if (!empty($followersIds)) {
                $now = now();
                $notifications = array_map(function ($followerId) use ($ad, $categoryNameAr, $categoryNameEn, $countryNameAr, $countryNameEn, $now) {
                    return [
                        'user_id' => $followerId,
                        'ad_id' => $ad->id,
                        'type' => 'followed_user_ad_approved',
                        'message_ar' => "المستخدم الذي تتابعه نشر إعلانًا جديدًا في $categoryNameAr - $countryNameAr",
                        'message_en' => "A user you follow has posted a new ad in $categoryNameEn - $countryNameEn",
                        'is_read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $followersIds);

                $inserted = Notification::insert($notifications);
                if ($inserted) {
                    $followersNotificationsCount = count($notifications);
                }
            }
        }

        return response()->json([
            'message' => 'تم تحديث حالة الإعلان بنجاح.',
        ]);
    }
    public function indexbyadsid(Request $request)
    {
        // $query = Ad::with(['subImages', 'fieldValues.field', 'fieldValues.fieldValue', 'user', 'adViews', 'features.value.field']);
        $query = Ad::with(['subImages', 'fieldValues.field', 'fieldValues.fieldValue', 'user', 'adViews', 'features.value.field', 'reel']);

        $query->leftJoin('car_models', 'ads.car_model', '=', 'car_models.id')
            ->select('ads.*', 'car_models.id as car_model_id', 'car_models.value_ar as car_model_ar', 'car_models.value_en as car_model_en');

        if ($request->has('ad_id')) {
            $query->where('ads.id', $request->ad_id);
        }

        $ads = $query->get();

        $ads->transform(function ($ad) {
            $reelVideoUrl = $ad->reel && $ad->reel->reels_video_url ? url($ad->reel->reels_video_url) : null;
            $reelThumbnailUrl = $ad->reel && $ad->reel->reels_thumbnail_url ? url($ad->reel->reels_thumbnail_url) : null;
            $reelLikesCount = 0;
            $likedUsers = [];

            if ($ad->reel) {
                $reelLikesCount = ReelLikesLog::where('reel_id', $ad->reel->reels_id)
                    ->where('reaction', 'like')
                    ->count();

                $likedUserIds = ReelLikesLog::where('reel_id', $ad->reel->reels_id)
                    ->where('reaction', 'like')
                    ->pluck('user_id')
                    ->toArray();

                $likedUsers = Userauth::whereIn('id', $likedUserIds)
                    ->get(['id', 'first_name', 'last_name', 'profile_image'])
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'profile_image' => $user->profile_image ? url('profile_images/' . $user->profile_image) : null,
                        ];
                    });
            }


            $ad->main_image = $ad->main_image ? url($ad->main_image) : null;

            $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();

            $ad->subImages->transform(function ($image) {
                $image->image = url($image->image);
                return $image;
            });


            $ad->fieldValues->transform(function ($fieldValue) {
                $field = optional($fieldValue->field);
                $fieldValueModel = optional($fieldValue->fieldValue);

                $fieldType = $fieldValueModel->field_type ?? 'Unknown';
                $valueAr = $fieldValueModel->value_ar ?? 'غير معروف';
                $valueEn = $fieldValueModel->value_en ?? 'Unknown';



                $fieldValueId = $fieldValue->category_field_value_id;

                if ($fieldType === 'text') {
                    $currentValueId = $valueAr;
                    $maxDepth = 10;
                    $depth = 0;

                    while (is_numeric($currentValueId) && $depth < $maxDepth) {
                        $realValue = \App\Models\CategoryFieldValue::find($currentValueId);

                        if (!$realValue || $realValue->category_field_id != $fieldValue->category_field_id) {
                            break;
                        }

                        $valueAr = $realValue->value_ar ?? $valueAr;
                        $valueEn = $realValue->value_en ?? $valueEn;

                        $fieldValueId = $realValue->id; // ✅ هنا التعديل: خزن ID الفعلي النهائي

                        if (!is_numeric($valueAr)) {
                            break;
                        }

                        $currentValueId = $valueAr;
                        $depth++;
                    }
                }


                return [
                    'field_id' => $fieldValue->category_field_id,
                    'field_name' => [
                        'ar' => $field->field_ar ?? 'غير معروف',
                        'en' => $field->field_en ?? 'Unknown',
                    ],
                    'field_value_id' => $fieldValueId, // ✅ هنا التعديل المهم
                    'field_value' => [
                        'ar' => $valueAr,
                        'en' => $valueEn,
                    ],
                    'field_type' => $fieldType,
                ];
            });



            $features = $ad->features->map(function ($feature) {
                return [
                    'feature_id' => $feature->feature_id,
                    'value_ar' => optional($feature->value)->value_ar ?? 'غير معروف',
                    'value_en' => optional($feature->value)->value_en ?? 'Unknown',
                ];
            });


            return [
                'id' => $ad->id,
                'country_id' => $ad->country_id,
                'city_id' => $ad->city_id,
                'user_id' => $ad->user_id,
                'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                'user_registered_at' => optional($ad->user)->created_at ?? null,
                'last_ad_posted_at' => ($latestAd && $latestAd->status === 'approved')
                    ? ($latestAd->accepted_at ? \Carbon\Carbon::parse($latestAd->accepted_at)->toISOString() : null)
                    : optional($latestAd)->created_at,
                'title' => $ad->title,
                'category_id' => $ad->category_id,
                'description' => $ad->description,
                'address' => $ad->address,
                'price' => $ad->price,
                'kilometer' => $ad->kilometer,
                'phone_number' => $ad->phone_number,
                'status' => $ad->status,
                'main_image' => $ad->main_image,
                'sub_images' => $ad->subImages,
                'details' => $ad->fieldValues,
                'features' => $features,
                'view_count' => $ad->adViews->count(),
                'car_model_id' => $ad->car_model_id,
                'car_model_ar' => $ad->car_model_ar,
                'car_model_en' => $ad->car_model_en,
                'accepted_at' => $ad->accepted_at ? \Carbon\Carbon::parse($ad->accepted_at)->toISOString() : null,
                'reel_video_url' => $reelVideoUrl,
                'reel_thumbnail_url' => $reelThumbnailUrl,
                'reel_likes_count' => $reelLikesCount,
                'liked_users' => $likedUsers,


            ];
        });

        return response()->json(['ads' => $ads]);
    }
    public function updateCarOptionFeature(Request $request)
    {
        // تحقق من البيانات المطلوبة
        $request->validate([
            'ad_id' => 'required|integer|exists:car_ad_features,car_ad_id',
            'feature_id' => 'required|integer|exists:car_ad_features,feature_id',
            'new_feature_id' => 'required|integer|exists:category_field_values,id',
        ]);

        // محاولة العثور على السجل
        $feature = AdFeature::where('car_ad_id', $request->ad_id)
            ->where('feature_id', $request->feature_id)
            ->first();

        if (!$feature) {
            return response()->json(['message' => 'Feature not found for this ad.'], 404);
        }

        // تحديث قيمة الميزة
        $feature->feature_id = $request->new_feature_id;
        $feature->save();

        return response()->json([
            'message' => 'Car option feature updated successfully.',
            'data' => $feature
        ]);
    }
    public function toggleFavorite(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'ad_id' => 'required|exists:ads,id',
        ]);

        $favorite = Favorite::where('user_id', $user->id)
            ->where('ad_id', $request->ad_id)
            ->first();

        if ($favorite) {
            // إزالة الإعلان من المفضلة
            $favorite->delete();
            return response()->json(['message' => 'Ad removed from favorites']);
        } else {
            // إضافة الإعلان إلى المفضلة
            Favorite::create([
                'user_id' => $user->id,
                'ad_id' => $request->ad_id,
            ]);
            return response()->json(['message' => 'Ad added to favorites']);
        }
    }
    public function getFavorites()
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $favorites = Favorite::with(['ad.subImages', 'ad.fieldValues', 'ad.user'])
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($favorite) {
                $ad = $favorite->ad;
                $latestAd = Ad::where('user_id', $ad->user_id)->latest('created_at')->first();

                // معالجة التفاصيل (details فقط) - The Decoder Ring
                $details =    $ad->fieldValues->transform(function ($fieldValue) {
                    $field = optional($fieldValue->field);
                    $fieldValueModel = optional($fieldValue->fieldValue);

                    $fieldType = $fieldValueModel->field_type ?? 'Unknown';
                    $valueAr = $fieldValueModel->value_ar ?? 'غير معروف';
                    $valueEn = $fieldValueModel->value_en ?? 'Unknown';

                    if ($fieldType === 'text') {
                        $currentValueId = $valueAr;
                        $maxDepth = 10; // لتجنب الحلقات اللامتناهية
                        $depth = 0;

                        // تابع البحث حتى تصل لقيمة نص حقيقية أو تنتهي الشروط
                        while (is_numeric($currentValueId) && $depth < $maxDepth) {
                            $realValue = \App\Models\CategoryFieldValue::find($currentValueId);

                            // إذا ما فيش قيمة أو الحقل مختلف عن المطلوب نوقف
                            if (!$realValue || $realValue->category_field_id != $fieldValue->category_field_id) {
                                break;
                            }

                            $valueAr = $realValue->value_ar ?? $valueAr;
                            $valueEn = $realValue->value_en ?? $valueEn;

                            // لو القيمة دي مش رقم، معناها وصلنا للنص الحقيقي
                            if (!is_numeric($valueAr)) {
                                break;
                            }

                            $currentValueId = $valueAr; // نتابع البحث بالقيمة الجديدة
                            $depth++;
                        }
                    }

                    return [
                        'field_id' => $fieldValue->category_field_id,
                        'field_name' => [
                            'ar' => $field->field_ar ?? 'غير معروف',
                            'en' => $field->field_en ?? 'Unknown',
                        ],
                        'field_value_id' => $fieldValue->category_field_value_id,
                        'field_value' => [
                            'ar' => $valueAr,
                            'en' => $valueEn,
                        ],
                        'field_type' => $fieldType,
                    ];
                });




                return [
                    'id' => $ad->id,
                    'user_id' => $ad->user_id,
                    'user_name' => trim(optional($ad->user)->first_name . ' ' . optional($ad->user)->last_name) ?: null,
                    'user_image' => optional($ad->user)->profile_image ? url('profile_images/' . $ad->user->profile_image) : null,
                    'user_registered_at' => optional($ad->user)->created_at ?? null,
                    'last_ad_posted_at' => optional($latestAd)->created_at ?? null,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'address' => $ad->address,
                    'price' => $ad->price,
                    'kilometer' => $ad->kilometer,

                    'phone_number' => $ad->phone_number,
                    'status' => $ad->status,
                    'main_image' => $ad->main_image ? url($ad->main_image) : null,
                    'sub_images' => $ad->subImages->map(fn($image) => ['image' => url($image->image)]),
                    'details' => $details,
                ];
            });

        return response()->json(['favorites' => $favorites]);
    }
    public function seen(Request $request, $ad_id)
    {
        // جلب الإعلان والتأكد من أنه موجود وحالته approved
        $ad = Ad::where('id', $ad_id)->where('status', 'approved')->firstOrFail();

        // جلب المستخدم المسجل (لو موجود)
        $user = auth('api')->user(); // أو auth()->user() حسب نوع التوثيق

        // التأكد إن المستخدم مش هو صاحب الإعلان
        if ($user && $user->id == $ad->user_id) {
            return response()->json(['message' => 'You cannot view your own ad.'], 403);
        }

        // تحديد الهوية بناءً على المستخدم أو IP الزائر
        $identifier = $user ? 'user_' . $user->id : 'guest_' . $request->ip();

        // التحقق إذا كان قد سبق تسجيل مشاهدة لهذا الإعلان من نفس الزائر
        $alreadyViewed = AdView::where('identifier', $identifier)
            ->where('ad_id', $ad_id)
            ->exists();

        if ($alreadyViewed) {
            return response()->json(['message' => 'View already recorded previously.'], 200);
        }

        // تسجيل المشاهدة لأول مرة فقط
        AdView::create([
            'identifier' => $identifier,
            'ad_id' => $ad_id,
        ]);

        return response()->json(['message' => 'Ad view recorded successfully']);
    }
    public function getUserProfile(Request $request, $user_id)
    {
        $authUser = JWTAuth::parseToken()->authenticate();

        $user = Userauth::with(['followers', 'following', 'ads.subImages', 'ads.fieldValues.fieldValue', 'ads.fieldValues.field'])->find($user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // التحقق مما إذا كان المستخدم المصادق عليه يتابع هذا الحساب
        $isFollowing = $authUser ? $user->followers->contains('follower_id', $authUser->id) : false;

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'is_blocked' => $user->is_blocked,
            'profile_image' => $user->profile_image ? url('profile_images/' . $user->profile_image) : null,
            'cover_image' => $user->cover_image ? url('cover_images/' . $user->cover_image) : null,
            'followers_count' => $user->followers->count(),
            'following_count' => $user->following->count(),
            'is_following' => $isFollowing,
            'ads' => $user->ads->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'price' => $ad->price,
                    'kilometer' => $ad->kilometer,
                    'status' => $ad->status,
                    'main_image' => $ad->main_image ? url($ad->main_image) : null,
                    'sub_images' => $ad->subImages->map(fn($image) => url($image->image)),
                    'details' => $ad->fieldValues->map(function ($fieldValue) {
                        $arVal = $fieldValue->fieldValue?->value_ar;
                        $enVal = $fieldValue->fieldValue?->value_en;

                        // فك تشفير القيمة إذا كان النوع "text" والقيمة رقمية (ID)
                        if ($fieldValue->fieldValue?->field_type === 'text' && is_numeric($arVal)) {
                            $textValue = \DB::table('category_field_values')->find($arVal);
                            if ($textValue && $textValue->category_field_id == $fieldValue->category_field_id) {
                                $arVal = $textValue->value_ar;
                                $enVal = $textValue->value_en;
                            }
                        }

                        return [
                            'field_id' => $fieldValue->category_field_id,
                            'field_name' => [
                                'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                                'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                            ],
                            'field_value' => [
                                'ar' => $arVal ?? 'غير معروف',
                                'en' => $enVal ?? 'Unknown',
                            ],
                        ];
                    }),
                ];
            }),
        ]);
    }
    public function stats()
    {
        $totalAds = \App\Models\Ad::count();

        $statusCounts = \App\Models\Ad::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'total_ads' => $totalAds,
            'status_counts' => [
                'pending' => $statusCounts->get('pending', 0),
                'approved' => $statusCounts->get('approved', 0),
                'rejected' => $statusCounts->get('rejected', 0),
            ],
        ]);
    }
    public function mostRecentAds(Request $request)
    {
        $query = Ad::where('status', 'approved')
            ->with([
                'user',
                'fieldValues.field',
                'fieldValues.fieldValue',
            ])
            ->orderBy('created_at', 'desc');
        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }
        $ads = $query->limit(15)->get();
        $adsData = $ads->map(function ($ad) {
            $adArray = $ad->toArray();
            // إزالة field_values من المصفوفة
            unset($adArray['field_values']);

            // تحويل صورة الغلاف
            $adArray['main_image'] = $ad->main_image ? url($ad->main_image) : null;

            // تحويل صور المستخدم
            if (isset($adArray['user']['profile_image'])) {
                $adArray['user']['profile_image'] = url('profile_images/' . $adArray['user']['profile_image']);
            }
            if (isset($adArray['user']['cover_image'])) {
                $adArray['user']['cover_image'] = url('cover_images/' . $adArray['user']['cover_image']);
            }

            // معلومات المالك
            $adArray['ownerName'] = $ad->user ? $ad->user->first_name . ' ' . $ad->user->last_name : null;
            $adArray['ownerPhoto'] = $adArray['user']['profile_image'] ?? null;
            $adArray['created_at'] = $ad->created_at->toDateTimeString();

            // معالجة التفاصيل (details فقط)
            $adArray['details'] = $ad->fieldValues->map(function ($fieldValue) {
                $arVal = $fieldValue->fieldValue?->value_ar;
                $enVal = $fieldValue->fieldValue?->value_en;

                // التحقق من النوع "text" ومعالجة القيمة إذا لزم
                if ($fieldValue->fieldValue?->field_type === 'text' && is_numeric($arVal)) {
                    $textValue = \DB::table('category_field_values')->find($arVal);
                    if ($textValue && $textValue->category_field_id == $fieldValue->category_field_id) {
                        $arVal = $textValue->value_ar;
                        $enVal = $textValue->value_en;
                    }
                }

                return [
                    'field_id' => $fieldValue->category_field_id,
                    'field_name' => [
                        'ar' => optional($fieldValue->field)->field_ar ?? 'غير معروف',
                        'en' => optional($fieldValue->field)->field_en ?? 'Unknown',
                    ],
                    'field_value' => [
                        'id' => $fieldValue->category_field_value_id,
                        'ar' => $arVal ?? 'غير معروف',
                        'en' => $enVal ?? 'Unknown',
                    ],
                ];
            });

            return $adArray;
        });


        return response()->json([
            'success' => true,
            'data' => $adsData
        ]);
    }
    public function testvidresize(Request $request)
    {
        if (!$request->hasFile('rvid')) {
            return response()->json([
                'success' => false,
                'message' => 'No video file uploaded',
            ], 400);
        }

        $video = $request->file('rvid');

        // معلومات الفيديو الأساسية
        $videoInfo = [
            'original_name' => $video->getClientOriginalName(),
            'mime_type' => $video->getMimeType(),
            'extension' => $video->getClientOriginalExtension(),
            'size_in_bytes' => $video->getSize(),
            'temporary_path' => $video->getRealPath(),
        ];

        $ffmpegPath = public_path('../ffmpeg-7.0.2-amd64-static/ffmpeg');
        $ffprobePath = public_path('../ffmpeg-7.0.2-amd64-static/ffprobe');

        $ffmpegExists = file_exists($ffmpegPath);
        $ffprobeExists = file_exists($ffprobePath);

        $outputDir = storage_path('app/public/compressed');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $filename = uniqid('compressed_') . '.mp4';
        $outputFile = $outputDir . '/' . $filename;

        $command = "$ffmpegPath -i " . escapeshellarg($video->getRealPath()) .
            " -vcodec libx264 -crf 28 -preset fast -acodec aac -b:a 128k " .
            escapeshellarg($outputFile) . " 2>&1";

        exec($command, $outputLog, $exitCode);

        $relativeUrl = Storage::disk('public')->url('compressed/' . $filename);
        $outputUrl = url($relativeUrl);  // هذا يضمن وجود الدومين في الرابط

        return response()->json([
            'success' => true,
            'video_info' => $videoInfo,
            'ffmpeg_path' => $ffmpegPath,
            'ffmpeg_exists' => $ffmpegExists,
            'ffprobe_path' => $ffprobePath,
            'ffprobe_exists' => $ffprobeExists,
            'compression' => [
                'output_file_path' => $outputFile,
                'output_file_url' => $outputUrl,
                'exit_code' => $exitCode,
                'ffmpeg_command' => $command,
                'log' => $outputLog
            ]
        ]);
    }
}
