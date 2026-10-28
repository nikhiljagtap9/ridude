<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DriverRouteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DriverTripController extends Controller
{
    public function saveCommutePlan(
        Request $request,
        DriverRouteService $routeService
    )
    {
        try {

            $user = $request->user();


            /*
            |--------------------------------------------------------------------------
            | Driver Check
            |--------------------------------------------------------------------------
            */

            if ($user->role !== 'driver') {

                return response()->json([
                    'status' => false,
                    'message' => 'Driver access only.'
                ], 403);
            }


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'home_address' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'home_latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90'
                ],

                'home_longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180'
                ],


                'workplace_name' => [
                    'nullable',
                    'string',
                    'max:150'
                ],

                'workplace_address' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'workplace_latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90'
                ],

                'workplace_longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180'
                ],


                'commute_date' => [
                    'required',
                    'date',
                    'after_or_equal:today'
                ],

                'shift_start_time' => [
                    'required',
                    'date_format:H:i'
                ],

                'shift_end_time' => [
                    'required',
                    'date_format:H:i',
                    'after:shift_start_time'
                ],

                'available_seats' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:8'
                ]
            ]);


            /*
            |--------------------------------------------------------------------------
            | Calculate HOME -> WORK Route
            |--------------------------------------------------------------------------
            */

            $homeToWorkRoute = $routeService->calculate(

                (float) $request->home_latitude,

                (float) $request->home_longitude,

                (float) $request->workplace_latitude,

                (float) $request->workplace_longitude
            );


            if (!$homeToWorkRoute['status']) {

                return response()->json([
                    'status' => false,
                    'message' =>
                        $homeToWorkRoute['message']
                        ?? 'Unable to calculate Home to Work route.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate WORK -> HOME Route
            |--------------------------------------------------------------------------
            */

            $workToHomeRoute = $routeService->calculate(

                (float) $request->workplace_latitude,

                (float) $request->workplace_longitude,

                (float) $request->home_latitude,

                (float) $request->home_longitude
            );


            if (!$workToHomeRoute['status']) {

                return response()->json([
                    'status' => false,
                    'message' =>
                        $workToHomeRoute['message']
                        ?? 'Unable to calculate Work to Home route.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Start Transaction
            |--------------------------------------------------------------------------
            */

            DB::beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Seats
            |--------------------------------------------------------------------------
            */

            $seats = $request->available_seats
                ?? config(
                    'ridude.default_available_seats',
                    3
                );


            /*
            |--------------------------------------------------------------------------
            | Save Driver Commute Settings
            |--------------------------------------------------------------------------
            */

            DB::table(
                'driver_commute_settings'
            )->updateOrInsert(

                [
                    'user_id' =>
                        $user->id,

                    'commute_date' =>
                        $request->commute_date,
                ],

                [
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
                        $request->shift_start_time,

                    'shift_end_time' =>
                        $request->shift_end_time,

                    'available_seats' =>
                        $seats,

                    'is_active' =>
                        true,

                    'updated_at' =>
                        now(),

                ]
            );


            /*
            |--------------------------------------------------------------------------
            | HOME -> WORK Departure Time
            |--------------------------------------------------------------------------
            |
            | Example:
            | Shift Start = 08:00
            | Lead = 45 Minutes
            | Trip Start = 07:15
            |
            */

            $homeToWorkTime =
                Carbon::createFromFormat(
                    'H:i',
                    $request->shift_start_time
                )
                ->subMinutes(
                    config(
                        'ridude.home_to_work_lead_minutes',
                        45
                    )
                )
                ->format('H:i:s');


            /*
            |--------------------------------------------------------------------------
            | WORK -> HOME Departure Time
            |--------------------------------------------------------------------------
            |
            | Example:
            | Shift End = 17:00
            | Delay = 10 Minutes
            | Trip Start = 17:10
            |
            */

            $workToHomeTime =
                Carbon::createFromFormat(
                    'H:i',
                    $request->shift_end_time
                )
                ->addMinutes(
                    config(
                        'ridude.work_to_home_delay_minutes',
                        10
                    )
                )
                ->format('H:i:s');


            /*
            |--------------------------------------------------------------------------
            | Create / Update HOME -> WORK Trip
            |--------------------------------------------------------------------------
            */

            DB::table('trips')->updateOrInsert(

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
                    * Existing Fare Field
                    *
                    * We will eventually use rider-wise
                    * fare from trip_riders.
                    */

                    'fare_per_rider' => 0,


                    /*
                    * Driver Route Information
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
                        ] ?? null,


                    /*
                    * Status
                    */

                    'status' =>
                        'scheduled',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Create / Update WORK -> HOME Trip
            |--------------------------------------------------------------------------
            */

            DB::table('trips')->updateOrInsert(

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
                    * Existing Fare Field
                    */

                    'fare_per_rider' => 0,


                    /*
                    * Driver Route Information
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
                        ] ?? null,


                    /*
                    * Status
                    */

                    'status' =>
                        'scheduled',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Get Created Trips
            |--------------------------------------------------------------------------
            */

            $trips = DB::table('trips')

                ->where(
                    'driver_id',
                    $user->id
                )

                ->where(
                    'trip_date',
                    $request->commute_date
                )

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
                'status' => true,

                'message' =>
                    'Commute plan saved successfully.',

                'data' => [

                    'trips' =>
                        $trips,

                    /*
                    * Helpful testing information
                    */

                    'home_to_work_route' => [

                        'distance_miles' =>
                            $homeToWorkRoute[
                                'distance_miles'
                            ],

                        'duration_minutes' =>
                            $homeToWorkRoute[
                                'duration_minutes'
                            ],
                    ],

                    'work_to_home_route' => [

                        'distance_miles' =>
                            $workToHomeRoute[
                                'distance_miles'
                            ],

                        'duration_minutes' =>
                            $workToHomeRoute[
                                'duration_minutes'
                            ],
                    ],
                ]
            ]);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors()
            ], 422);


        } catch (Throwable $e) {

            if (
                DB::transactionLevel() > 0
            ) {

                DB::rollBack();
            }


            return response()->json([
                'status' => false,

                'message' =>
                    'Unable to save commute plan.',

                'error' =>
                    $e->getMessage()
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
}    
    