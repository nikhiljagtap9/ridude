<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RiderTripController extends Controller
{
    public function availableTrips(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'rider') {

            return response()->json([
                'status' => false,
                'message' => 'Rider access only.'
            ], 403);
        }


        $request->validate([

            'date' => [
                'required',
                'date'
            ],

            'direction' => [
                'required',
                Rule::in([
                    'home_to_work',
                    'work_to_home'
                ])
            ]

        ]);


        $trips = DB::table('trips as t')

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

            ->where(
                't.trip_date',
                $request->date
            )

            ->where(
                't.direction',
                $request->direction
            )

            ->where(
                't.status',
                'scheduled'
            )

            ->select(
                't.*',
                'driver.full_name as driver_name',
                'dp.profile_photo as driver_photo'
            )

            ->get();


        foreach ($trips as $trip) {

            $bookedSeats = DB::table('trip_riders')

                ->where(
                    'trip_id',
                    $trip->id
                )

                ->where(
                    'booking_status',
                    'confirmed'
                )

                ->count();


            $trip->seats_filled =
                $bookedSeats;

            $trip->seats_remaining =
                max(
                    0,
                    $trip->available_seats
                    - $bookedSeats
                );
        }


        return response()->json([
            'status' => true,
            'data' => $trips
        ]);
    }

    public function reserve(Request $request,int $tripId) 
    {
        try {

            $user = $request->user();

            if ($user->role !== 'rider') {

                return response()->json([
                    'status' => false,
                    'message' => 'Rider access only.'
                ], 403);
            }


            $request->validate([

                'pickup_address' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'pickup_latitude' => [
                    'nullable',
                    'numeric'
                ],

                'pickup_longitude' => [
                    'nullable',
                    'numeric'
                ],

                'dropoff_address' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'dropoff_latitude' => [
                    'nullable',
                    'numeric'
                ],

                'dropoff_longitude' => [
                    'nullable',
                    'numeric'
                ],

            ]);


            DB::beginTransaction();


            /*
            * Lock trip while booking
            */
            $trip = DB::table('trips')

                ->where(
                    'id',
                    $tripId
                )

                ->lockForUpdate()

                ->first();


            if (!$trip) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Trip not found.'
                ], 404);
            }


            if ($trip->status !== 'scheduled') {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                        'This trip is no longer available.'
                ], 422);
            }


            /*
            * Already reserved?
            */
            $alreadyBooked = DB::table(
                'trip_riders'
            )
                ->where(
                    'trip_id',
                    $tripId
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
                ->exists();


            if ($alreadyBooked) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                        'You already reserved this trip.'
                ], 422);
            }


            /*
            * One day at a time rule
            *
            * Rider cannot have another confirmed trip
            * of same direction/date.
            */
            $existingTrip = DB::table(
                'trip_riders as tr'
            )

                ->join(
                    'trips as t',
                    't.id',
                    '=',
                    'tr.trip_id'
                )

                ->where(
                    'tr.rider_id',
                    $user->id
                )

                ->where(
                    'tr.booking_status',
                    'confirmed'
                )

                ->where(
                    't.trip_date',
                    $trip->trip_date
                )

                ->where(
                    't.direction',
                    $trip->direction
                )

                ->exists();


            if ($existingTrip) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                        'You already have a reservation for this trip direction on this date.'
                ], 422);
            }


            /*
            * Seat count
            */
            $bookedSeats = DB::table(
                'trip_riders'
            )

                ->where(
                    'trip_id',
                    $tripId
                )

                ->where(
                    'booking_status',
                    'confirmed'
                )

                ->count();


            if (
                $bookedSeats
                >= $trip->available_seats
            ) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                        'No seats available for this trip.'
                ], 422);
            }


            /*
            * Next stop order
            */
            $nextStop = DB::table(
                'trip_riders'
            )
                ->where(
                    'trip_id',
                    $tripId
                )
                ->max('stop_order');


            $nextStop =
                ((int) $nextStop) + 1;


            /*
            * Save booking
            */
            $tripRiderId = DB::table(
                'trip_riders'
            )->insertGetId([

                'trip_id' =>
                    $tripId,

                'rider_id' =>
                    $user->id,

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
                    $nextStop,

                'booking_status' =>
                    'confirmed',

                'ride_status' =>
                    'waiting',

                /*
                * Fare from trip DB
                */
                'fare_amount' =>
                    $trip->fare_per_rider,

                'confirmed_at' =>
                    now(),

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);


            DB::commit();


            return response()->json([
                'status' => true,

                'message' =>
                    'Trip reserved successfully.',

                'data' => [
                    'trip_rider_id' =>
                        $tripRiderId,

                    'trip_id' =>
                        $tripId,

                    'fare' =>
                        $trip->fare_per_rider,

                    'status' =>
                        'confirmed'
                ]

            ], 201);


        } catch (Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }


            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to reserve trip.',
                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }

    public function myTrips(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'rider') {

            return response()->json([
                'status' => false,
                'message' => 'Rider access only.'
            ], 403);
        }


        $baseQuery = DB::table(
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

            ->where(
                'tr.rider_id',
                $user->id
            )

            ->select(
                'tr.id as trip_rider_id',
                'tr.booking_status',
                'tr.ride_status',
                'tr.fare_amount',
                'tr.pickup_address',
                'tr.dropoff_address',

                't.id as trip_id',
                't.direction',
                't.trip_date',
                't.departure_time',
                't.status as trip_status',
                't.available_seats',

                'driver.id as driver_id',
                'driver.full_name as driver_name',

                'dp.profile_photo as driver_photo'
            );


        $all = $baseQuery
            ->orderBy(
                't.trip_date',
                'desc'
            )
            ->get();


        $upcoming = [];
        $completed = [];
        $cancelled = [];


        foreach ($all as $trip) {

            if (
                $trip->booking_status
                === 'cancelled'
            ) {

                $cancelled[] = $trip;

            } elseif (
                $trip->booking_status
                === 'completed'
                || $trip->trip_status
                === 'completed'
            ) {

                $completed[] = $trip;

            } else {

                $upcoming[] = $trip;
            }
        }


        return response()->json([
            'status' => true,

            'data' => [
                'upcoming' =>
                    $upcoming,

                'completed' =>
                    $completed,

                'cancelled' =>
                    $cancelled,

                'counts' => [
                    'upcoming' =>
                        count($upcoming),

                    'completed' =>
                        count($completed),

                    'cancelled' =>
                        count($cancelled),
                ]
            ]
        ]);
    }

    /**
     * Trip Details
     *   Route on Map
     * So no separate map API is required.
     */
    public function tripDetail(Request $request,int $tripId) 
    {
        $user = $request->user();

        $trip = DB::table(
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
                'tr.fare_amount',

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
                'message' => 'Trip not found.'
            ], 404);
        }


        /*
        * All Rider stops
        */
        $stops = DB::table(
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
        * Accepted payment options
        */
        $paymentMethods = DB::table(
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


        $seatsFilled = DB::table(
            'trip_riders'
        )

            ->where(
                'trip_id',
                $tripId
            )

            ->where(
                'booking_status',
                'confirmed'
            )

            ->count();


        return response()->json([
            'status' => true,

            'data' => [

                'trip' =>
                    $trip,

                'seats_filled' =>
                    $seatsFilled,

                'stops' =>
                    $stops,

                'payment_methods' =>
                    $paymentMethods
            ]
        ]);
    }

    public function cancel(Request $request,int $tripId) 
    {
        $user = $request->user();


        $request->validate([
            'reason' => [
                'nullable',
                'string',
                'max:500'
            ]
        ]);


        $booking = DB::table(
            'trip_riders'
        )

            ->where(
                'trip_id',
                $tripId
            )

            ->where(
                'rider_id',
                $user->id
            )

            ->where(
                'booking_status',
                'confirmed'
            )

            ->first();


        if (!$booking) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Active booking not found.'
            ], 404);
        }


        DB::table('trip_riders')

            ->where(
                'id',
                $booking->id
            )

            ->update([

                'booking_status' =>
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
                'Trip cancelled successfully.'
        ]);
    }
}
