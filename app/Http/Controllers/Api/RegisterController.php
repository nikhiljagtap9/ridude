<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $uploadedFiles = [];

        $plan = null;
        $trialStart = null;
        $trialEnd = null;

        try {

            /*
            * --------------------------------------------------
            * VALIDATION
            * --------------------------------------------------
            */
            $request->validate([

                /*
                * Account
                */
                'role' => [
                    'required',
                    Rule::in(['driver', 'rider'])
                ],

                /*
                * Driver Subscription
                */
                'subscription_plan_id' => [
                    'required_if:role,driver',
                    'nullable',
                    'integer',
                    Rule::exists(
                        'subscription_plans',
                        'id'
                    )->where(function ($query) {
                        $query->where('is_active', 1);
                    })
                ],

                'full_name' => [
                    'required',
                    'string',
                    'max:150'
                ],

                'email' => [
                    'required',
                    'email',
                    'max:150',
                    'unique:users,email'
                ],

                'country_code' => [
                    'required',
                    'string',
                    'max:10'
                ],

                'phone_number' => [
                    'required',
                    'string',
                    'min:6',
                    'max:20'
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed'
                ],

                'terms_accepted' => [
                    'required',
                    'accepted'
                ],

                /*
                * --------------------------------------------------
                * DRIVER PERSONAL INFORMATION
                * --------------------------------------------------
                */
                'date_of_birth' => [
                    'required_if:role,driver',
                    'nullable',
                    'date',
                    'before:today'
                ],

                'gender' => [
                    'required_if:role,driver',
                    'nullable',
                    Rule::in([
                        'male',
                        'female',
                        'other'
                    ])
                ],

                'address' => [
                    'required',
                    'nullable',
                    'string',
                    'max:255'
                ],

                'city' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:100'
                ],

                'state' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:100'
                ],

                'zip_code' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:20'
                ],

                /*
                * --------------------------------------------------
                * PROFILE PHOTO
                * --------------------------------------------------
                */
                'profile_photo' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png',
                    'max:5120'
                ],

                /*
                * --------------------------------------------------
                * VEHICLE
                * --------------------------------------------------
                */
                'car_make' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:100'
                ],

                'car_model' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:100'
                ],

                'car_year' => [
                    'required_if:role,driver',
                    'nullable',
                    'integer',
                    'digits:4',
                    'min:1900',
                    'max:' . date('Y')
                ],

                'car_color' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:50'
                ],

                'license_plate_number' => [
                    'required_if:role,driver',
                    'nullable',
                    'string',
                    'max:50',
                    'unique:driver_vehicles,license_plate_number'
                ],

                'car_front_photo' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png',
                    'max:5120'
                ],

                'car_back_photo' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png',
                    'max:5120'
                ],

                /*
                * --------------------------------------------------
                * PAYMENT METHODS
                * --------------------------------------------------
                */
                'payment_method_ids' => [
                    'required_if:role,driver',
                    'nullable',
                    'array',
                    'min:1'
                ],

                'payment_method_ids.*' => [
                    'integer',
                    Rule::exists(
                        'payment_methods',
                        'id'
                    )->where(function ($query) {
                        $query->where('is_active', 1);
                    })
                ],

                /*
                * --------------------------------------------------
                * DRIVER LICENSE
                * --------------------------------------------------
                */
                'driver_license_front' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                'driver_license_back' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                /*
                * --------------------------------------------------
                * CAR REGISTRATION
                * --------------------------------------------------
                */
                'car_registration_front' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                'car_registration_back' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                /*
                * --------------------------------------------------
                * CAR INSURANCE
                * --------------------------------------------------
                */
                'car_insurance_front' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                'car_insurance_back' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                /*
                * --------------------------------------------------
                * CAR INSPECTION
                * --------------------------------------------------
                */
                'car_inspection' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                /*
                * --------------------------------------------------
                * WORK ID
                * --------------------------------------------------
                */
                'work_id_front' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

                'work_id_back' => [
                    'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,pdf',
                    'max:5120'
                ],

            ]);


            /*
            * --------------------------------------------------
            * CHECK DUPLICATE PHONE
            * --------------------------------------------------
            */
            $phoneExists = User::where(
                    'country_code',
                    $request->country_code
                )
                ->where(
                    'phone_number',
                    $request->phone_number
                )
                ->exists();

            if ($phoneExists) {

                return response()->json([
                    'status' => false,

                    'message' =>
                        'This phone number is already registered.',

                    'errors' => [
                        'phone_number' => [
                            'This phone number is already registered.'
                        ]
                    ]
                ], 422);
            }


            /*
            * --------------------------------------------------
            * START TRANSACTION
            * --------------------------------------------------
            */
            DB::beginTransaction();


            /*
            * --------------------------------------------------
            * CREATE USER
            * --------------------------------------------------
            *
            * Password will automatically hash because
            * User model has:
            *
            * 'password' => 'hashed'
            *
            */
            $user = User::create([

                'role' =>
                    $request->role,

                'full_name' =>
                    trim($request->full_name),

                'email' =>
                    strtolower(
                        trim($request->email)
                    ),

                'country_code' =>
                    trim($request->country_code),

                'phone_number' =>
                    trim($request->phone_number),

                'password' =>
                    $request->password,

                'terms_accepted' =>
                    true,

                'status' => 'under_review',

                'submitted_at' =>
                    now(),
            ]);


            /*
            * ==================================================
            * DRIVER REGISTRATION
            * ==================================================
            */
            if ($request->role === 'driver') {

                /*
                * --------------------------------------------------
                * SUBSCRIPTION PLAN
                * --------------------------------------------------
                */
                $plan = DB::table('subscription_plans')
                    ->where(
                        'id',
                        $request->subscription_plan_id
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->first();

                if (!$plan) {
                    throw new \Exception(
                        'Selected subscription plan is invalid or inactive.'
                    );
                }


                /*
                * Trial dates
                */
                $trialStart = now();

                $trialEnd = now()
                    ->copy()
                    ->addDays(
                        (int) $plan->trial_days
                    );


                /*
                * Save subscription
                */
                DB::table('user_subscriptions')->insert([

                    'user_id' =>
                        $user->id,

                    'subscription_plan_id' =>
                        $plan->id,

                    /*
                    * IMPORTANT:
                    * Take price from DB,
                    * not mobile app request.
                    */
                    'price' =>
                        $plan->price,

                    'currency' =>
                        $plan->currency,

                    'billing_cycle' =>
                        $plan->billing_cycle,

                    'trial_start_at' =>
                        $trialStart,

                    'trial_end_at' =>
                        $trialEnd,

                    /*
                    * Paid subscription starts
                    * after trial / store confirmation.
                    */
                    'subscription_start_at' =>
                        null,

                    'subscription_end_at' =>
                        null,

                    'status' =>
                        'trial',

                    'auto_renew' =>
                        true,

                    'store' =>
                        null,

                    'transaction_id' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


                /*
                * --------------------------------------------------
                * PROFILE PHOTO
                * --------------------------------------------------
                */
                $profilePhoto = $this->uploadFile(
                    $request,
                    'profile_photo',
                    'drivers/profile',
                    $uploadedFiles
                );


                /*
                * --------------------------------------------------
                * DRIVER PROFILE
                * --------------------------------------------------
                */
                DB::table('driver_profiles')->insert([

                    'user_id' =>
                        $user->id,

                    'date_of_birth' =>
                        $request->date_of_birth,

                    'gender' =>
                        $request->gender,

                    'address' =>
                        $request->address,

                    'city' =>
                        $request->city,

                    'state' =>
                        $request->state,

                    'zip_code' =>
                        $request->zip_code,

                    'profile_photo' =>
                        $profilePhoto,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


                /*
                * --------------------------------------------------
                * VEHICLE PHOTOS
                * --------------------------------------------------
                */
                $carFrontPhoto = $this->uploadFile(
                    $request,
                    'car_front_photo',
                    'drivers/cars',
                    $uploadedFiles
                );

                $carBackPhoto = $this->uploadFile(
                    $request,
                    'car_back_photo',
                    'drivers/cars',
                    $uploadedFiles
                );


                /*
                * --------------------------------------------------
                * VEHICLE
                * --------------------------------------------------
                */
                DB::table('driver_vehicles')->insert([

                    'user_id' =>
                        $user->id,

                    'car_make' =>
                        $request->car_make,

                    'car_model' =>
                        $request->car_model,

                    'car_year' =>
                        $request->car_year,

                    'car_color' =>
                        $request->car_color,

                    'license_plate_number' =>
                        strtoupper(
                            trim(
                                $request->license_plate_number
                            )
                        ),

                    'front_photo' =>
                        $carFrontPhoto,

                    'back_photo' =>
                        $carBackPhoto,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


                /*
                * --------------------------------------------------
                * DRIVER PAYMENT METHODS
                * --------------------------------------------------
                */
                if (!empty($request->payment_method_ids)) {

                    foreach (
                        array_unique(
                            $request->payment_method_ids
                        ) as $paymentMethodId
                    ) {

                        DB::table(
                            'driver_payment_methods'
                        )->insert([

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


                /*
                * --------------------------------------------------
                * DRIVER DOCUMENTS
                * --------------------------------------------------
                */

                /*
                * Driver License
                */
                $this->saveDocument(
                    $request,
                    $user->id,
                    'driver_license',
                    'driver_license_front',
                    'driver_license_back',
                    $uploadedFiles
                );


                /*
                * Car Registration
                */
                $this->saveDocument(
                    $request,
                    $user->id,
                    'car_registration',
                    'car_registration_front',
                    'car_registration_back',
                    $uploadedFiles
                );


                /*
                * Car Insurance
                */
                $this->saveDocument(
                    $request,
                    $user->id,
                    'car_insurance',
                    'car_insurance_front',
                    'car_insurance_back',
                    $uploadedFiles
                );


                /*
                * Car Inspection
                */
                $this->saveDocument(
                    $request,
                    $user->id,
                    'car_inspection',
                    'car_inspection',
                    null,
                    $uploadedFiles
                );


                /*
                * Work ID
                */
                $this->saveDocument(
                    $request,
                    $user->id,
                    'work_id',
                    'work_id_front',
                    'work_id_back',
                    $uploadedFiles
                );
            }


            /*
            * ==================================================
            * RIDER
            * ==================================================
            *
            * Add Rider tables later.
            */
            if ($request->role === 'rider') {

                /*
                * --------------------------------------------------
                * PROFILE PHOTO
                * --------------------------------------------------
                */
                $profilePhoto = $this->uploadFile(
                    $request,
                    'profile_photo',
                    'riders/profile',
                    $uploadedFiles
                );

                /*
                * --------------------------------------------------
                * RIDER PROFILE
                * --------------------------------------------------
                */
                DB::table('rider_profiles')->insert([

                    'user_id' =>
                        $user->id,

                    'address' =>
                        trim($request->address),

                    'profile_photo' =>
                        $profilePhoto,

                    'verification_status' =>
                        'pending',

                    'rejection_reason' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

            }


            /*
            * --------------------------------------------------
            * CREATE SANCTUM TOKEN
            * --------------------------------------------------
            */
            $token = $user
                ->createToken(
                    'ridude-mobile-app'
                )
                ->plainTextToken;


            /*
            * --------------------------------------------------
            * COMMIT TRANSACTION
            * --------------------------------------------------
            */
            DB::commit();


            /*
            * --------------------------------------------------
            * SUCCESS RESPONSE
            * --------------------------------------------------
            */
            $responseData = [

                'user_id' =>
                    $user->id,

                'role' =>
                    $user->role,

                'full_name' =>
                    $user->full_name,

                'email' =>
                    $user->email,

                'country_code' =>
                    $user->country_code,

                'phone_number' =>
                    $user->phone_number,

                'status' =>
                    $user->status,

                'token_type' =>
                    'Bearer',

                'token' =>
                    $token,
            ];


            /*
            * Add subscription data
            * only for Driver
            */
            if (
                $request->role === 'driver'
                && $plan
            ) {

                $responseData['subscription'] = [

                    'plan_id' =>
                        $plan->id,

                    'plan_name' =>
                        $plan->name,

                    'price' =>
                        $plan->price,

                    'currency' =>
                        $plan->currency,

                    'billing_cycle' =>
                        $plan->billing_cycle,

                    'trial_days' =>
                        $plan->trial_days,

                    'trial_start_at' =>
                        $trialStart
                            ? $trialStart->toDateTimeString()
                            : null,

                    'trial_end_at' =>
                        $trialEnd
                            ? $trialEnd->toDateTimeString()
                            : null,

                    'status' =>
                        'trial',
                ];
            }


            return response()->json([

                'status' =>
                    true,

                'message' =>
                    $request->role === 'driver'
                        ? 'Driver registration submitted successfully.'
                        : 'Rider registration completed successfully.',

                'data' =>
                    $responseData,

            ], 201);


        } catch (ValidationException $e) {

            /*
            * --------------------------------------------------
            * VALIDATION ERROR
            * --------------------------------------------------
            */
            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (Throwable $e) {

            /*
            * --------------------------------------------------
            * ROLLBACK DATABASE
            * --------------------------------------------------
            */
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }


            /*
            * --------------------------------------------------
            * DELETE ALREADY UPLOADED FILES
            * --------------------------------------------------
            */
            foreach ($uploadedFiles as $file) {

                Storage::disk('public')
                    ->delete($file);
            }


            /*
            * --------------------------------------------------
            * ERROR LOG
            * --------------------------------------------------
            */
            \Log::error(
                'RIDUDE Registration Error',
                [
                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );


            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Registration failed. Please try again.',

                /*
                * TEMPORARY FOR DEVELOPMENT
                * Remove these three in production.
                */
                'error' =>
                    $e->getMessage(),

                'file' =>
                    $e->getFile(),

                'line' =>
                    $e->getLine(),

            ], 500);
        }
    }


    private function uploadFile(
        Request $request,
        string $fieldName,
        string $directory,
        array &$uploadedFiles
    ): ?string {

        if (!$request->hasFile($fieldName)) {
            return null;
        }

        $path = $request
            ->file($fieldName)
            ->store(
                $directory,
                'public'
            );

        $uploadedFiles[] = $path;

        return $path;
    }


    private function saveDocument(
        Request $request,
        int $userId,
        string $documentType,
        string $frontField,
        ?string $backField,
        array &$uploadedFiles
    ): void {

        $frontFile = $this->uploadFile(
            $request,
            $frontField,
            'drivers/documents',
            $uploadedFiles
        );

        $backFile = null;

        if ($backField) {
            $backFile = $this->uploadFile(
                $request,
                $backField,
                'drivers/documents',
                $uploadedFiles
            );
        }

        if (!$frontFile && !$backFile) {
            return;
        }

        DB::table('driver_documents')->insert([
            'user_id' =>
                $userId,

            'document_type' =>
                $documentType,

            'front_file' =>
                $frontFile,

            'back_file' =>
                $backFile,

            'verification_status' =>
                'pending',

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);
    }
}