<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DriverTripController extends Controller
{
    public function saveCommutePlan(Request $request)
    {
        try {

            $user = $request->user();

            if ($user->role !== 'driver') {
                return response()->json([
                    'status' => false,
                    'message' => 'Driver access only.'
                ], 403);
            }


            $request->validate([

                'home_address' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'home_latitude' => [
                    'nullable',
                    'numeric',
                    'between:-90,90'
                ],

                'home_longitude' => [
                    'nullable',
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
                    'nullable',
                    'numeric',
                    'between:-90,90'
                ],

                'workplace_longitude' => [
                    'nullable',
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


            DB::beginTransaction();


            $seats = $request->available_seats
                ?? config(
                    'ridude.default_available_seats',
                    3
                );


            /*
             * Save settings
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

                    'created_at' =>
                        now(),
                ]
            );


            /*
             * Home -> Work
             *
             * Example:
             * Shift starts 8:00
             * trip starts 7:15
             */
            $homeToWorkTime = Carbon::createFromFormat(
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
             * Work -> Home
             *
             * Example:
             * Shift ends 5:00
             * trip starts 5:10
             */
            $workToHomeTime = Carbon::createFromFormat(
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
             * Create HOME -> WORK
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

                    'start_name' =>
                        'Home',

                    'start_address' =>
                        $request->home_address,

                    'start_latitude' =>
                        $request->home_latitude,

                    'start_longitude' =>
                        $request->home_longitude,

                    'destination_name' =>
                        $request->workplace_name
                        ?? 'Work',

                    'destination_address' =>
                        $request->workplace_address,

                    'destination_latitude' =>
                        $request->workplace_latitude,

                    'destination_longitude' =>
                        $request->workplace_longitude,

                    'available_seats' =>
                        $seats,

                    'fare_per_rider' =>
                        config(
                            'ridude.default_fare_per_rider',
                            12.40
                        ),

                    'status' =>
                        'scheduled',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );


            /*
             * Create WORK -> HOME
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

                    'start_name' =>
                        $request->workplace_name
                        ?? 'Work',

                    'start_address' =>
                        $request->workplace_address,

                    'start_latitude' =>
                        $request->workplace_latitude,

                    'start_longitude' =>
                        $request->workplace_longitude,

                    'destination_name' =>
                        'Home',

                    'destination_address' =>
                        $request->home_address,

                    'destination_latitude' =>
                        $request->home_latitude,

                    'destination_longitude' =>
                        $request->home_longitude,

                    'available_seats' =>
                        $seats,

                    'fare_per_rider' =>
                        config(
                            'ridude.default_fare_per_rider',
                            12.40
                        ),

                    'status' =>
                        'scheduled',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]
            );


            DB::commit();


            $trips = DB::table('trips')
                ->where(
                    'driver_id',
                    $user->id
                )
                ->where(
                    'trip_date',
                    $request->commute_date
                )
                ->orderBy('departure_time')
                ->get();


            return response()->json([
                'status' => true,
                'message' =>
                    'Commute plan saved successfully.',
                'data' => [
                    'trips' => $trips
                ]
            ]);


        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);


        } catch (Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to save commute plan.',
                'error' => $e->getMessage()
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
                'rp.photo_id',
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
    