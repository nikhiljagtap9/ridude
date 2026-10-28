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
}