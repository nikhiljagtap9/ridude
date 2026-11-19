<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\DriverTripController;
use App\Http\Controllers\Api\RiderTripController;
use App\Http\Controllers\Api\WorkplaceController;

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

     Route::get(
        '/workplaces',
        [WorkplaceController::class, 'index']
    );

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
            '/trip/start',
            [DriverTripController::class, 'startTrip']
        );

        Route::post(
            '/trip/rider/pickup',
            [DriverTripController::class, 'pickupRider']
        );

        Route::post(
            '/trip/rider/dropoff',
            [DriverTripController::class, 'dropoffRider']
        );

        Route::post(
            '/trip/complete',
            [DriverTripController::class, 'completeTrip']
        );

        Route::get(
            '/earnings',
            [DriverTripController::class, 'earnings']
        );

        Route::get(
            '/profile',
            [DriverTripController::class, 'showProfile']
        );

        Route::post(
            '/profile',
            [DriverTripController::class, 'updateProfile']
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
            '/my-trips',
            [RiderTripController::class, 'myTrips']
        );

        Route::post(
            '/trip-detail',
            [RiderTripController::class, 'tripDetail']
        );

        Route::post(
            '/trips/{tripId}/cancel',
            [RiderTripController::class, 'cancel']
        );

        Route::post(
            '/ride-request',
            [
                RiderTripController::class,
                'requestRide'
            ]
        );

        Route::get(
            '/profile',
            [RiderTripController::class, 'showProfile']
        );

        Route::post(
            '/profile',
            [RiderTripController::class, 'updateProfile']
        );
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');