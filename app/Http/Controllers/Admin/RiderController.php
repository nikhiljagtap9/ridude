<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('users')
            ->leftJoin(
                'rider_profiles',
                'rider_profiles.user_id',
                '=',
                'users.id'
            )

            ->leftJoinSub(
                DB::table('trip_riders')
                    ->select(
                        'rider_id',
                        DB::raw('COUNT(*) as total_trips')
                    )
                    ->where(
                        'booking_status',
                        'completed'
                    )
                    ->groupBy('rider_id'),
                'trip_count',
                function ($join) {
                    $join->on(
                        'trip_count.rider_id',
                        '=',
                        'users.id'
                    );
                }
            )

            ->where(
                'users.role',
                'rider'
            );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('q')) {

            $search = trim($request->q);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'users.full_name',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'users.email',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'users.phone_number',
                    'like',
                    '%' . $search . '%'
                );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Get Riders
        |--------------------------------------------------------------------------
        */

        $riders = $query
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.country_code',
                'users.phone_number',
                'users.status',
                'users.created_at',

                'rider_profiles.address',
                'rider_profiles.profile_photo',
                'rider_profiles.verification_status',

                DB::raw(
                    'COALESCE(trip_count.total_trips, 0) as total_trips'
                ),
            ])

            ->orderByDesc(
                'users.created_at'
            )

            ->paginate(20)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Total Rider Count
        |--------------------------------------------------------------------------
        */

        $totalRiders = DB::table('users')
            ->where(
                'role',
                'rider'
            )
            ->count();


        if ($request->ajax()) {
            return view(
                    'admin.riders.partials.list',
                    compact('riders')
                );
        }

        return view(
            'admin.riders.index',
            compact(
                'riders',
                'totalRiders'
            )
        );
    }
}