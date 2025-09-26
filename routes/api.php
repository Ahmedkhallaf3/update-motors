<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\UserauthController;
use App\Http\Controllers\Apis\CityController;
use App\Http\Controllers\Apis\BannerController;
use App\Http\Controllers\Apis\CountryController;
use App\Http\Controllers\Apis\CategoryController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\Apis\CategoryFieldController;
use App\Http\Controllers\Apis\BlogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Api\CarModelController;
use App\Http\Controllers\VisitorLogController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\GoogleController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [UserauthController::class, 'register']);

// تسجيل الدخول وإرجاع التوكن 
Route::post('/login', [UserauthController::class, 'login']);
Route::middleware('auth:api')->post('/logout', [UserauthController::class, 'logout']);

Route::middleware('auth:api')->get('/me', [UserauthController::class, 'me']);

Route::middleware(['auth:api', 'is.admin'])->get('listUsers', [UserauthController::class, 'listUsers']);


Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetCode']);
Route::post('/verify-code', [ResetPasswordController::class, 'verifyCode']);
Route::middleware('auth:api')->post('/reset-password', [ResetPasswordController::class, 'resetPassword']);

Route::middleware('auth:api')->post('/update-profile', [UserauthController::class, 'update']);


Route::middleware('auth:api')->group(function () {
    Route::post('/follow/{id}', [FollowController::class, 'follow']); // متابعة
    Route::post('/unfollow/{id}', [FollowController::class, 'unfollow']); // إلغاء المتابعة
    Route::get('/user/{id}/followers', [FollowController::class, 'countFollowers']); // جلب عدد المتابعين والمتابَعين
});

Route::middleware('auth:api')->group(function () {


});




Route::middleware('auth:api')->get('/userdetials/{user_id}', [AdController::class, 'getUserProfile']); // ✅ جميع الإعلانات

Route::get('/banners', [BannerController::class, 'index']);
Route::post('/banners', [BannerController::class, 'store']);
Route::post('/banners/{id}', [BannerController::class, 'update']);
Route::delete('/banners/{id}', [BannerController::class, 'destroy']);

Route::get('/blogs', [BlogController::class, 'index']);
Route::post('/blogs', [BlogController::class, 'store']);
Route::post('/blogs/{id}', [BlogController::class, 'update']);
Route::delete('/blogs/{id}', [BlogController::class, 'destroy']);


Route::get('/categories', [CategoryController::class, 'index']); // عرض جميع التصنيفات
Route::post('/categories', [CategoryController::class, 'store']); // إنشاء تصنيف جديد
Route::post('/categories/{id}', [CategoryController::class, 'update']); // تحديث تصنيف
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']); // حذف تصنيف


Route::get('/countries', [CountryController::class, 'index']);
Route::post('/countries', [CountryController::class, 'store']);
Route::post('/countries/{id}', [CountryController::class, 'update']);
Route::delete('/countries/{id}', [CountryController::class, 'destroy']);

Route::get('/cities', [CityController::class, 'index']);
Route::post('/cities', [CityController::class, 'store']);
Route::post('/cities/{id}', [CityController::class, 'update']);
Route::delete('/cities/{id}', [CityController::class, 'destroy']);


Route::prefix('categories/{categoryId}/fields')->group(function () {
    Route::get('/', [CategoryFieldController::class, 'index']); // عرض جميع الحقول للفئة
    Route::post('/', [CategoryFieldController::class, 'store']); // إضافة حقل جديد



    Route::delete('/{fieldId}', [CategoryFieldController::class, 'destroy']); // حذف حقل
});

Route::post('/update-category-field/{fieldId}', [CategoryFieldController::class, 'updateCategoryFieldArAndEn']);

Route::middleware('auth:api')->group(function () { // ✅ استخدم 'api' وليس 'jwt'
    Route::post('ads', [AdController::class, 'store']);
});

Route::get('/car-models/by-make/{makeId}', [CarModelController::class, 'getByMakeId']);


Route::middleware('auth:api')->group(function () {
    Route::get('/ads', [AdController::class, 'index']);
});

//Route::post('/ads/{id}/status', [AdController::class, 'updateStatus']);
Route::middleware(['auth:api', 'is.admin'])->post('/ads/{id}/status', [AdController::class, 'updateStatus']);
Route::get('/adss', [AdController::class, 'indexadsusers']); // ✅ جميع الإعلانات
Route::get('/search', [AdController::class, 'search']);
Route::get('/adsbyuserid', [AdController::class, 'indexbyuserid']);
Route::get('/adsbyadsid', [AdController::class, 'indexbyadsid']);
Route::post('/ads/{ad_id}/seen', [AdController::class, 'seen']);
Route::get('/ads/popular', [AdController::class, 'indexadsusersByViews']);
Route::get('/ads/allpopular', [AdController::class, 'indexAdsGroupedByCategory']);
// update car option to another car option by use the ad id and the car option id in body use new_feature_id to add the new feature id 
Route::post('/caroptionupdate', [AdController::class, 'updateCarOptionFeature']);
Route::middleware('auth:api')->post('/ads/update/{id}', [AdController::class, 'update']);
//  Route::middleware('auth:api')->delete('/ads/destory/{id}', [AdController::class, 'destroyadmin']); // حذف حقل
// طلب صاحب الموقع ايقاف الحذف من الادمن فقط وبناء عليه علقة السطر وعدلتته فقط للكل 
Route::delete('/ads/destory/{id}', [AdController::class, 'destroy'])->middleware('auth:api');
// Route::middleware(['auth:api', 'is.admin'])->delete('/ads/destory/{id}', [AdController::class, 'destroyadmin']); // حذف حقل 
// Route::delete('/ads/{id}', [AdController::class, 'destroy'])->middleware('auth:api');
Route::middleware('auth:api')->group(function () {
    Route::post('/toggle-favorite', [AdController::class, 'toggleFavorite']); // إضافة وإزالة المفضلة
    Route::get('/favorites', [AdController::class, 'getFavorites']); // جلب الإعلانات المفضلة
});
Route::middleware('auth:api')->group(function () {
    Route::post('/send-message', [ChatController::class, 'sendMessage']);
    Route::get('/messages/{chat_id}', [ChatController::class, 'fetchMessages']);
    Route::post('/mark-as-read/{userId}', [ChatController::class, 'markAsRead']);
    Route::get('/conversations', [ChatController::class, 'conversations']);
    Route::delete('/delete-chat/{chatId}', [ChatController::class, 'deleteChat']);
    Route::get('/has-new-messages', [ChatController::class, 'hasNewMessages']);
    Broadcast::routes(['middleware' => ['auth:api']]);
});
// هذا الراوت محمي بوسطائين (middleware):
// 1. 'auth:api' للتأكد من أن المستخدم مسجل دخول عبر API token.
// 2. 'is.admin' للتأكد من أن المستخدم أدمين فقط.
// فقط الأدمين يمكنه استدعاء دالة blockUser لتبديل حالة الحظر للمستخدم حسب الـ id.
Route::middleware(['auth:api', 'is.admin'])->post('blockUser', [UserauthController::class, 'blockUser']);
// إرجاع إحصائيات عامة عن الإعلانات: العدد الكلي وعدد الإعلانات حسب الحالة (pending, approved, rejected)
Route::middleware(['auth:api', 'is.admin'])->get('/ads/stats', [AdController::class, 'stats']);


Route::post('/categories/{id}/toggle-kilometers', [CategoryController::class, 'toggleKilometersApi']);

Route::post('/categories/{categoryId}/makes/{makeId}/models', [CategoryFieldController::class, 'storeCarModels']);

Route::get('/categories/{categoryId}/makes', [CategoryController::class, 'listMakes']);
Route::delete('/car-models/{modelId}', [CategoryFieldController::class, 'deleteCarModel']);
Route::post('/categories/{categoryId}/addNewMake', [CategoryController::class, 'addNewMake']);
Route::put('/editMakeById/{makeId}', [CategoryController::class, 'editMakeById']);
Route::delete(
    '/deleteMakeById/catid/{catId}/fldid/{fldId}/valid/{valId}',
    [CategoryController::class, 'deleteMakeById']
);
Route::get('/mostRecentAds', [AdController::class, 'mostRecentAds']);
Route::post('/testvidresize', [AdController::class, 'testvidresize']);
Route::post('/add-field', [CategoryController::class, 'addField']);

Route::post('/track-visitor', [VisitorLogController::class, 'track']);
Route::get('/track-visitor-statistics', [VisitorLogController::class, 'statistics']);



//ReelController
Route::get('reels', [ReelController::class, 'getAllReels']);
Route::get('reels/{id}', [ReelController::class, 'getReelById']);
Route::post('reels/{reelId}/increment-view', [ReelController::class, 'incrementView']);

Route::middleware('auth:api')->group(function () {
    Route::post('reels/{reel}/reaction', [ReelController::class, 'toggleLike']);  // يحتاج توثيق
    Route::post('reels/{reelId}/increment-share', [ReelController::class, 'incrementShare']); // يحتاج توثيق
});
Route::get('/google/authenticate', [GoogleController::class, 'getLogindataUsingGoogleCode']);





Route::middleware(['cors', 'auth:api'])->group(function () {
   Route::middleware(['is.admin'])->get('/userauths/count', [UserauthController::class, 'countUsers']);
   Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
   Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);
   Route::post('/notifications/test', [NotificationController::class, 'testNotification']);
Route::post('/track-exit', [VisitorLogController::class, 'trackExit']);
});
