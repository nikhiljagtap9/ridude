<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RiderController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\WorkplaceController;
use App\Http\Controllers\Admin\TripTransactionController;
use App\Http\Controllers\Admin\FareCalculationController;



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
            '/riders/{id}/details',
            [RiderController::class, 'details']
        )->name('riders.details');

        Route::get(
            '/drivers',
            [DriverController::class, 'index']
        )->name('drivers.index');

        Route::get(
            '/drivers/{id}/details',
            [DriverController::class, 'details']
        )->name('drivers.details');

        Route::post(
            '/drivers/{id}/status',
            [DriverController::class, 'updateStatus']
        )->name('drivers.status');

        Route::get(
            '/workplaces',
            [WorkplaceController::class, 'index']
        )->name('workplaces.index');

        Route::post(
            '/workplaces',
            [WorkplaceController::class, 'store']
        )->name('workplaces.store');

        Route::post(
            '/workplaces/{id}/branches',
            [WorkplaceController::class, 'storeBranch']
        )->name('workplaces.branches.store');

        Route::delete(
            '/workplace-branches/{id}',
            [WorkplaceController::class, 'deleteBranch']
        )->name('workplaces.branches.delete');

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
            '/payment-methods/{id}/update',
            [PaymentMethodController::class, 'update']
        )->name('payment-methods.update');

        Route::delete(
            '/payment-methods/{id}',
            [PaymentMethodController::class, 'destroy']
        )->name('payment-methods.destroy');

        Route::get(
            '/fare-calculations',
            [FareCalculationController::class, 'index']
        )->name('fare-calculations.index');

        // Route::post(
        //     '/fare-calculations',
        //     [FareCalculationController::class, 'store']
        // )->name('fare-calculations.store');

        Route::post(
            '/fare-calculations/{id}/update',
            [FareCalculationController::class, 'update']
        )->name('fare-calculations.update');

        Route::get(
            '/trips',
            [TripController::class, 'index']
        )->name('trips.index');

        Route::get(
            '/trip-transactions',
            [TripTransactionController::class, 'index']
        )->name('trip-transactions.index');
    

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        )->name('logout');

    });