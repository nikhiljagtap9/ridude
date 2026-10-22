<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');


        /*
        |--------------------------------------------------------------------------
        | Trip Records Query
        |--------------------------------------------------------------------------
        |
        | One trip can contain multiple riders, therefore trip_riders is used
        | for the Rider/Fare record.
        |
        */

        $query = DB::table('trip_riders')

            ->join(
                'trips',
                'trips.id',
                '=',
                'trip_riders.trip_id'
            )

            ->join(
                'users as riders',
                'riders.id',
                '=',
                'trip_riders.rider_id'
            )

            ->join(
                'users as drivers',
                'drivers.id',
                '=',
                'trips.driver_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        if ($from) {

            $query->whereDate(
                'trips.trip_date',
                '>=',
                $from
            );
        }


        if ($to) {

            $query->whereDate(
                'trips.trip_date',
                '<=',
                $to
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Records
        |--------------------------------------------------------------------------
        */

        $trips = $query
            ->select([
                'trip_riders.id as trip_rider_id',
                'trip_riders.trip_id',

                'trips.trip_date',
                'trips.direction',
                'trips.start_address',
                'trips.destination_address',
                'trips.status as trip_status',

                'trip_riders.fare_amount',
                'trip_riders.booking_status',
                'trip_riders.ride_status',

                'riders.full_name as rider_name',
                'drivers.full_name as driver_name',
            ])

            ->orderByDesc('trips.trip_date')
            ->orderByDesc('trip_riders.id')

            ->paginate(20)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Total Filtered Records
        |--------------------------------------------------------------------------
        */

        $totalTrips = DB::table('trip_riders')
            ->join(
                'trips',
                'trips.id',
                '=',
                'trip_riders.trip_id'
            )

            ->when(
                $from,
                function ($q) use ($from) {

                    $q->whereDate(
                        'trips.trip_date',
                        '>=',
                        $from
                    );
                }
            )

            ->when(
                $to,
                function ($q) use ($to) {

                    $q->whereDate(
                        'trips.trip_date',
                        '<=',
                        $to
                    );
                }
            )

            ->count();


        return view(
            'admin.trips.index',
            compact(
                'trips',
                'totalTrips',
                'from',
                'to'
            )
        );
    }
}