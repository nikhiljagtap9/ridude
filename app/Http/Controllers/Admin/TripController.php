<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([

            'driver_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'rider_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'period' => [
                'nullable',
                'in:daily,weekly,monthly',
            ],

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Period Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('period')) {

            if ($request->period === 'daily') {

                $request->merge([
                    'from' => now()->toDateString(),
                    'to' => now()->toDateString(),
                ]);

            } elseif ($request->period === 'weekly') {

                $request->merge([
                    'from' =>
                        now()
                            ->startOfWeek()
                            ->toDateString(),

                    'to' =>
                        now()
                            ->endOfWeek()
                            ->toDateString(),
                ]);

            } elseif ($request->period === 'monthly') {

                $request->merge([
                    'from' =>
                        now()
                            ->startOfMonth()
                            ->toDateString(),

                    'to' =>
                        now()
                            ->endOfMonth()
                            ->toDateString(),
                ]);
            }
        }


        $query =
            $this->tripQuery(
                $request
            );


        $totalTrips =
            (clone $query)
                ->count();


        $trips =
            $query
                ->orderByDesc('t.trip_date')
                ->orderByDesc('t.id')
                ->paginate(20)
                ->withQueryString();


        $drivers =
            DB::table('users')
                ->where('role', 'driver')
                ->orderBy('full_name')
                ->select(
                    'id',
                    'full_name'
                )
                ->get();


        $riders =
            DB::table('users')
                ->where('role', 'rider')
                ->orderBy('full_name')
                ->select(
                    'id',
                    'full_name'
                )
                ->get();


        return view(
            'admin.trips.index',
            compact(
                'trips',
                'totalTrips',
                'drivers',
                'riders'
            )
        );
    }

    private function tripQuery(Request $request)
    {
        $query =
            DB::table('trip_riders as tr')

            ->join(
                'trips as t',
                't.id',
                '=',
                'tr.trip_id'
            )

            ->join(
                'users as rider',
                'rider.id',
                '=',
                'tr.rider_id'
            )

            ->join(
                'users as driver',
                'driver.id',
                '=',
                't.driver_id'
            )

            ->select(
                'tr.id as trip_rider_id',

                't.id as trip_id',
                't.trip_date',
                't.direction',
                't.start_address',
                't.destination_address',
                't.status as trip_status',

                'tr.rider_id',
                'tr.booking_status',
                'tr.ride_status',
                'tr.fare_amount',

                'rider.full_name as rider_name',

                'driver.id as driver_id',
                'driver.full_name as driver_name'
            );


        /*
        |--------------------------------------------------------------------------
        | Driver Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('driver_id')) {

            $query->where(
                't.driver_id',
                $request->driver_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Rider Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('rider_id')) {

            $query->where(
                'tr.rider_id',
                $request->rider_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | From Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from')) {

            $query->whereDate(
                't.trip_date',
                '>=',
                $request->from
            );
        }


        /*
        |--------------------------------------------------------------------------
        | To Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('to')) {

            $query->whereDate(
                't.trip_date',
                '<=',
                $request->to
            );
        }


        return $query;
    }

    public function exportCsv(Request $request)
    {
        $request->validate([

            'driver_id' => [
                'nullable',
                'integer',
            ],

            'rider_id' => [
                'nullable',
                'integer',
            ],

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
            ],
        ]);


        $trips =
            $this->tripQuery(
                $request
            )

            ->orderByDesc(
                't.trip_date'
            )

            ->orderByDesc(
                't.id'
            )

            ->get();


        $fileName =
            'trips-'
            . now()->format(
                'Y-m-d-His'
            )
            . '.csv';


        return response()->streamDownload(
            function () use ($trips) {

                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );


                /*
                |--------------------------------------------------------------------------
                | CSV Header
                |--------------------------------------------------------------------------
                */

                fputcsv(
                    $handle,
                    [
                        'Trip ID',
                        'Date',
                        'Direction',
                        'Driver',
                        'Rider',
                        'Start Address',
                        'Destination Address',
                        'Fare',
                        'Trip Status',
                        'Booking Status',
                        'Ride Status',
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CSV Rows
                |--------------------------------------------------------------------------
                */

                foreach ($trips as $trip) {

                    fputcsv(
                        $handle,
                        [
                            'TRP-' . str_pad(
                                $trip->trip_id,
                                5,
                                '0',
                                STR_PAD_LEFT
                            ),

                            $trip->trip_date,

                            $trip->direction,

                            $trip->driver_name,

                            $trip->rider_name,

                            $trip->start_address,

                            $trip->destination_address,

                            number_format(
                                (float)
                                $trip->fare_amount,
                                2,
                                '.',
                                ''
                            ),

                            $trip->trip_status,

                            $trip->booking_status,

                            $trip->ride_status,
                        ]
                    );
                }


                fclose(
                    $handle
                );
            },

            $fileName,

            [
                'Content-Type' =>
                    'text/csv',

                'Content-Disposition' =>
                    'attachment; filename="'
                    . $fileName
                    . '"',
            ]
        );
    }
}