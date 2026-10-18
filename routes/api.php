<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\DriverTripController;
use App\Http\Controllers\Api\RiderTripController;

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
    AuthController::class,
    'register'
]);

Route::post('/login', [
    AuthController::class,
    'login'
]);



/*
|--------------------------------------------------------------------------
| Authenticated APIs
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

});

Route::middleware('auth:sanctum')
    ->prefix('driver')
    ->group(function () {

        Route::post(
            '/commute-plan',
            [DriverTripController::class, 'saveCommutePlan']
        );

        Route::get(
            '/commute-plan',
            [DriverTripController::class, 'getCommutePlan']
        );

        Route::get(
            '/my-trip',
            [DriverTripController::class, 'myTrip']
        );

        Route::post(
            '/trips/{tripId}/start',
            [DriverTripController::class, 'startTrip']
        );

        Route::post(
            '/trips/{tripId}/riders/{tripRiderId}/pickup',
            [DriverTripController::class, 'pickupRider']
        );

        Route::post(
            '/trips/{tripId}/riders/{tripRiderId}/dropoff',
            [DriverTripController::class, 'dropoffRider']
        );

        Route::post(
            '/trips/{tripId}/complete',
            [DriverTripController::class, 'completeTrip']
        );
});



Route::middleware('auth:sanctum')
    ->prefix('rider')
    ->group(function () {

        Route::get(
            '/home',
            [RiderTripController::class, 'home']
        );

        Route::get(
            '/available-trips',
            [RiderTripController::class, 'availableTrips']
        );

        Route::post(
            '/trips/{tripId}/reserve',
            [RiderTripController::class, 'reserve']
        );

        Route::get(
            '/my-trips',
            [RiderTripController::class, 'myTrips']
        );

        Route::get(
            '/trips/{tripId}',
            [RiderTripController::class, 'tripDetail']
        );

        Route::post(
            '/trips/{tripId}/cancel',
            [RiderTripController::class, 'cancel']
        );
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');