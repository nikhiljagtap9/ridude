<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DriverRouteService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DriverTripController extends Controller
{
    /*
     * 
     * direction = home_to_work
        → shift_start_time required
        → shift_end_time NOT required
        → only home_to_work route calculate
        → only home_to_work trip create

        direction = work_to_home
        → shift_end_time required
        → shift_start_time NOT required
        → only work_to_home route calculate
        → only work_to_home trip create

        direction = both
        → shift_start_time required
        → shift_end_time required
        → both routes calculate
        → both trips create
    */

    public function saveCommutePlan(
    Request $request,
    DriverRouteService $routeService
    ) {
        try {

            $user = $request->user();


            /*
            |--------------------------------------------------------------------------
            | Driver Check
            |--------------------------------------------------------------------------
            */

            if (
                !$user ||
                $user->role !== 'driver'
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'Driver access only.',
                ], 403);
            }


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                /*
                * Direction
                */
                'direction' => [
                    'required',
                    'in:home_to_work,work_to_home,both',
                ],


                /*
                * Home
                */
                'home_address' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'home_latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'home_longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],


                /*
                * Workplace
                */
                'workplace_name' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'workplace_address' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'workplace_latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'workplace_longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],


                /*
                * Date
                */
                'commute_date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],


                /*
                * HOME -> WORK
                *
                * Required only when:
                * home_to_work
                * both
                */
                'shift_start_time' => [
                    'nullable',
                    'required_if:direction,home_to_work,both',
                    'date_format:H:i',
                ],


                /*
                * WORK -> HOME
                *
                * Required only when:
                * work_to_home
                * both
                */
                'shift_end_time' => [
                    'nullable',
                    'required_if:direction,work_to_home,both',
                    'date_format:H:i',
                ],


                /*
                * Seats
                */
                'available_seats' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:8',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Additional Both Direction Time Validation
            |--------------------------------------------------------------------------
            */

            if (
                $request->direction === 'both'
                &&
                $request->shift_start_time
                &&
                $request->shift_end_time
            ) {

                $shiftStart =
                    Carbon::createFromFormat(
                        'H:i',
                        $request->shift_start_time
                    );

                $shiftEnd =
                    Carbon::createFromFormat(
                        'H:i',
                        $request->shift_end_time
                    );


                if (
                    $shiftEnd->lessThanOrEqualTo(
                        $shiftStart
                    )
                ) {

                    return response()->json([
                        'status' => false,
                        'message' =>
                            'Shift end time must be after shift start time.',
                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Minimum 4 Hours Required Before Trip
            |--------------------------------------------------------------------------
            |
            | HOME -> WORK
            | shift_start_time must be at least 4 hours from current time
            |
            | WORK -> HOME
            | shift_end_time must be at least 4 hours from current time
            |
            */

            // $now = Carbon::now(
            //     config('app.timezone')
            // );


            /*
            |--------------------------------------------------------------------------
            | HOME -> WORK Minimum 4 Hours Required Before Trip
            |--------------------------------------------------------------------------
            */

            // if (
            //     in_array(
            //         $request->direction,
            //         [
            //             'home_to_work',
            //             'both',
            //         ],
            //         true
            //     )
            // ) {

            //     $shiftStartDateTime =
            //         Carbon::createFromFormat(
            //             'Y-m-d H:i',
            //             $request->commute_date
            //             . ' '
            //             . $request->shift_start_time,
            //             config('app.timezone')
            //         );


            //     $minimumAllowedTime =
            //         $now
            //             ->copy()
            //             ->addHours(4);


            //     if (
            //         $shiftStartDateTime
            //             ->lt(
            //                 $minimumAllowedTime
            //             )
            //     ) {

            //         return response()->json([

            //             'status' => false,

            //             'message' =>
            //                 'Home to Work trip must be scheduled at least 4 hours before shift start time.',

            //             'data' => [

            //                 'direction' =>
            //                     'home_to_work',

            //                 'current_time' =>
            //                     $now->format(
            //                         'Y-m-d H:i:s'
            //                     ),

            //                 'shift_start_time' =>
            //                     $shiftStartDateTime
            //                         ->format(
            //                             'Y-m-d H:i:s'
            //                         ),

            //                 'minimum_allowed_time' =>
            //                     $minimumAllowedTime
            //                         ->format(
            //                             'Y-m-d H:i:s'
            //                         ),
            //             ]

            //         ], 422);
            //     }
            // }


            /*
            |--------------------------------------------------------------------------
            | WORK -> HOME Minimum 4 Hours Required Before Trip
            |--------------------------------------------------------------------------
            */

            // if (
            //     in_array(
            //         $request->direction,
            //         [
            //             'work_to_home',
            //             'both',
            //         ],
            //         true
            //     )
            // ) {

            //     $shiftEndDateTime =
            //         Carbon::createFromFormat(
            //             'Y-m-d H:i',
            //             $request->commute_date
            //             . ' '
            //             . $request->shift_end_time,
            //             config('app.timezone')
            //         );


            //     $minimumAllowedTime =
            //         $now
            //             ->copy()
            //             ->addHours(4);


            //     if (
            //         $shiftEndDateTime
            //             ->lt(
            //                 $minimumAllowedTime
            //             )
            //     ) {

            //         return response()->json([

            //             'status' => false,

            //             'message' =>
            //                 'Work to Home trip must be scheduled at least 4 hours before shift end time.',

            //             'data' => [

            //                 'direction' =>
            //                     'work_to_home',

            //                 'current_time' =>
            //                     $now->format(
            //                         'Y-m-d H:i:s'
            //                     ),

            //                 'shift_end_time' =>
            //                     $shiftEndDateTime
            //                         ->format(
            //                             'Y-m-d H:i:s'
            //                         ),

            //                 'minimum_allowed_time' =>
            //                     $minimumAllowedTime
            //                         ->format(
            //                             'Y-m-d H:i:s'
            //                         ),
            //             ]

            //         ], 422);
            //     }
            // }


            /*
            |--------------------------------------------------------------------------
            | Seats
            |--------------------------------------------------------------------------
            */

            $seats =
                $request->available_seats
                ?? config(
                    'ridude.default_available_seats',
                    3
                );

            /*
            |--------------------------------------------------------------------------
            | Edit Restriction - 4 Hours Before Shift
            |--------------------------------------------------------------------------
            |
            | HOME -> WORK
            | Can edit only until 4 hours before shift_start_time
            |
            | WORK -> HOME
            | Can edit only until 4 hours before shift_end_time
            |
            */

            $existingSetting =
                DB::table('driver_commute_settings')

                    ->where(
                        'user_id',
                        $user->id
                    )

                    ->where(
                        'commute_date',
                        $request->commute_date
                    )

                    ->first();


            /*
            |--------------------------------------------------------------------------
            | Only Apply Restriction While Editing Existing Plan
            |--------------------------------------------------------------------------
            */

            if ($existingSetting) {

                $now = Carbon::now();


                /*
                |--------------------------------------------------------------------------
                | HOME -> WORK Edit Check
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $request->direction,
                        [
                            'home_to_work',
                            'both',
                        ],
                        true
                    )
                    &&
                    !empty(
                        $existingSetting
                            ->shift_start_time
                    )
                ) {

                    $shiftStartDateTime =
                        Carbon::parse(
                            $request->commute_date
                            . ' '
                            . $existingSetting
                                ->shift_start_time
                        );


                    $homeToWorkEditDeadline =
                        $shiftStartDateTime
                            ->copy()
                            ->subHours(4);


                    if (
                        $now->greaterThanOrEqualTo(
                            $homeToWorkEditDeadline
                        )
                    ) {

                        return response()->json([

                            'status' =>
                                false,

                            'message' =>
                                'Home to Work schedule cannot be edited within 4 hours of shift start time.',

                            'data' => [

                                'direction' =>
                                    'home_to_work',

                                'shift_time' =>
                                    $shiftStartDateTime
                                        ->format(
                                            'Y-m-d H:i:s'
                                        ),

                                'edit_deadline' =>
                                    $homeToWorkEditDeadline
                                        ->format(
                                            'Y-m-d H:i:s'
                                        ),
                            ],

                        ], 422);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | WORK -> HOME Edit Check
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $request->direction,
                        [
                            'work_to_home',
                            'both',
                        ],
                        true
                    )
                    &&
                    !empty(
                        $existingSetting
                            ->shift_end_time
                    )
                ) {

                    $shiftEndDateTime =
                        Carbon::parse(
                            $request->commute_date
                            . ' '
                            . $existingSetting
                                ->shift_end_time
                        );


                    $workToHomeEditDeadline =
                        $shiftEndDateTime
                            ->copy()
                            ->subHours(4);


                    if (
                        $now->greaterThanOrEqualTo(
                            $workToHomeEditDeadline
                        )
                    ) {

                        return response()->json([

                            'status' =>
                                false,

                            'message' =>
                                'Work to Home schedule cannot be edited within 4 hours of shift end time.',

                            'data' => [

                                'direction' =>
                                    'work_to_home',

                                'shift_time' =>
                                    $shiftEndDateTime
                                        ->format(
                                            'Y-m-d H:i:s'
                                        ),

                                'edit_deadline' =>
                                    $workToHomeEditDeadline
                                        ->format(
                                            'Y-m-d H:i:s'
                                        ),
                            ],

                        ], 422);
                    }
                }
            }    


            /*
            |--------------------------------------------------------------------------
            | Route Variables
            |--------------------------------------------------------------------------
            */

            $homeToWorkRoute = null;

            $workToHomeRoute = null;


            /*
            |--------------------------------------------------------------------------
            | Calculate HOME -> WORK
            |--------------------------------------------------------------------------
            |
            | Only when direction:
            | home_to_work
            | both
            |
            */

            if (
                in_array(
                    $request->direction,
                    [
                        'home_to_work',
                        'both',
                    ],
                    true
                )
            ) {

                $homeToWorkRoute =
                    $routeService->calculate(

                        (float)
                        $request->home_latitude,

                        (float)
                        $request->home_longitude,

                        (float)
                        $request->workplace_latitude,

                        (float)
                        $request->workplace_longitude
                    );


                if (
                    !$homeToWorkRoute['status']
                ) {

                    return response()->json([
                        'status' => false,

                        'message' =>
                            $homeToWorkRoute['message']
                            ?? 'Unable to calculate Home to Work route.',

                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate WORK -> HOME
            |--------------------------------------------------------------------------
            |
            | Only when direction:
            | work_to_home
            | both
            |
            */

            if (
                in_array(
                    $request->direction,
                    [
                        'work_to_home',
                        'both',
                    ],
                    true
                )
            ) {

                $workToHomeRoute =
                    $routeService->calculate(

                        (float)
                        $request->workplace_latitude,

                        (float)
                        $request->workplace_longitude,

                        (float)
                        $request->home_latitude,

                        (float)
                        $request->home_longitude
                    );


                if (
                    !$workToHomeRoute['status']
                ) {

                    return response()->json([
                        'status' => false,

                        'message' =>
                            $workToHomeRoute['message']
                            ?? 'Unable to calculate Work to Home route.',

                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Start Transaction
            |--------------------------------------------------------------------------
            */

            DB::beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Driver Commute Settings
            |--------------------------------------------------------------------------
            */

            $settingsData = [

                'home_address' =>
                    $request->home_address,

                'home_latitude' =>
                    $request->home_latitude,

                'home_longitude' =>
                    $request->home_longitude,

                'workplace_name' =>
                    $request->workplace_name,

                'workplace_address' =>
                    $request->workplace_address,

                'workplace_latitude' =>
                    $request->workplace_latitude,

                'workplace_longitude' =>
                    $request->workplace_longitude,

                'shift_start_time' =>
                    in_array(
                        $request->direction,
                        ['home_to_work', 'both'],
                        true
                    )
                        ? $request->shift_start_time
                        : null,

                'shift_end_time' =>
                    in_array(
                        $request->direction,
                        ['work_to_home', 'both'],
                        true
                    )
                        ? $request->shift_end_time
                        : null,    

                'available_seats' =>
                    $seats,

                'is_active' =>
                    true,

                'updated_at' =>
                    now(),
            ];


            /*
            * Save only relevant shift times.
            */

            if (
                in_array(
                    $request->direction,
                    [
                        'home_to_work',
                        'both',
                    ],
                    true
                )
            ) {

                $settingsData[
                    'shift_start_time'
                ] =
                    $request->shift_start_time;
            }


            if (
                in_array(
                    $request->direction,
                    [
                        'work_to_home',
                        'both',
                    ],
                    true
                )
            ) {

                $settingsData[
                    'shift_end_time'
                ] =
                    $request->shift_end_time;
            }


            DB::table(
                'driver_commute_settings'
            )
            ->updateOrInsert(

                [
                    'user_id' =>
                        $user->id,

                    'commute_date' =>
                        $request->commute_date,
                ],

                $settingsData
            );


            /*
            |--------------------------------------------------------------------------
            | HOME -> WORK Trip
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $request->direction,
                    [
                        'home_to_work',
                        'both',
                    ],
                    true
                )
            ) {

                $homeToWorkTime =
                    Carbon::createFromFormat(
                        'H:i',
                        $request->shift_start_time
                    )
                    ->format('H:i:s');


                DB::table('trips')
                    ->updateOrInsert(

                        [
                            'driver_id' =>
                                $user->id,

                            'trip_date' =>
                                $request->commute_date,

                            'direction' =>
                                'home_to_work',
                        ],

                        [
                            'departure_time' =>
                                $homeToWorkTime,


                            /*
                            * Start
                            */
                            'start_name' =>
                                'Home',

                            'start_address' =>
                                $request->home_address,

                            'start_latitude' =>
                                $request->home_latitude,

                            'start_longitude' =>
                                $request->home_longitude,


                            /*
                            * Destination
                            */
                            'destination_name' =>
                                $request->workplace_name
                                ?? 'Work',

                            'destination_address' =>
                                $request->workplace_address,

                            'destination_latitude' =>
                                $request->workplace_latitude,

                            'destination_longitude' =>
                                $request->workplace_longitude,


                            /*
                            * Seats
                            */
                            'available_seats' =>
                                $seats,


                            /*
                            * Fare calculated rider-wise
                            */
                            'fare_per_rider' =>
                                0,


                            /*
                            * Google Route
                            */
                            'estimated_distance' =>
                                $homeToWorkRoute[
                                    'distance_miles'
                                ],

                            'estimated_duration_minutes' =>
                                $homeToWorkRoute[
                                    'duration_minutes'
                                ],

                            'route_polyline' =>
                                $homeToWorkRoute[
                                    'polyline'
                                ]
                                ?? null,


                            /*
                            * Status
                            */
                            'status' =>
                                'scheduled',

                            'updated_at' =>
                                now(),
                        ]
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | WORK -> HOME Trip
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $request->direction,
                    [
                        'work_to_home',
                        'both',
                    ],
                    true
                )
            ) {

                $workToHomeTime =
                    Carbon::createFromFormat(
                        'H:i',
                        $request->shift_end_time
                    )
                    ->format('H:i:s');


                DB::table('trips')
                    ->updateOrInsert(

                        [
                            'driver_id' =>
                                $user->id,

                            'trip_date' =>
                                $request->commute_date,

                            'direction' =>
                                'work_to_home',
                        ],

                        [
                            'departure_time' =>
                                $workToHomeTime,


                            /*
                            * Start
                            */
                            'start_name' =>
                                $request->workplace_name
                                ?? 'Work',

                            'start_address' =>
                                $request->workplace_address,

                            'start_latitude' =>
                                $request->workplace_latitude,

                            'start_longitude' =>
                                $request->workplace_longitude,


                            /*
                            * Destination
                            */
                            'destination_name' =>
                                'Home',

                            'destination_address' =>
                                $request->home_address,

                            'destination_latitude' =>
                                $request->home_latitude,

                            'destination_longitude' =>
                                $request->home_longitude,


                            /*
                            * Seats
                            */
                            'available_seats' =>
                                $seats,


                            /*
                            * Rider-wise fare
                            */
                            'fare_per_rider' =>
                                0,


                            /*
                            * Google Route
                            */
                            'estimated_distance' =>
                                $workToHomeRoute[
                                    'distance_miles'
                                ],

                            'estimated_duration_minutes' =>
                                $workToHomeRoute[
                                    'duration_minutes'
                                ],

                            'route_polyline' =>
                                $workToHomeRoute[
                                    'polyline'
                                ]
                                ?? null,


                            /*
                            * Status
                            */
                            'status' =>
                                'scheduled',

                            'updated_at' =>
                                now(),
                        ]
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Remove Unselected Direction
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | Previously BOTH saved.
            | User changes selection to HOME -> WORK.
            |
            | Remove scheduled WORK -> HOME trip only when
            | there are no riders allocated to it.
            |
            */

            if (
                $request->direction
                !== 'both'
            ) {

                $oppositeDirection =
                    $request->direction
                    === 'home_to_work'

                        ? 'work_to_home'

                        : 'home_to_work';


                $oppositeTrip =
                    DB::table('trips')

                        ->where(
                            'driver_id',
                            $user->id
                        )

                        ->where(
                            'trip_date',
                            $request->commute_date
                        )

                        ->where(
                            'direction',
                            $oppositeDirection
                        )

                        ->where(
                            'status',
                            'scheduled'
                        )

                        ->first();


                if ($oppositeTrip) {

                    $hasRiders =
                        DB::table(
                            'trip_riders'
                        )

                        ->where(
                            'trip_id',
                            $oppositeTrip->id
                        )

                        ->where(
                            'booking_status',
                            '!=',
                            'cancelled'
                        )

                        ->exists();


                    /*
                    * Safe delete only if no rider allocated.
                    */
                    if (!$hasRiders) {

                        DB::table('trips')

                            ->where(
                                'id',
                                $oppositeTrip->id
                            )

                            ->delete();
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Get Only Selected Trips
            |--------------------------------------------------------------------------
            */

            $tripQuery =
                DB::table('trips')

                    ->where(
                        'driver_id',
                        $user->id
                    )

                    ->where(
                        'trip_date',
                        $request->commute_date
                    );


            if (
                $request->direction
                === 'home_to_work'
            ) {

                $tripQuery->where(
                    'direction',
                    'home_to_work'
                );

            } elseif (
                $request->direction
                === 'work_to_home'
            ) {

                $tripQuery->where(
                    'direction',
                    'work_to_home'
                );

            } else {

                $tripQuery->whereIn(
                    'direction',
                    [
                        'home_to_work',
                        'work_to_home',
                    ]
                );
            }


            $trips =
                $tripQuery

                    ->orderBy(
                        'departure_time'
                    )

                    ->get();


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' =>
                    true,

                'message' =>
                    'Commute plan saved successfully.',

                'data' => [

                    'direction' =>
                        $request->direction,

                    'trips' =>
                        $trips,

                    'home_to_work_route' =>
                        $homeToWorkRoute
                        ? [
                            'distance_miles' =>
                                $homeToWorkRoute[
                                    'distance_miles'
                                ],

                            'duration_minutes' =>
                                $homeToWorkRoute[
                                    'duration_minutes'
                                ],
                        ]
                        : null,

                    'work_to_home_route' =>
                        $workToHomeRoute
                        ? [
                            'distance_miles' =>
                                $workToHomeRoute[
                                    'distance_miles'
                                ],

                            'duration_minutes' =>
                                $workToHomeRoute[
                                    'duration_minutes'
                                ],
                        ]
                        : null,
                ]
            ]);


        } catch (
            ValidationException $e
        ) {

            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (
            Throwable $e
        ) {

            if (
                DB::transactionLevel()
                > 0
            ) {

                DB::rollBack();
            }


            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Unable to save commute plan.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }

    public function myTrip(Request $request)
    {
        $user = $request->user();


        if ($user->role !== 'driver') {

            return response()->json([
                'status' => false,
                'message' => 'Driver access only.'
            ], 403);
        }


        $date = $request->date
            ?? now()->toDateString();


        $trip = DB::table('trips')

            ->where(
                'driver_id',
                $user->id
            )

            ->where(
                'trip_date',
                $date
            )

            ->whereIn(
                'status',
                [
                    'scheduled',
                    'active'
                ]
            )

            ->orderBy(
                'departure_time'
            )

            ->first();


        if (!$trip) {

            return response()->json([
                'status' => true,
                'message' =>
                    'No trip found.',
                'data' => null
            ]);
        }


        $riders = DB::table(
            'trip_riders as tr'
        )

            ->join(
                'users as rider',
                'rider.id',
                '=',
                'tr.rider_id'
            )

            ->leftJoin(
                'rider_profiles as rp',
                'rp.user_id',
                '=',
                'rider.id'
            )

            ->where(
                'tr.trip_id',
                $trip->id
            )

            ->where(
                'tr.booking_status',
                'confirmed'
            )

            ->orderBy(
                'tr.stop_order'
            )

            ->select(
                'tr.id as trip_rider_id',
                'rider.id as rider_id',
                'rider.full_name',
                'rp.profile_photo',
                'tr.pickup_address',
                'tr.pickup_latitude',
                'tr.pickup_longitude',
                'tr.estimated_pickup_time',
                'tr.stop_order',
                'tr.ride_status'
            )

            ->get();


        return response()->json([
            'status' => true,

            'data' => [
                'trip' =>
                    $trip,

                'riders' =>
                    $riders,

                'seats_filled' =>
                    $riders->count(),

                'seats_available' =>
                    max(
                        0,
                        $trip->available_seats
                        - $riders->count()
                    )
            ]
        ]);
    }

    public function startTrip(Request $request,int $tripId) 
    {
        $user = $request->user();


        $updated = DB::table('trips')

            ->where(
                'id',
                $tripId
            )

            ->where(
                'driver_id',
                $user->id
            )

            ->where(
                'status',
                'scheduled'
            )

            ->update([
                'status' =>
                    'active',

                'started_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);


        if (!$updated) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to start trip.'
            ], 422);
        }


        return response()->json([
            'status' => true,
            'message' =>
                'Trip started successfully.'
        ]);
    }

    public function pickupRider(Request $request,int $tripId,int $tripRiderId) 
    {
        $user = $request->user();

        $trip = DB::table('trips')
            ->where('id', $tripId)
            ->where('driver_id', $user->id)
            ->where('status', 'active')
            ->first();


        if (!$trip) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Active trip not found.'
            ], 404);
        }


        $updated = DB::table('trip_riders')

            ->where(
                'id',
                $tripRiderId
            )

            ->where(
                'trip_id',
                $tripId
            )

            ->where(
                'booking_status',
                'confirmed'
            )

            ->where(
                'ride_status',
                'waiting'
            )

            ->update([

                'ride_status' =>
                    'picked_up',

                'picked_up_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);


        return response()->json([
            'status' =>
                (bool) $updated,

            'message' =>
                $updated
                    ? 'Rider picked up successfully.'
                    : 'Unable to update rider.'
        ]);
    }

    public function dropoffRider(Request $request,int $tripId,int $tripRiderId) 
    {
        $user = $request->user();

        $trip = DB::table('trips')
            ->where('id', $tripId)
            ->where('driver_id', $user->id)
            ->where('status', 'active')
            ->first();


        if (!$trip) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Active trip not found.'
            ], 404);
        }


        $updated = DB::table('trip_riders')

            ->where(
                'id',
                $tripRiderId
            )

            ->where(
                'trip_id',
                $tripId
            )

            ->where(
                'ride_status',
                'picked_up'
            )

            ->update([

                'ride_status' =>
                    'dropped_off',

                'dropped_off_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);


        return response()->json([
            'status' =>
                (bool) $updated,

            'message' =>
                $updated
                    ? 'Rider dropped off successfully.'
                    : 'Unable to update rider.'
        ]);
    }

    public function completeTrip(Request $request,int $tripId) 
    {
        $user = $request->user();


        DB::beginTransaction();


        try {

            $trip = DB::table('trips')

                ->where(
                    'id',
                    $tripId
                )

                ->where(
                    'driver_id',
                    $user->id
                )

                ->where(
                    'status',
                    'active'
                )

                ->lockForUpdate()

                ->first();


            if (!$trip) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                        'Active trip not found.'
                ], 404);
            }


            /*
            * Mark trip completed
            */
            DB::table('trips')

                ->where(
                    'id',
                    $tripId
                )

                ->update([

                    'status' =>
                        'completed',

                    'completed_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


            /*
            * Mark rider bookings completed
            */
            DB::table('trip_riders')

                ->where(
                    'trip_id',
                    $tripId
                )

                ->where(
                    'booking_status',
                    'confirmed'
                )

                ->update([

                    'booking_status' =>
                        'completed',

                    'updated_at' =>
                        now(),
                ]);


            DB::commit();


            return response()->json([
                'status' => true,
                'message' =>
                    'Trip completed successfully.'
            ]);


        } catch (Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to complete trip.',
                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }

    public function earnings(Request $request)
    {
        try {

            $user = $request->user();


            /*
            |--------------------------------------------------------------------------
            | Period
            |--------------------------------------------------------------------------
            |
            | this_week
            | last_week
            | this_month
            | all
            |
            */

            $period =
                $request->get(
                    'period',
                    'this_week'
                );


            /*
            |--------------------------------------------------------------------------
            | Date Range
            |--------------------------------------------------------------------------
            */

            switch ($period) {

                case 'last_week':

                    $startDate =
                        Carbon::now()
                            ->subWeek()
                            ->startOfWeek(
                                Carbon::MONDAY
                            );

                    $endDate =
                        Carbon::now()
                            ->subWeek()
                            ->endOfWeek(
                                Carbon::SUNDAY
                            );

                    break;


                case 'this_month':

                    $startDate =
                        Carbon::now()
                            ->startOfMonth();

                    $endDate =
                        Carbon::now()
                            ->endOfMonth();

                    break;


                case 'all':

                    $startDate = null;
                    $endDate = null;

                    break;


                case 'this_week':

                default:

                    $period =
                        'this_week';

                    $startDate =
                        Carbon::now()
                            ->startOfWeek(
                                Carbon::MONDAY
                            );

                    $endDate =
                        Carbon::now()
                            ->endOfWeek(
                                Carbon::SUNDAY
                            );

                    break;
            }


            /*
            |--------------------------------------------------------------------------
            | Completed Trips Query
            |--------------------------------------------------------------------------
            */

            $completedTripsQuery =
                DB::table(
                    'trips'
                )

                ->where(
                    'driver_id',
                    $user->id
                )

                ->where(
                    'status',
                    'completed'
                );


            if (
                $startDate
                &&
                $endDate
            ) {

                $completedTripsQuery

                    ->whereDate(
                        'trip_date',
                        '>=',
                        $startDate
                            ->toDateString()
                    )

                    ->whereDate(
                        'trip_date',
                        '<=',
                        $endDate
                            ->toDateString()
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Trips Completed
            |--------------------------------------------------------------------------
            */

            $tripsCompleted =
                (clone $completedTripsQuery)
                    ->count();


            /*
            |--------------------------------------------------------------------------
            | Riders Carried
            |--------------------------------------------------------------------------
            */

            $ridersQuery =
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
                    't.driver_id',
                    $user->id
                )

                ->where(
                    't.status',
                    'completed'
                )

                ->where(
                    'tr.ride_status',
                    'dropped_off'
                )

                ->where(
                    'tr.booking_status',
                    'confirmed'
                );


            if (
                $startDate
                &&
                $endDate
            ) {

                $ridersQuery

                    ->whereDate(
                        't.trip_date',
                        '>=',
                        $startDate
                            ->toDateString()
                    )

                    ->whereDate(
                        't.trip_date',
                        '<=',
                        $endDate
                            ->toDateString()
                    );
            }


            $ridersCarried =
                (clone $ridersQuery)
                    ->count();


            /*
            |--------------------------------------------------------------------------
            | Total Earnings
            |--------------------------------------------------------------------------
            */

            $totalEarnings =
                (float)
                (
                    (clone $ridersQuery)
                        ->sum(
                            'tr.fare_amount'
                        )
                    ?? 0
                );


            /*
            |--------------------------------------------------------------------------
            | Hours Online
            |--------------------------------------------------------------------------
            |
            | For now:
            | Trip started_at -> completed_at
            |
            */

            $completedTrips =
                (clone $completedTripsQuery)
                    ->select(
                        'id',
                        'started_at',
                        'completed_at'
                    )
                    ->get();


            $totalMinutes = 0;


            foreach (
                $completedTrips as $trip
            ) {

                if (
                    $trip->started_at
                    &&
                    $trip->completed_at
                ) {

                    $startedAt =
                        Carbon::parse(
                            $trip->started_at
                        );

                    $completedAt =
                        Carbon::parse(
                            $trip->completed_at
                        );


                    $totalMinutes +=
                        $startedAt
                            ->diffInMinutes(
                                $completedAt
                            );
                }
            }


            $hoursOnline =
                round(
                    $totalMinutes / 60,
                    1
                );


            /*
            |--------------------------------------------------------------------------
            | Daily Earnings Overview
            |--------------------------------------------------------------------------
            */

            $earningsOverview = [];


            if (
                $period !== 'all'
            ) {

                $days =
                    CarbonPeriod::create(
                        $startDate,
                        $endDate
                    );


                foreach (
                    $days as $day
                ) {

                    $dayEarning =
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
                            't.driver_id',
                            $user->id
                        )

                        ->where(
                            't.status',
                            'completed'
                        )

                        ->where(
                            'tr.ride_status',
                            'dropped_off'
                        )

                        ->where(
                            'tr.booking_status',
                            'confirmed'
                        )

                        ->whereDate(
                            't.trip_date',
                            $day
                                ->toDateString()
                        )

                        ->sum(
                            'tr.fare_amount'
                        );


                    $earningsOverview[] = [

                        'date' =>
                            $day
                                ->toDateString(),

                        'day' =>
                            $day
                                ->format('D'),

                        'amount' =>
                            round(
                                (float)
                                $dayEarning,
                                2
                            ),
                    ];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Recent Trips
            |--------------------------------------------------------------------------
            */

            $recentTripsQuery =
                DB::table(
                    'trips as t'
                )

                ->where(
                    't.driver_id',
                    $user->id
                )

                ->where(
                    't.status',
                    'completed'
                );


            if (
                $startDate
                &&
                $endDate
            ) {

                $recentTripsQuery

                    ->whereDate(
                        't.trip_date',
                        '>=',
                        $startDate
                            ->toDateString()
                    )

                    ->whereDate(
                        't.trip_date',
                        '<=',
                        $endDate
                            ->toDateString()
                    );
            }


            $recentTrips =
                $recentTripsQuery

                    ->orderByDesc(
                        't.trip_date'
                    )

                    ->orderByDesc(
                        't.departure_time'
                    )

                    ->limit(5)

                    ->select(
                        't.id',
                        't.trip_date',
                        't.departure_time',
                        't.direction',
                        't.start_name',
                        't.start_address',
                        't.destination_name',
                        't.destination_address',
                        't.started_at',
                        't.completed_at'
                    )

                    ->get();


            /*
            |--------------------------------------------------------------------------
            | Attach Riders + Earnings To Each Trip
            |--------------------------------------------------------------------------
            */

            foreach (
                $recentTrips as $trip
            ) {

                $trip->riders_count =
                    DB::table(
                        'trip_riders'
                    )

                    ->where(
                        'trip_id',
                        $trip->id
                    )

                    ->where(
                        'ride_status',
                        'dropped_off'
                    )

                    ->where(
                        'booking_status',
                        'confirmed'
                    )

                    ->count();


                $trip->earnings =
                    round(
                        (float)
                        DB::table(
                            'trip_riders'
                        )

                        ->where(
                            'trip_id',
                            $trip->id
                        )

                        ->where(
                            'ride_status',
                            'dropped_off'
                        )

                        ->where(
                            'booking_status',
                            'confirmed'
                        )

                        ->sum(
                            'fare_amount'
                        ),
                        2
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Previous Week Comparison
            |--------------------------------------------------------------------------
            */

            $comparisonPercentage = null;


            if (
                $period ===
                'this_week'
            ) {

                $previousStart =
                    Carbon::now()
                        ->subWeek()
                        ->startOfWeek(
                            Carbon::MONDAY
                        );


                $previousEnd =
                    Carbon::now()
                        ->subWeek()
                        ->endOfWeek(
                            Carbon::SUNDAY
                        );


                $previousEarnings =
                    (float)
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
                        't.driver_id',
                        $user->id
                    )

                    ->where(
                        't.status',
                        'completed'
                    )

                    ->where(
                        'tr.ride_status',
                        'dropped_off'
                    )

                    ->where(
                        'tr.booking_status',
                        'confirmed'
                    )

                    ->whereBetween(
                        't.trip_date',
                        [
                            $previousStart
                                ->toDateString(),

                            $previousEnd
                                ->toDateString(),
                        ]
                    )

                    ->sum(
                        'tr.fare_amount'
                    );


                if (
                    $previousEarnings > 0
                ) {

                    $comparisonPercentage =
                        round(
                            (
                                (
                                    $totalEarnings
                                    -
                                    $previousEarnings
                                )
                                /
                                $previousEarnings
                            )
                            *
                            100,
                            2
                        );

                } elseif (
                    $totalEarnings > 0
                ) {

                    $comparisonPercentage =
                        100;
                } else {

                    $comparisonPercentage =
                        0;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Driver Rating
            |--------------------------------------------------------------------------
            |
            | Rating table structure has not yet been provided.
            | Keep null until ratings module is connected.
            |
            */

            $driverRating =
                null;


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' =>
                    true,

                'data' => [

                    'period' =>
                        $period,

                    'date_range' => [

                        'from' =>
                            $startDate
                                ? $startDate
                                    ->toDateString()
                                : null,

                        'to' =>
                            $endDate
                                ? $endDate
                                    ->toDateString()
                                : null,
                    ],


                    /*
                    * Main Card
                    */
                    'total_earnings' =>
                        round(
                            $totalEarnings,
                            2
                        ),

                    'currency' =>
                        'USD',

                    'comparison_percentage' =>
                        $comparisonPercentage,


                    /*
                    * Statistics
                    */
                    'stats' => [

                        'trips_completed' =>
                            $tripsCompleted,

                        'riders_carried' =>
                            $ridersCarried,

                        'hours_online' =>
                            $hoursOnline,

                        'driver_rating' =>
                            $driverRating,
                    ],


                    /*
                    * Graph
                    */
                    'earnings_overview' =>
                        $earningsOverview,


                    /*
                    * Recent Trips
                    */
                    'recent_trips' =>
                        $recentTrips,
                ]

            ]);


        } catch (\Throwable $e) {

            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Unable to load earnings.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Driver Profile
    |--------------------------------------------------------------------------
    */

    public function showProfile(Request $request)
    {
        try {

            $user = $request->user();


            $profile =
                DB::table('users as u')

                ->leftJoin(
                    'driver_profiles as dp',
                    'dp.user_id',
                    '=',
                    'u.id'
                )

                ->leftJoin(
                    'driver_vehicles as dv',
                    'dv.user_id',
                    '=',
                    'u.id'
                )

                ->where(
                    'u.id',
                    $user->id
                )

                ->select(

                    /*
                     * User
                     */
                    'u.id',
                    'u.full_name',
                    'u.email',
                    'u.country_code',
                    'u.phone_number',
                    'u.role',

                    /*
                     * Driver Profile
                     */
                    'dp.date_of_birth',
                    'dp.gender',
                    'dp.address',
                    'dp.city',
                    'dp.state',
                    'dp.zip_code',
                    'dp.profile_photo',

                    /*
                     * Vehicle
                     */
                    'dv.car_make',
                    'dv.car_model',
                    'dv.car_year',
                    'dv.car_color',
                    'dv.license_plate_number',
                    'dv.front_photo',
                    'dv.back_photo'
                )

                ->first();


            if (!$profile) {

                return response()->json([
                    'status' => false,
                    'message' =>
                        'Driver profile not found.',
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | Payment Methods
            |--------------------------------------------------------------------------
            */

            $paymentMethods =
                DB::table(
                    'driver_payment_methods as dpm'
                )

                ->join(
                    'payment_methods as pm',
                    'pm.id',
                    '=',
                    'dpm.payment_method_id'
                )

                ->where(
                    'dpm.user_id',
                    $user->id
                )

                ->where(
                    'pm.is_active',
                    1
                )

                ->select(
                    'pm.id',
                    'pm.name',
                    'pm.slug',
                    'pm.icon'
                )

                ->get();


            return response()->json([
                'status' => true,

                'data' => [

                    'profile' =>
                        $profile,

                    'payment_methods' =>
                        $paymentMethods,
                ]
            ]);


        } catch (Throwable $e) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to load driver profile.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update Driver Profile
    |--------------------------------------------------------------------------
    */

    public function updateProfile(Request $request)
    {
        try {

            $user = $request->user();


            $request->validate([

                'full_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'country_code' => [
                    'nullable',
                    'string',
                    'max:10',
                ],

                'phone_number' => [
                    'nullable',
                    'string',
                    'max:30',
                ],

                'date_of_birth' => [
                    'nullable',
                    'date',
                ],

                'gender' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'address' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'state' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'zip_code' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'profile_photo' => [
                    'nullable',
                    'image',
                    'max:5120',
                ],

                'car_make' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'car_model' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'car_year' => [
                    'nullable',
                    'integer',
                ],

                'car_color' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'license_plate_number' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'front_photo' => [
                    'nullable',
                    'image',
                    'max:5120',
                ],

                'back_photo' => [
                    'nullable',
                    'image',
                    'max:5120',
                ],

                'payment_method_ids' => [
                    'nullable',
                    'array',
                ],

                'payment_method_ids.*' => [
                    'integer',
                    'exists:payment_methods,id',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Duplicate Phone Check
            |--------------------------------------------------------------------------
            */

            $countryCode =
                $request->country_code
                ?? $user->country_code;


            $phoneNumber =
                $request->phone_number
                ?? $user->phone_number;


            if (
                $countryCode
                &&
                $phoneNumber
            ) {

                $phoneExists =
                    DB::table('users')

                        ->where(
                            'country_code',
                            $countryCode
                        )

                        ->where(
                            'phone_number',
                            $phoneNumber
                        )

                        ->where(
                            'id',
                            '!=',
                            $user->id
                        )

                        ->exists();


                if ($phoneExists) {

                    return response()->json([
                        'status' => false,
                        'message' =>
                            'This phone number is already registered with another account.',
                    ], 422);
                }
            }


            DB::beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Update User
            |--------------------------------------------------------------------------
            */

            $userData = [];


            if (
                $request->has(
                    'full_name'
                )
            ) {

                $userData[
                    'full_name'
                ] =
                    $request->full_name;
            }


            if (
                $request->has(
                    'country_code'
                )
            ) {

                $userData[
                    'country_code'
                ] =
                    $request->country_code;
            }


            if (
                $request->has(
                    'phone_number'
                )
            ) {

                $userData[
                    'phone_number'
                ] =
                    $request->phone_number;
            }


            if (
                !empty(
                    $userData
                )
            ) {

                $userData[
                    'updated_at'
                ] =
                    now();


                DB::table('users')

                    ->where(
                        'id',
                        $user->id
                    )

                    ->update(
                        $userData
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Driver Profile
            |--------------------------------------------------------------------------
            */

            $profile =
                DB::table(
                    'driver_profiles'
                )

                ->where(
                    'user_id',
                    $user->id
                )

                ->first();


            $profileData = [];


            if (
                $request->has(
                    'date_of_birth'
                )
            ) {

                $profileData[
                    'date_of_birth'
                ] =
                    $request->date_of_birth;
            }


            if (
                $request->has(
                    'gender'
                )
            ) {

                $profileData[
                    'gender'
                ] =
                    $request->gender;
            }


            if (
                $request->has(
                    'address'
                )
            ) {

                $profileData[
                    'address'
                ] =
                    $request->address;
            }


            if (
                $request->has(
                    'city'
                )
            ) {

                $profileData[
                    'city'
                ] =
                    $request->city;
            }


            if (
                $request->has(
                    'state'
                )
            ) {

                $profileData[
                    'state'
                ] =
                    $request->state;
            }


            if (
                $request->has(
                    'zip_code'
                )
            ) {

                $profileData[
                    'zip_code'
                ] =
                    $request->zip_code;
            }


            /*
            |--------------------------------------------------------------------------
            | Profile Photo
            |--------------------------------------------------------------------------
            */

            if (
                $request->hasFile(
                    'profile_photo'
                )
            ) {

                $profilePhoto =
                    $request
                        ->file(
                            'profile_photo'
                        )
                        ->store(
                            'drivers/profile',
                            'public'
                        );


                $profileData[
                    'profile_photo'
                ] =
                    $profilePhoto;
            }


            if (
                !empty(
                    $profileData
                )
            ) {

                $profileData[
                    'updated_at'
                ] =
                    now();


                if ($profile) {

                    DB::table(
                        'driver_profiles'
                    )

                    ->where(
                        'user_id',
                        $user->id
                    )

                    ->update(
                        $profileData
                    );

                } else {

                    $profileData[
                        'user_id'
                    ] =
                        $user->id;

                    $profileData[
                        'created_at'
                    ] =
                        now();


                    DB::table(
                        'driver_profiles'
                    )
                    ->insert(
                        $profileData
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Vehicle
            |--------------------------------------------------------------------------
            */

            $vehicle =
                DB::table(
                    'driver_vehicles'
                )

                ->where(
                    'user_id',
                    $user->id
                )

                ->first();


            $vehicleData = [];


            if (
                $request->has(
                    'car_make'
                )
            ) {

                $vehicleData[
                    'car_make'
                ] =
                    $request->car_make;
            }


            if (
                $request->has(
                    'car_model'
                )
            ) {

                $vehicleData[
                    'car_model'
                ] =
                    $request->car_model;
            }


            if (
                $request->has(
                    'car_year'
                )
            ) {

                $vehicleData[
                    'car_year'
                ] =
                    $request->car_year;
            }


            if (
                $request->has(
                    'car_color'
                )
            ) {

                $vehicleData[
                    'car_color'
                ] =
                    $request->car_color;
            }


            if (
                $request->has(
                    'license_plate_number'
                )
            ) {

                $vehicleData[
                    'license_plate_number'
                ] =
                    $request->license_plate_number;
            }


            /*
            |--------------------------------------------------------------------------
            | Vehicle Photos
            |--------------------------------------------------------------------------
            */

            if (
                $request->hasFile(
                    'front_photo'
                )
            ) {

                $vehicleData[
                    'front_photo'
                ] =
                    $request
                        ->file(
                            'front_photo'
                        )
                        ->store(
                            'drivers/vehicles',
                            'public'
                        );
            }


            if (
                $request->hasFile(
                    'back_photo'
                )
            ) {

                $vehicleData[
                    'back_photo'
                ] =
                    $request
                        ->file(
                            'back_photo'
                        )
                        ->store(
                            'drivers/vehicles',
                            'public'
                        );
            }


            if (
                !empty(
                    $vehicleData
                )
            ) {

                $vehicleData[
                    'updated_at'
                ] =
                    now();


                if ($vehicle) {

                    DB::table(
                        'driver_vehicles'
                    )

                    ->where(
                        'user_id',
                        $user->id
                    )

                    ->update(
                        $vehicleData
                    );

                } else {

                    $vehicleData[
                        'user_id'
                    ] =
                        $user->id;

                    $vehicleData[
                        'created_at'
                    ] =
                        now();


                    DB::table(
                        'driver_vehicles'
                    )
                    ->insert(
                        $vehicleData
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Payment Methods
            |--------------------------------------------------------------------------
            */

            if (
                $request->has(
                    'payment_method_ids'
                )
            ) {

                DB::table(
                    'driver_payment_methods'
                )

                ->where(
                    'user_id',
                    $user->id
                )

                ->delete();


                foreach (
                    $request->payment_method_ids
                    as $paymentMethodId
                ) {

                    DB::table(
                        'driver_payment_methods'
                    )

                    ->insert([

                        'user_id' =>
                            $user->id,

                        'payment_method_id' =>
                            $paymentMethodId,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);
                }
            }


            DB::commit();


            return response()->json([
                'status' => true,
                'message' =>
                    'Driver profile updated successfully.',
            ]);


        } catch (\Illuminate\Validation\ValidationException $e) {

            if (
                DB::transactionLevel()
                > 0
            ) {

                DB::rollBack();
            }


            throw $e;


        } catch (\Throwable $e) {

            if (
                DB::transactionLevel()
                > 0
            ) {

                DB::rollBack();
            }


            return response()->json([
                'status' => false,

                'message' =>
                    'Unable to update driver profile.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }

}    
    