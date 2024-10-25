<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RatingController;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->namespace('App\Http\Controllers\Profile')->middleware('auth:api')->group(function () {
    Route::middleware('auth.required')->group(function () {
        Route::get('settings', 'SettingsController@index');
        Route::post('upload-avatar', 'SettingsController@uploadAvatar');
    });
    Route::get('{name}', 'ShowController');
});

Route::controller(HomeController::class)->group(function () {
    Route::get('home/{limit?}', 'index');
});

Route::prefix('dashboard')->controller(DashboardController::class)->middleware(['auth:api', 'auth.required'])->group(function () {
    Route::get('spheres', 'index');
    Route::post('create', 'create')->middleware('throttle:5,1');
    Route::post('edit', 'edit');
});

Route::controller(ExploreController::class)->middleware(['auth:api'])->prefix('explore')->group(function () {
    Route::get('show/{sphere}', 'show');
    Route::get('index', 'index');
});

Route::post('react/{sphere}/{type}', [RatingController::class, 'reaction'])
    ->middleware(['auth:api', 'auth.required'])
    ->where('type', 'like|dislike');

Route::namespace('App\Http\Controllers\Auth')->prefix('auth')->group(function () {

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('login', 'LoginController')->name('login');
        Route::post('register', 'RegisterController')->name('register');
    });

    Route::middleware(['auth:api', 'auth.required'])->group(function () {
        Route::get('details', 'DetailsController')->name('details');

        Route::prefix('email')->group(function () {
            Route::post('verification-notification', 'EmailController@sendVerificationEmail');
            Route::get('verify/{id}/{hash}', 'EmailController@verify')
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');
        });

        Route::post('logout', 'LogoutController')
            ->name('logout');
    });

    Route::get('register/rules', function () {

        $rules = (new RegisterRequest)->rules();

        return response()->json($rules);
    });
});
