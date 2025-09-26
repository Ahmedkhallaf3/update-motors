<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Home\HomeController;
use Illuminate\Support\Facades\Route;
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



        Route::prefix('admin')->name('admin.')->group(function(){


            /**
             * -------------------------------------------------
             * Start Authentication Routes
             * -------------------------------------------------
             */

                Route::get('login',[LoginController::class,'showLoginForm']);
                Route::post('login',[LoginController::class,'login'])->name('login');
                Route::post('logout',[LoginController::class,'logout'])->name('logout');

            /**
             * -------------------------------------------------
             * End Authentication Routes
             * -------------------------------------------------
             */

            Route::middleware('auth')->group(function(){

                /**
                 * -------------------------------------------------
                 * Start Home Routes
                 * -------------------------------------------------
                 */

                    Route::get('/home', [HomeController::class,'index'])->name('home');

                /**
                 * -------------------------------------------------
                 * End Home Routes
                 * -------------------------------------------------
                 */
            });

        });

});
