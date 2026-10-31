<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class RiderAllocationService
{
    protected FareCalculationService $fareService;

    protected DriverRouteService $routeService;

    protected RouteDistanceService $routeDistanceService;


    public function __construct(
        FareCalculationService $fareService,
        DriverRouteService $routeService,
        RouteDistanceService $routeDistanceService
    ) {
        $this->fareService =
            $fareService;

        $this->routeService =
            $routeService;

        $this->routeDistanceService =
            $routeDistanceService;
    }


    /*
    |--------------------------------------------------------------------------
    | Find Best Driver Trip
    |--------------------------------------------------------------------------
    */

    public function allocateBestTrip(
        int $tripRiderId
    ): array {

        try {

            /*
            |--------------------------------------------------------------------------
            | Pending Rider Request
            |--------------------------------------------------------------------------
            */

            $request =
                DB::table(
                    'trip_riders'
                )

                ->where(
                    'id',
                    $tripRiderId
                )

                ->where(
                    'allocation_status',
                    'pending'
                )

                ->whereNull(
                    'trip_id'
                )

                ->first();


            if (!$request) {

                return [
                    'status' => false,
                    'message' =>
                        'Pending rider request not found.',
                ];
            }


            if (
                !$request->requested_trip_date
                ||
                !$request->requested_direction
            ) {

                return [
                    'status' => false,
                    'message' =>
                        'Trip date or direction missing.',
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Candidate Trips
            |--------------------------------------------------------------------------
            */

            $query =
                DB::table('trips')

                    ->where(
                        'trip_date',
                        $request
                            ->requested_trip_date
                    )

                    ->where(
                        'direction',
                        $request
                            ->requested_direction
                    )

                    ->where(
                        'status',
                        'scheduled'
                    )

                    ->whereNotNull(
                        'route_polyline'
                    );


            /*
            |--------------------------------------------------------------------------
            | Same Workplace
            |--------------------------------------------------------------------------
            */

            if (
                $request
                    ->requested_direction
                === 'home_to_work'
            ) {

                $query->where(
                    'destination_address',
                    $request
                        ->dropoff_address
                );

            } else {

                $query->where(
                    'start_address',
                    $request
                        ->pickup_address
                );
            }


            $trips =
                $query->get();


            if ($trips->isEmpty()) {

                return [
                    'status' => false,
                    'message' =>
                        'No matching driver trip found.',
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Allocation Window - 4 Hours Before Trip
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | Trip Time = 18:00
            |
            | 13:50 -> Not Eligible
            | 14:00 -> Eligible
            | 15:00 -> Eligible
            | 17:50 -> Eligible
            | 18:00 -> Not Eligible
            |
            */

            $now =
                Carbon::now(
                    config('app.timezone')
                );


            $trips =
                $trips
                    ->filter(
                        function ($trip) use ($now) {

                            /*
                            * Create complete trip datetime
                            */

                            $tripDateTime =
                                Carbon::createFromFormat(
                                    'Y-m-d H:i:s',
                                    $trip->trip_date
                                    . ' '
                                    . $trip->departure_time,
                                    config('app.timezone')
                                );


                            /*
                            * Allocation starts 4 hours
                            * before trip departure.
                            */

                            $allocationStartTime =
                                $tripDateTime
                                    ->copy()
                                    ->subHours(4);


                            /*
                            * Eligible when:
                            *
                            * now >= trip time - 4 hours
                            *
                            * AND
                            *
                            * now < trip time
                            */

                            return
                                $now->greaterThanOrEqualTo(
                                    $allocationStartTime
                                )
                                &&
                                $now->lessThan(
                                    $tripDateTime
                                );
                        }
                    )
                    ->values();


            /*
            |--------------------------------------------------------------------------
            | No Trip Inside Allocation Window
            |--------------------------------------------------------------------------
            */

            if ($trips->isEmpty()) {

                return [
                    'status' => false,
                    'message' =>
                        'Matching trip found, but allocation will start only within 4 hours of trip time.',
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Candidate Ranking
            |--------------------------------------------------------------------------
            */

            $candidates = [];


            foreach (
                $trips as $trip
            ) {

                /*
                 * Pending with trip_id means seat
                 * is already temporarily reserved.
                 */

                $filledSeats =
                    DB::table(
                        'trip_riders'
                    )

                    ->where(
                        'trip_id',
                        $trip->id
                    )

                    ->whereIn(
                        'allocation_status',
                        [
                            'pending',
                            'allocated',
                            'finalized',
                        ]
                    )

                    ->where(
                        'booking_status',
                        '!=',
                        'cancelled'
                    )

                    ->count();


                if (
                    $filledSeats >=
                    (int)
                    $trip
                        ->available_seats
                ) {

                    continue;
                }


                $pickupDistance =
                    $this
                        ->routeDistanceService
                        ->distanceFromPolyline(

                            (float)
                            $request
                                ->pickup_latitude,

                            (float)
                            $request
                                ->pickup_longitude,

                            $trip
                                ->route_polyline
                        );


                if (
                    !$pickupDistance[
                        'status'
                    ]
                ) {

                    continue;
                }


                $candidates[] = [

                    'trip_id' =>
                        (int)
                        $trip->id,

                    'pickup_distance' =>
                        (float)
                        $pickupDistance[
                            'distance_miles'
                        ],
                ];
            }


            if (empty($candidates)) {

                return [
                    'status' => false,
                    'message' =>
                        'No driver with available seats found.',
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Closest Route First
            |--------------------------------------------------------------------------
            */

            usort(
                $candidates,

                function (
                    array $a,
                    array $b
                ) {

                    return
                        $a[
                            'pickup_distance'
                        ]
                        <=>
                        $b[
                            'pickup_distance'
                        ];
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Try Candidate Trips
            |--------------------------------------------------------------------------
            */

            foreach (
                $candidates
                as $candidate
            ) {

                DB::beginTransaction();


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Driver Trip
                    |--------------------------------------------------------------------------
                    */

                    $trip =
                        DB::table('trips')

                            ->where(
                                'id',
                                $candidate[
                                    'trip_id'
                                ]
                            )

                            ->where(
                                'status',
                                'scheduled'
                            )

                            ->lockForUpdate()

                            ->first();


                    if (!$trip) {

                        DB::rollBack();

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Recheck Seats
                    |--------------------------------------------------------------------------
                    */

                    $filledSeats =
                        DB::table(
                            'trip_riders'
                        )

                        ->where(
                            'trip_id',
                            $trip->id
                        )

                        ->whereIn(
                            'allocation_status',
                            [
                                'pending',
                                'allocated',
                                'finalized',
                            ]
                        )

                        ->where(
                            'booking_status',
                            '!=',
                            'cancelled'
                        )

                        ->count();


                    if (
                        $filledSeats >=
                        (int)
                        $trip
                            ->available_seats
                    ) {

                        DB::rollBack();

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Lock Pending Rider Request
                    |--------------------------------------------------------------------------
                    */

                    $pendingRequest =
                        DB::table(
                            'trip_riders'
                        )

                        ->where(
                            'id',
                            $tripRiderId
                        )

                        ->where(
                            'allocation_status',
                            'pending'
                        )

                        ->whereNull(
                            'trip_id'
                        )

                        ->lockForUpdate()

                        ->first();


                    if (!$pendingRequest) {

                        DB::rollBack();

                        return [
                            'status' => false,
                            'message' =>
                                'Rider request already processed.',
                        ];
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Stop Order
                    |--------------------------------------------------------------------------
                    */

                    $lastStopOrder =
                        DB::table(
                            'trip_riders'
                        )

                        ->where(
                            'trip_id',
                            $trip->id
                        )

                        ->max(
                            'stop_order'
                        );


                    $stopOrder =
                        ((int)
                            $lastStopOrder
                        )
                        + 1;


                    /*
                    |--------------------------------------------------------------------------
                    | Assign Trip
                    |--------------------------------------------------------------------------
                    */

                    DB::table(
                        'trip_riders'
                    )

                    ->where(
                        'id',
                        $tripRiderId
                    )

                    ->update([

                        'trip_id' =>
                            $trip->id,

                        'stop_order' =>
                            $stopOrder,

                        'updated_at' =>
                            now(),
                    ]);


                    DB::commit();


                    /*
                    |--------------------------------------------------------------------------
                    | Calculate Distance + Fare
                    |--------------------------------------------------------------------------
                    */

                    $allocation =
                        $this
                            ->allocate(
                                $tripRiderId
                            );


                    if (
                        !$allocation[
                            'status'
                        ]
                    ) {

                        /*
                         * Reset rider request
                         * if Google/fare calculation fails.
                         */

                        DB::table(
                            'trip_riders'
                        )

                        ->where(
                            'id',
                            $tripRiderId
                        )

                        ->update([

                            'trip_id' =>
                                null,

                            'stop_order' =>
                                0,

                            'allocation_status' =>
                                'pending',

                            'allocated_at' =>
                                null,

                            'updated_at' =>
                                now(),
                        ]);


                        return $allocation;
                    }


                    return [

                        'status' =>
                            true,

                        'message' =>
                            'Rider allocated successfully.',

                        'trip_rider_id' =>
                            $tripRiderId,

                        'trip_id' =>
                            $allocation[
                                'trip_id'
                            ],

                        'driver_id' =>
                            $allocation[
                                'driver_id'
                            ],

                        'pickup_distance_from_route' =>
                            $allocation[
                                'pickup_distance_from_route'
                            ],

                        'rider_distance_miles' =>
                            $allocation[
                                'rider_distance_miles'
                            ],

                        'fare_level_no' =>
                            $allocation[
                                'fare_level_no'
                            ],

                        'fare_amount' =>
                            $allocation[
                                'fare_amount'
                            ],
                    ];


                } catch (Throwable $e) {

                    if (
                        DB::transactionLevel()
                        > 0
                    ) {

                        DB::rollBack();
                    }


                    Log::error(
                        'Candidate rider allocation failed',
                        [
                            'trip_rider_id' =>
                                $tripRiderId,

                            'trip_id' =>
                                $candidate[
                                    'trip_id'
                                ],

                            'message' =>
                                $e->getMessage(),
                        ]
                    );
                }
            }


            return [
                'status' => false,
                'message' =>
                    'No available driver could be allocated.',
            ];


        } catch (Throwable $e) {

            if (
                DB::transactionLevel()
                > 0
            ) {

                DB::rollBack();
            }


            Log::error(
                'Best trip allocation failed',
                [
                    'trip_rider_id' =>
                        $tripRiderId,

                    'message' =>
                        $e->getMessage(),
                ]
            );


            return [
                'status' => false,
                'message' =>
                    'Unable to allocate rider.',

                'error' =>
                    $e->getMessage(),
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Rider Distance + Fare
    |--------------------------------------------------------------------------
    */

    public function allocate(
        int $tripRiderId
    ): array {

        try {

            $tripRider =
                DB::table(
                    'trip_riders as tr'
                )

                ->join(
                    'trips as t',
                    't.id',
                    '=',
                    'tr.trip_id'
                )

                ->where(
                    'tr.id',
                    $tripRiderId
                )

                ->select(

                    'tr.*',

                    't.id as driver_trip_id',

                    't.driver_id',

                    't.estimated_distance as driver_route_distance',

                    't.estimated_duration_minutes as driver_route_duration',

                    't.route_polyline'
                )

                ->first();


            if (!$tripRider) {

                return [
                    'status' => false,
                    'message' =>
                        'Trip rider not found.',
                ];
            }


            if (
                $tripRider
                    ->pickup_latitude
                === null
                ||
                $tripRider
                    ->pickup_longitude
                === null
                ||
                $tripRider
                    ->dropoff_latitude
                === null
                ||
                $tripRider
                    ->dropoff_longitude
                === null
            ) {

                return [
                    'status' => false,
                    'message' =>
                        'Rider pickup/dropoff coordinates not found.',
                ];
            }


            if (
                empty(
                    $tripRider
                        ->route_polyline
                )
            ) {

                return [
                    'status' => false,
                    'message' =>
                        'Driver route polyline not found.',
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Pickup Distance From Driver Route
            |--------------------------------------------------------------------------
            */

            $pickupRouteDistance =
                $this
                    ->routeDistanceService
                    ->distanceFromPolyline(

                        (float)
                        $tripRider
                            ->pickup_latitude,

                        (float)
                        $tripRider
                            ->pickup_longitude,

                        $tripRider
                            ->route_polyline
                    );


            if (
                !$pickupRouteDistance[
                    'status'
                ]
            ) {

                return
                    $pickupRouteDistance;
            }


            /*
            |--------------------------------------------------------------------------
            | Rider Pickup -> Dropoff Google Route
            |--------------------------------------------------------------------------
            */

            $riderRoute =
                $this
                    ->routeService
                    ->calculate(

                        (float)
                        $tripRider
                            ->pickup_latitude,

                        (float)
                        $tripRider
                            ->pickup_longitude,

                        (float)
                        $tripRider
                            ->dropoff_latitude,

                        (float)
                        $tripRider
                            ->dropoff_longitude
                    );


            if (
                !$riderRoute[
                    'status'
                ]
            ) {

                return $riderRoute;
            }


            /*
            |--------------------------------------------------------------------------
            | Fare
            |--------------------------------------------------------------------------
            */

            $fare =
                $this
                    ->fareService
                    ->calculate(

                        (float)
                        $riderRoute[
                            'distance_miles'
                        ]
                    );


            if (
                !$fare[
                    'status'
                ]
            ) {

                return $fare;
            }


            /*
            |--------------------------------------------------------------------------
            | Update Rider Allocation
            |--------------------------------------------------------------------------
            */

            DB::table(
                'trip_riders'
            )

            ->where(
                'id',
                $tripRiderId
            )

            ->update([

                'distance_miles' =>
                    $fare[
                        'distance_miles'
                    ],

                'pickup_distance_from_route' =>
                    $pickupRouteDistance[
                        'distance_miles'
                    ],

                'fare_amount' =>
                    $fare[
                        'fare_amount'
                    ],

                'fare_level_no' =>
                    $fare[
                        'level_no'
                    ],

                'fare_base_price' =>
                    $fare[
                        'base_price'
                    ],

                'fare_price_per_mile' =>
                    $fare[
                        'price_per_mile'
                    ],

                'allocation_status' =>
                    'allocated',

                'allocation_type' =>
                    'auto',

                'allocated_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);


            return [

                'status' =>
                    true,

                'message' =>
                    'Rider allocation calculated successfully.',

                'trip_rider_id' =>
                    $tripRiderId,

                'trip_id' =>
                    $tripRider
                        ->driver_trip_id,

                'driver_id' =>
                    $tripRider
                        ->driver_id,

                'driver_route_distance_miles' =>
                    $tripRider
                        ->driver_route_distance,

                'pickup_distance_from_route' =>
                    $pickupRouteDistance[
                        'distance_miles'
                    ],

                'rider_distance_miles' =>
                    $fare[
                        'distance_miles'
                    ],

                'rider_duration_minutes' =>
                    $riderRoute[
                        'duration_minutes'
                    ]
                    ?? null,

                'fare_level_no' =>
                    $fare[
                        'level_no'
                    ],

                'fare_level_name' =>
                    $fare[
                        'level_name'
                    ],

                'base_price' =>
                    $fare[
                        'base_price'
                    ],

                'price_per_mile' =>
                    $fare[
                        'price_per_mile'
                    ],

                'fare_amount' =>
                    $fare[
                        'fare_amount'
                    ],
            ];


        } catch (Throwable $e) {

            Log::error(
                'Rider allocation calculation failed',
                [
                    'trip_rider_id' =>
                        $tripRiderId,

                    'message' =>
                        $e->getMessage(),
                ]
            );


            return [
                'status' => false,

                'message' =>
                    'Rider allocation calculation failed.',

                'error' =>
                    $e->getMessage(),
            ];
        }
    }
}