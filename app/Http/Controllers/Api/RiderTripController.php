<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Storage;

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

                'state_id' => [
                    'required',
                    'integer',
                    'exists:states,id',
                ],

                'city_id' => [
                    'required',
                    'integer',
                    'exists:cities,id',
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
              Selected city does not belong to selected state.   
            */

            $cityExists =
                DB::table('cities')

                ->where(
                    'id',
                    $request->city_id
                )

                ->where(
                    'state_id',
                    $request->state_id
                )

                ->exists();


            if (!$cityExists) {

                return response()->json([
                    'status' => false,
                    'message' =>
                        'Selected city does not belong to selected state.'
                ], 422);
            }


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

                    'state_id' =>
                        $request->state_id,

                    'city_id' =>
                        $request->city_id,    

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

    public function tripDetail(Request $request)
    {
        $request->validate([
            'trip_id' => [
                'required',
                'integer',
                'exists:trips,id',
            ],
        ]);


        $user =
            $request->user();


        $tripId =
            (int) $request->trip_id;


        /*
        |--------------------------------------------------------------------------
        | Allocated Trip Detail
        |--------------------------------------------------------------------------
        */

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

                'tr.ride_status',

                'tr.allocation_status',

                'tr.fare_amount',

                'tr.distance_miles',

                'tr.pickup_distance_from_route',

                'tr.pickup_address',

                'tr.pickup_latitude',

                'tr.pickup_longitude',

                'tr.dropoff_address',

                'tr.dropoff_latitude',

                'tr.dropoff_longitude',

                'tr.estimated_pickup_time',

                'tr.stop_order',

                'driver.full_name as driver_name',

                'driver.country_code as driver_country_code',

                'driver.phone_number as driver_phone_number',

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
        | Driver Photo URL
        |--------------------------------------------------------------------------
        */

        if (!empty($trip->driver_photo)) {

            $trip->driver_photo_url =
                asset(
                    'storage/' .
                    $trip->driver_photo
                );

        } else {

            $trip->driver_photo_url =
                null;
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

                'tr.id as trip_rider_id',

                'tr.rider_id',

                'tr.stop_order',

                'tr.pickup_address',

                'tr.pickup_latitude',

                'tr.pickup_longitude',

                'tr.dropoff_address',

                'tr.dropoff_latitude',

                'tr.dropoff_longitude',

                'tr.estimated_pickup_time',

                'tr.ride_status',

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


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' =>
                true,

            'message' =>
                'Trip details fetched successfully.',

            'data' => [

                'trip' =>
                    $trip,

                'stops' =>
                    $stops,

                'payment_methods' =>
                    $paymentMethods,
            ]

        ], 200);
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


    /*
    |--------------------------------------------------------------------------
    | Get Rider Profile
    |--------------------------------------------------------------------------
    */
    public function showProfile(Request $request)
    {
        try {

            $user = $request->user();

            if (
                !$user ||
                $user->role !== 'rider'
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'Rider access only.',
                ], 403);
            }


            $profile =
                DB::table('users as u')

                    ->leftJoin(
                        'rider_profiles as rp',
                        'rp.user_id',
                        '=',
                        'u.id'
                    )

                    ->where(
                        'u.id',
                        $user->id
                    )

                    ->select(
                        'u.id',
                        'u.full_name',
                        'u.email',
                        'u.country_code',
                        'u.phone_number',
                        'u.role',

                        'rp.address',
                        'rp.profile_photo',
                        'rp.verification_status',
                        'rp.rejection_reason'
                    )

                    ->first();


            if (!$profile) {

                return response()->json([
                    'status' => false,
                    'message' => 'Rider profile not found.',
                ], 404);
            }


            return response()->json([
                'status' => true,
                'data' => $profile,
            ]);


        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to load rider profile.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Rider Profile
    |--------------------------------------------------------------------------
    */

    public function updateProfile(Request $request)
    {
        try {

            $user = $request->user();


            if (
                !$user ||
                $user->role !== 'rider'
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'Rider access only.',
                ], 403);
            }


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'full_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'nullable',
                    'email',
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

                'address' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'profile_photo' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Duplicate Email Check
            |--------------------------------------------------------------------------
            */

            if (
                $request->filled(
                    'email'
                )
            ) {

                $emailExists =
                    DB::table('users')

                        ->where(
                            'email',
                            $request->email
                        )

                        ->where(
                            'id',
                            '!=',
                            $user->id
                        )

                        ->exists();


                if ($emailExists) {

                    return response()->json([
                        'status' => false,
                        'message' =>
                            'This email address is already registered.',
                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Duplicate Phone Check
            |--------------------------------------------------------------------------
            */

            $countryCode =
                $request->has(
                    'country_code'
                )
                    ? $request->country_code
                    : $user->country_code;


            $phoneNumber =
                $request->has(
                    'phone_number'
                )
                    ? $request->phone_number
                    : $user->phone_number;


            if (
                $countryCode &&
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
            | Update Users Table
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
                    'email'
                )
            ) {

                $userData[
                    'email'
                ] =
                    $request->email;
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
            | Rider Profile
            |--------------------------------------------------------------------------
            */

            $riderProfile =
                DB::table(
                    'rider_profiles'
                )

                ->where(
                    'user_id',
                    $user->id
                )

                ->first();


            $profileData = [];


            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            if (
                $request->has(
                    'address'
                )
            ) {

                $profileData[
                    'address'
                ] =
                    trim(
                        $request->address
                    );
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

                /*
                * Delete old image
                */

                if (
                    $riderProfile &&
                    !empty(
                        $riderProfile
                            ->profile_photo
                    )
                ) {

                    Storage::disk(
                        'public'
                    )
                    ->delete(
                        $riderProfile
                            ->profile_photo
                    );
                }


                $profilePhoto =
                    $request
                        ->file(
                            'profile_photo'
                        )

                        ->store(
                            'riders/profile',
                            'public'
                        );


                $profileData[
                    'profile_photo'
                ] =
                    $profilePhoto;
            }


            /*
            |--------------------------------------------------------------------------
            | Update / Insert Rider Profile
            |--------------------------------------------------------------------------
            */

            if (
                !empty(
                    $profileData
                )
            ) {

                $profileData[
                    'updated_at'
                ] =
                    now();


                if ($riderProfile) {

                    DB::table(
                        'rider_profiles'
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
                        'verification_status'
                    ] =
                        'pending';


                    $profileData[
                        'rejection_reason'
                    ] =
                        null;


                    $profileData[
                        'created_at'
                    ] =
                        now();


                    DB::table(
                        'rider_profiles'
                    )

                    ->insert(
                        $profileData
                    );
                }
            }


            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Updated Profile Response
            |--------------------------------------------------------------------------
            */

            $profile =
                DB::table('users as u')

                    ->leftJoin(
                        'rider_profiles as rp',
                        'rp.user_id',
                        '=',
                        'u.id'
                    )

                    ->where(
                        'u.id',
                        $user->id
                    )

                    ->select(
                        'u.id',
                        'u.role',
                        'u.full_name',
                        'u.email',
                        'u.country_code',
                        'u.phone_number',

                        'rp.address',
                        'rp.profile_photo',
                        'rp.verification_status',
                        'rp.rejection_reason'
                    )

                    ->first();


            $profile->profile_photo_url =
                !empty(
                    $profile->profile_photo
                )
                    ? asset(
                        'storage/'
                        . $profile->profile_photo
                    )
                    : null;


            return response()->json([
                'status' => true,
                'message' =>
                    'Rider profile updated successfully.',
                'data' =>
                    $profile,
            ]);


        } catch (
            \Illuminate\Validation\ValidationException $e
        ) {

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
                    'Unable to update rider profile.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}