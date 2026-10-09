<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\SubscriptionPlanController;

/*
|--------------------------------------------------------------------------
| Public APIs
|--------------------------------------------------------------------------
*/

Route::get('/subscription-plans', [
    SubscriptionPlanController::class,
    'index'
]);

Route::get('/payment-methods', [
    SubscriptionPlanController::class,
    'paymentMethods'
]);

Route::post('/register', [
    RegisterController::class,
    'register'
]);

/*
|--------------------------------------------------------------------------
| Authenticated APIs
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');