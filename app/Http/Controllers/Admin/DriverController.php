<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Current Filter
        |--------------------------------------------------------------------------
        */

        $status = $request->get(
            'status',
            'pending'
        );


        /*
        |--------------------------------------------------------------------------
        | Driver Counts
        |--------------------------------------------------------------------------
        */

        $totalDrivers = DB::table('users')
            ->where('role', 'driver')
            ->count();


        $pendingDrivers = DB::table('users')
            ->where('role', 'driver')
            ->where('status', 'under_review')
            ->count();


        $approvedDrivers = DB::table('users')
            ->where('role', 'driver')
            ->where('status', 'approved')
            ->count();


        $rejectedDrivers = DB::table('users')
            ->where('role', 'driver')
            ->where('status', 'rejected')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Driver Query
        |--------------------------------------------------------------------------
        */

        $query = DB::table('users')

            ->leftJoin(
                'driver_profiles',
                'driver_profiles.user_id',
                '=',
                'users.id'
            )

            ->leftJoin(
                'driver_vehicles',
                'driver_vehicles.user_id',
                '=',
                'users.id'
            )

            ->where(
                'users.role',
                'driver'
            );


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($status === 'pending') {

            $query->where(
                'users.status',
                'under_review'
            );

        } elseif (
            in_array(
                $status,
                [
                    'approved',
                    'rejected'
                ]
            )
        ) {

            $query->where(
                'users.status',
                $status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('q')) {

            $search = trim(
                $request->q
            );

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'users.full_name',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'users.email',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'users.phone_number',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'driver_vehicles.car_make',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'driver_vehicles.car_model',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'driver_vehicles.license_plate_number',
                        'like',
                        '%' . $search . '%'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Listing
        |--------------------------------------------------------------------------
        */

        $drivers = $query
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.country_code',
                'users.phone_number',
                'users.status',
                'users.created_at',

                'driver_profiles.profile_photo',

                'driver_vehicles.car_make',
                'driver_vehicles.car_model',
                'driver_vehicles.car_year',
                'driver_vehicles.car_color',
                'driver_vehicles.license_plate_number',
            ])

            ->orderByDesc(
                'users.created_at'
            )

            ->paginate(20)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | AJAX
        |--------------------------------------------------------------------------
        */

        if ($request->ajax()) {

            return view(
                'admin.drivers.partials.list',
                compact(
                    'drivers',
                    'status'
                )
            );
        }


        return view(
            'admin.drivers.index',
            compact(
                'drivers',
                'status',
                'totalDrivers',
                'pendingDrivers',
                'approvedDrivers',
                'rejectedDrivers'
            )
        );
    }


    public function details(int $id)
    {
        $driver = DB::table('users')
            ->leftJoin(
                'driver_profiles',
                'driver_profiles.user_id',
                '=',
                'users.id'
            )
            ->leftJoin(
                'driver_vehicles',
                'driver_vehicles.user_id',
                '=',
                'users.id'
            )
            ->where('users.id', $id)
            ->where('users.role', 'driver')
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.country_code',
                'users.phone_number',
                'users.status',
                'users.created_at',

                'driver_profiles.date_of_birth',
                'driver_profiles.gender',
                'driver_profiles.address',
                'driver_profiles.city',
                'driver_profiles.state',
                'driver_profiles.zip_code',
                'driver_profiles.profile_photo',

                'driver_vehicles.car_make',
                'driver_vehicles.car_model',
                'driver_vehicles.car_year',
                'driver_vehicles.car_color',
                'driver_vehicles.license_plate_number',
                'driver_vehicles.front_photo',
                'driver_vehicles.back_photo',
            ])
            ->first();


        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Driver not found.',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Documents
        |--------------------------------------------------------------------------
        */

        $documents = DB::table('driver_documents')
            ->where('user_id', $id)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Payment Methods
        |--------------------------------------------------------------------------
        */

        $paymentMethods = DB::table('driver_payment_methods as dpm')
            ->join(
                'payment_methods as pm',
                'pm.id',
                '=',
                'dpm.payment_method_id'
            )
            ->where('dpm.user_id', $id)
            ->where('pm.is_active', 1)
            ->orderBy('pm.name')
            ->pluck('pm.name');


        return view(
            'admin.drivers.partials.details',
            compact(
                'driver',
                'documents',
                'paymentMethods'
            )
        );
    }


    public function updateStatus(
        Request $request,
        int $id
    ) {
        $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'approved',
                    'rejected'
                ])
            ]
        ]);


        $driver = DB::table('users')
            ->where('id', $id)
            ->where('role', 'driver')
            ->first();


        if (!$driver) {

            return response()->json([
                'status' => false,
                'message' => 'Driver not found.'
            ], 404);
        }


        $update = [
            'status' => $request->status,
            'updated_at' => now(),
        ];


        if ($request->status === 'approved') {

            $update['approved_at'] = now();

            $update['rejected_at'] = null;

            $update['rejection_reason'] = null;

        } else {

            $update['rejected_at'] = now();

            $update['approved_at'] = null;

            $update['rejection_reason'] =
                $request->input(
                    'reason'
                );
        }


        DB::table('users')
            ->where('id', $id)
            ->update($update);


        return response()->json([
            'status' => true,

            'message' =>
                $request->status === 'approved'
                    ? 'Driver approved successfully.'
                    : 'Driver rejected successfully.'
        ]);
    }


    

    public function driverSubscriptions()
    {
        $drivers =
            DB::table('users as u')

            ->join(
                'user_subscriptions as us',
                'us.user_id',
                '=',
                'u.id'
            )

            ->where(
                'u.role',
                'driver'
            )

            ->select(

                'u.id',

                'u.full_name',

                DB::raw(
                    'COUNT(us.id) as payments_count'
                ),

                DB::raw(
                    'SUM(us.price) as total_amount'
                ),

                DB::raw(
                    'MAX(us.currency) as currency'
                )
            )

            ->groupBy(
                'u.id',
                'u.full_name'
            )

            ->orderBy(
                'u.full_name'
            )

            ->get();


        $subscriptions =
            DB::table(
                'user_subscriptions as us'
            )

            ->join(
                'users as u',
                'u.id',
                '=',
                'us.user_id'
            )

            ->join(
                'subscription_plans as sp',
                'sp.id',
                '=',
                'us.subscription_plan_id'
            )

            ->where(
                'u.role',
                'driver'
            )

            ->select(

                'us.id',

                'us.user_id',

                'u.full_name as driver_name',

                'sp.name as plan_name',

                'sp.slug as plan_slug',

                'us.price',

                'us.currency',

                'us.billing_cycle',

                'us.trial_start_at',

                'us.trial_end_at',

                'us.subscription_start_at',

                'us.subscription_end_at',

                'us.status',

                'us.auto_renew',

                'us.store',

                'us.transaction_id',

                'us.created_at'
            )

            ->orderByDesc(
                'us.id'
            )

            ->get();


        return view(
            'admin.driver-subscriptions.index',
            compact(
                'drivers',
                'subscriptions'
            )
        );
    }

    public function exportDriverSubscriptions(Request $request)
    {
        $query =
            DB::table('user_subscriptions as us')

            ->join(
                'users as u',
                'u.id',
                '=',
                'us.user_id'
            )

            ->join(
                'subscription_plans as sp',
                'sp.id',
                '=',
                'us.subscription_plan_id'
            )

            ->where(
                'u.role',
                'driver'
            )

            ->select(
                'us.id',
                'us.user_id',

                'u.full_name as driver_name',
                'u.email as driver_email',

                'sp.name as plan_name',

                'us.price',
                'us.currency',
                'us.billing_cycle',

                'us.trial_start_at',
                'us.trial_end_at',

                'us.subscription_start_at',
                'us.subscription_end_at',

                'us.status',
                'us.auto_renew',
                'us.store',
                'us.transaction_id',

                'us.created_at'
            );


        /*
        |--------------------------------------------------------------------------
        | Driver Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('driver_id')) {

            $query->where(
                'us.user_id',
                $request->driver_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Plan Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('billing_cycle')
            &&
            in_array(
                $request->billing_cycle,
                [
                    'week',
                    'month',
                ],
                true
            )
        ) {

            $query->where(
                'us.billing_cycle',
                $request->billing_cycle
            );
        }


        $subscriptions =
            $query
                ->orderByDesc('us.id')
                ->get();


        $fileName =
            'driver-subscriptions-'
            . now()->format('Y-m-d-His')
            . '.csv';


        return response()->streamDownload(
            function () use ($subscriptions) {

                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );


                /*
                |--------------------------------------------------------------------------
                | Header
                |--------------------------------------------------------------------------
                */

                fputcsv(
                    $handle,
                    [
                        'Subscription ID',
                        'Driver',
                        'Email',
                        'Plan',
                        'Billing Cycle',
                        'Amount',
                        'Currency',
                        'Start Date',
                        'End Date',
                        'Status',
                        'Auto Renew',
                        'Store',
                        'Transaction ID',
                        'Created At',
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | Rows
                |--------------------------------------------------------------------------
                */

                foreach ($subscriptions as $subscription) {

                    $startDate =
                        $subscription->subscription_start_at
                        ?? $subscription->trial_start_at;


                    $endDate =
                        $subscription->subscription_end_at
                        ?? $subscription->trial_end_at;


                    fputcsv(
                        $handle,
                        [
                            'SUB-' . str_pad(
                                $subscription->id,
                                4,
                                '0',
                                STR_PAD_LEFT
                            ),

                            $subscription->driver_name,

                            $subscription->driver_email,

                            $subscription->plan_name,

                            ucfirst(
                                $subscription->billing_cycle
                            ),

                            number_format(
                                (float) $subscription->price,
                                2,
                                '.',
                                ''
                            ),

                            $subscription->currency,

                            $startDate,

                            $endDate,

                            ucfirst(
                                $subscription->status
                            ),

                            $subscription->auto_renew
                                ? 'Yes'
                                : 'No',

                            $subscription->store
                                ?? '',

                            $subscription->transaction_id
                                ?? '',

                            $subscription->created_at,
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
                    'text/csv; charset=UTF-8',
            ]
        );
    }
}