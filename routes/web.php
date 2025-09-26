<?php

use App\Http\Controllers\AdController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CityController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryFieldController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CarModelController;
use App\Http\Controllers\GoogleController;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ]
    ], function(){

        Auth::routes();

        Route::group(['middleware' => 'guest'],function(){
            Route::get('/', function () {
                return view('auth.login');
            });
        });
            Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
            Route::resource('banners',BannerController::class);
            Route::resource('categorys',CategoryController::class);
            Route::resource('countrys',CountryController::class);
            Route::resource('citys',CityController::class);
                  Route::resource('blogs',BlogController::class);

                  Route::get('/car-models', [CarModelController::class, 'index'])->name('carModel.index');

                  // Route to delete a car model
Route::delete('/car-models/{id}', [CarModelController::class, 'destroy'])->name('carModel.delete');

 Route::delete('/category-field-values/{valueId}/delete', [CategoryFieldController::class, 'deleteValue']);
            Route::get('/categories/{id}/fields/create', [CategoryFieldController::class, 'create'])->name('categories.fields.create'); // إضافة
            Route::post('/categories/{id}/fields/store', [CategoryFieldController::class, 'store'])->name('categories.fields.store'); // تخزين
            Route::get('/categories/{id}/fields', [CategoryFieldController::class, 'show'])->name('categories.fields.show');

// إضافة مسار لحفظ الموديلات
Route::post('/categories/{category}/fields/store-car-model', [CategoryFieldController::class, 'storeCarModel'])
    ->name('categories.fields.store-car-model');

            Route::get('/categories/{id}/fields/{field_id}/edit', [CategoryFieldController::class, 'edit'])->name('categories.fields.edit'); // تعديل
            Route::post('/categories/{id}/fields/{field_id}/update', [CategoryFieldController::class, 'update'])->name('categories.fields.update'); // تحديث

            Route::delete('/categories/{id}/fields/{field_id}/destroy', [CategoryFieldController::class, 'destroy'])->name('categories.fields.destroy'); // حذف
            Route::get('/ads-management', [AdController::class, 'index'])->name('ads.management');
            Route::put('/ads-management/{id}', [AdController::class, 'updateStatus'])->name('ads.updateStatus');
            Route::delete('/ads/{id}', [AdController::class, 'destroy'])->name('ads.destroy');
      
      
      Route::post('categories/{category}/fields/ensureExists', [CategoryFieldController::class, 'ensureExists'])->name('categories.fields.ensureExists');

Route::post('/categories/{categoryId}/toggle-kilometers', [CategoryController::class, 'toggleKilometers'])->name('categories.toggleKilometers');
});

// Handle preflight OPTIONS requests for CORS routes
Route::options('auth/google', function () { return response('', 200); });
Route::options('auth/google/callback', function () { return response('', 200); });
Route::options('google/authenticate', function () { return response('', 200); });

// Your CORS routes
Route::middleware('cors')->group(function () {
    Route::get('auth/google', [GoogleController::class, 'googlepage']);
    Route::get('auth/google/callback', [GoogleController::class, 'googlecallback']);
    Route::get('/google/authenticate', [GoogleController::class, 'getLogindataUsingGoogleCode']);
});