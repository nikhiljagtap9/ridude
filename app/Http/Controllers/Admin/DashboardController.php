<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $registeredRiders = User::where(
            'role',
            'rider'
        )->count();


        $registeredDrivers = User::where(
            'role',
            'driver'
        )->count();


        $approvedDrivers = User::where(
                'role',
                'driver'
            )
            ->where(
                'status',
                'approved'
            )
            ->count();


        $pendingDrivers = User::where(
                'role',
                'driver'
            )
            ->where(
                'status',
                'under_review'
            )
            ->count();


        $tripsToday = DB::table('trips')
            ->whereDate(
                'trip_date',
                today()
            )
            ->count();


        $totalTrips = DB::table('trips')
            ->count();


        $subscriptionRevenue =
            DB::table('user_subscriptions')
                ->whereIn(
                    'status',
                    [
                        'active',
                        'trial'
                    ]
                )
                ->sum('price');


        $pendingDriverList = User::where(
                'users.role',
                'driver'
            )
            ->where(
                'users.status',
                'under_review'
            )

            ->leftJoin(
                'driver_vehicles',
                'driver_vehicles.user_id',
                '=',
                'users.id'
            )

            ->select([
                'users.id',
                'users.full_name',

                'driver_vehicles.car_make',
                'driver_vehicles.car_model',
                'driver_vehicles.license_plate_number',
            ])

            ->latest('users.created_at')

            ->limit(5)

            ->get();


        return view(
            'admin.dashboard',
            compact(
                'registeredRiders',
                'registeredDrivers',
                'approvedDrivers',
                'pendingDrivers',
                'tripsToday',
                'totalTrips',
                'subscriptionRevenue',
                'pendingDriverList'
            )
        );
    }
}