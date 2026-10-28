<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class RiderTripController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Submit Ride Request
    |--------------------------------------------------------------------------
    */

    public function requestRide(
        Request $request
    ) {
        try {

            $user =
                $request->user();


            $request->validate([

                'trip_date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                ],

                'direction' => [
                    'required',
                    'in:home_to_work,work_to_home',
                ],

                'pickup_address' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'pickup_latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'pickup_longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],

                'dropoff_address' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'dropoff_latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'dropoff_longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Duplicate Request
            |--------------------------------------------------------------------------
            */

            $existing =
                DB::table(
                    'trip_riders'
                )

                ->where(
                    'rider_id',
                    $user->id
                )

                ->where(
                    'requested_trip_date',
                    $request->trip_date
                )

                ->where(
                    'requested_direction',
                    $request->direction
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

                ->exists();


            if ($existing) {

                return response()->json([
                    'status' => false,

                    'message' =>
                        'You already have a ride request for this date and direction.',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Create Pending Request
            |--------------------------------------------------------------------------
            */

            $tripRiderId =
                DB::table(
                    'trip_riders'
                )
                ->insertGetId([

                    'trip_id' =>
                        null,

                    'rider_id' =>
                        $user->id,

                    'requested_trip_date' =>
                        $request->trip_date,

                    'requested_direction' =>
                        $request->direction,

                    'pickup_address' =>
                        $request->pickup_address,

                    'pickup_latitude' =>
                        $request->pickup_latitude,

                    'pickup_longitude' =>
                        $request->pickup_longitude,

                    'dropoff_address' =>
                        $request->dropoff_address,

                    'dropoff_latitude' =>
                        $request->dropoff_latitude,

                    'dropoff_longitude' =>
                        $request->dropoff_longitude,

                    'stop_order' =>
                        0,

                    'booking_status' =>
                        'confirmed',

                    'ride_status' =>
                        'waiting',

                    'fare_amount' =>
                        0,

                    'allocation_status' =>
                        'pending',

                    'allocation_type' =>
                        'auto',

                    'allocated_at' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


            return response()->json([
                'status' => true,

                'message' =>
                    'Ride request submitted successfully.',

                'data' => [

                    'trip_rider_id' =>
                        $tripRiderId,

                    'allocation_status' =>
                        'pending',

                    'trip_date' =>
                        $request->trip_date,

                    'direction' =>
                        $request->direction,
                ]

            ], 201);


        } catch (Throwable $e) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Unable to submit ride request.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | My Ride Requests / Trips
    |--------------------------------------------------------------------------
    */

    public function myTrips(
        Request $request
    ) {

        $user =
            $request->user();


        $trips =
            DB::table(
                'trip_riders as tr'
            )

            ->leftJoin(
                'trips as t',
                't.id',
                '=',
                'tr.trip_id'
            )

            ->leftJoin(
                'users as driver',
                'driver.id',
                '=',
                't.driver_id'
            )

            ->leftJoin(
                'driver_profiles as dp',
                'dp.user_id',
                '=',
                'driver.id'
            )

            ->where(
                'tr.rider_id',
                $user->id
            )

            ->select(

                'tr.id as trip_rider_id',

                'tr.requested_trip_date',

                'tr.requested_direction',

                'tr.allocation_status',

                'tr.booking_status',

                'tr.ride_status',

                'tr.pickup_address',

                'tr.dropoff_address',

                'tr.distance_miles',

                'tr.pickup_distance_from_route',

                'tr.fare_amount',

                'tr.allocated_at',

                't.id as trip_id',

                't.trip_date',

                't.direction',

                't.departure_time',

                't.status as trip_status',

                'driver.id as driver_id',

                'driver.full_name as driver_name',

                'dp.profile_photo as driver_photo'
            )

            ->orderByDesc(
                'tr.created_at'
            )

            ->get();


        return response()->json([
            'status' => true,
            'data' => $trips,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Allocated Trip Detail
    |--------------------------------------------------------------------------
    */

    public function tripDetail(
        Request $request,
        int $tripId
    ) {

        $user =
            $request->user();


        $trip =
            DB::table(
                'trip_riders as tr'
            )

            ->join(
                'trips as t',
                't.id',
                '=',
                'tr.trip_id'
            )

            ->join(
                'users as driver',
                'driver.id',
                '=',
                't.driver_id'
            )

            ->leftJoin(
                'driver_profiles as dp',
                'dp.user_id',
                '=',
                'driver.id'
            )

            ->leftJoin(
                'driver_vehicles as dv',
                'dv.user_id',
                '=',
                'driver.id'
            )

            ->where(
                't.id',
                $tripId
            )

            ->where(
                'tr.rider_id',
                $user->id
            )

            ->select(

                't.*',

                'tr.id as trip_rider_id',

                'tr.booking_status',

                'tr.allocation_status',

                'tr.fare_amount',

                'tr.distance_miles',

                'tr.pickup_distance_from_route',

                'driver.full_name as driver_name',

                'dp.profile_photo as driver_photo',

                'dv.car_make',

                'dv.car_model',

                'dv.car_color',

                'dv.license_plate_number'
            )

            ->first();


        if (!$trip) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Trip not found.',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Rider Stops
        |--------------------------------------------------------------------------
        */

        $stops =
            DB::table(
                'trip_riders as tr'
            )

            ->join(
                'users as rider',
                'rider.id',
                '=',
                'tr.rider_id'
            )

            ->where(
                'tr.trip_id',
                $tripId
            )

            ->where(
                'tr.booking_status',
                'confirmed'
            )

            ->whereIn(
                'tr.allocation_status',
                [
                    'allocated',
                    'finalized',
                ]
            )

            ->orderBy(
                'tr.stop_order'
            )

            ->select(

                'tr.id',

                'tr.stop_order',

                'tr.pickup_address',

                'tr.pickup_latitude',

                'tr.pickup_longitude',

                'tr.estimated_pickup_time',

                'rider.full_name as rider_name'
            )

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Driver Payment Methods
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
                $trip->driver_id
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

                'trip' =>
                    $trip,

                'stops' =>
                    $stops,

                'payment_methods' =>
                    $paymentMethods,
            ]
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel Rider Request / Allocation
    |--------------------------------------------------------------------------
    */

    public function cancelRequest(
        Request $request,
        int $tripRiderId
    ) {

        $user =
            $request->user();


        $request->validate([

            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);


        $booking =
            DB::table(
                'trip_riders'
            )

            ->where(
                'id',
                $tripRiderId
            )

            ->where(
                'rider_id',
                $user->id
            )

            ->where(
                'booking_status',
                '!=',
                'cancelled'
            )

            ->first();


        if (!$booking) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Ride request not found.',
            ], 404);
        }


        DB::table(
            'trip_riders'
        )

        ->where(
            'id',
            $tripRiderId
        )

        ->update([

            'booking_status' =>
                'cancelled',

            'allocation_status' =>
                'cancelled',

            'cancelled_at' =>
                now(),

            'cancelled_by' =>
                'rider',

            'cancel_reason' =>
                $request->reason,

            'updated_at' =>
                now(),
        ]);


        return response()->json([
            'status' => true,

            'message' =>
                'Ride request cancelled successfully.',
        ]);
    }
}