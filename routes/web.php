<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RiderController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\PaymentMethodController;


/*
|--------------------------------------------------------------------------
| Admin Guest Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get(
            '/login',
            [AuthController::class, 'loginForm']
        )->name('login');


        Route::post(
            '/login',
            [AuthController::class, 'login']
        )->name('login.submit');

    });


/*
|--------------------------------------------------------------------------
| Admin Protected Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware('admin')
    ->group(function () {

        Route::get(
            '/dashboard',
            [DashboardController::class, 'index']
        )->name('dashboard');

        Route::get(
            '/riders',
            [RiderController::class, 'index']
        )->name('riders.index');

        Route::get(
            '/drivers',
            [DriverController::class, 'index']
        )->name('drivers.index');

        Route::post(
            '/drivers/{id}/status',
            [DriverController::class, 'updateStatus']
        )->name('drivers.status');

         Route::get(
            '/payment-methods',
            [PaymentMethodController::class, 'index']
        )->name('payment-methods.index');

        Route::post(
            '/payment-methods',
            [PaymentMethodController::class, 'store']
        )->name('payment-methods.store');

        Route::post(
            '/payment-methods/{id}/toggle',
            [PaymentMethodController::class, 'toggle']
        )->name('payment-methods.toggle');

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        )->name('logout');

    });