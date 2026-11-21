<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripTransactionController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Driver payment methods
        |--------------------------------------------------------------------------
        |
        | Converts:
        |
        | driver_id 1:
        | Cash
        | PayPal
        | Venmo
        |
        | into:
        |
        | Cash, PayPal, Venmo
        |
        */

        $paymentMethodsSub = DB::table(
            'driver_payment_methods as dpm'
        )
        ->join(
            'payment_methods as pm',
            'pm.id',
            '=',
            'dpm.payment_method_id'
        )
        ->select(
            'dpm.user_id',
            DB::raw(
                "GROUP_CONCAT(
                    DISTINCT pm.name
                    ORDER BY pm.name
                    SEPARATOR ', '
                ) as payment_methods"
            )
        )
        ->where('pm.is_active', 1)
        ->groupBy('dpm.user_id');


        /*
        |--------------------------------------------------------------------------
        | Base Transaction Query
        |--------------------------------------------------------------------------
        */

        $query = DB::table(
            'trip_riders'
        )

        ->join(
            'trips',
            'trips.id',
            '=',
            'trip_riders.trip_id'
        )

        ->join(
            'users as riders',
            'riders.id',
            '=',
            'trip_riders.rider_id'
        )

        ->join(
            'users as drivers',
            'drivers.id',
            '=',
            'trips.driver_id'
        )

        ->leftJoinSub(
            $paymentMethodsSub,
            'driver_methods',
            function ($join) {

                $join->on(
                    'driver_methods.user_id',
                    '=',
                    'drivers.id'
                );

            }
        );


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
                        'riders.full_name',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'drivers.full_name',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'trip_riders.trip_id',
                        'like',
                        '%' . $search . '%'
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from')) {

            $query->whereDate(
                'trips.trip_date',
                '>=',
                $request->from
            );

        }


        if ($request->filled('to')) {

            $query->whereDate(
                'trips.trip_date',
                '<=',
                $request->to
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Listing
        |--------------------------------------------------------------------------
        */

        $transactions = $query
            ->select([
                'trip_riders.id',
                'trip_riders.trip_id',
                'trip_riders.rider_id',
                'trip_riders.fare_amount',
                'trip_riders.booking_status',
                'trip_riders.ride_status',

                'trips.driver_id',
                'trips.trip_date',
                'trips.status as trip_status',

                'riders.full_name as rider_name',
                'drivers.full_name as driver_name',

                'driver_methods.payment_methods',
            ])

            /*
             * Transactions normally mean successfully
             * completed rides.
             */
            ->where(
                'trip_riders.booking_status',
                'completed'
            )

            ->orderByDesc(
                'trips.trip_date'
            )

            ->orderByDesc(
                'trip_riders.id'
            )

            ->paginate(20)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */

        $statsQuery = DB::table(
            'trip_riders'
        )
        ->join(
            'trips',
            'trips.id',
            '=',
            'trip_riders.trip_id'
        )
        ->where(
            'trip_riders.booking_status',
            'completed'
        );


        if ($request->filled('from')) {

            $statsQuery->whereDate(
                'trips.trip_date',
                '>=',
                $request->from
            );

        }


        if ($request->filled('to')) {

            $statsQuery->whereDate(
                'trips.trip_date',
                '<=',
                $request->to
            );

        }


        $totalRevenue = (
            clone $statsQuery
        )->sum(
            'trip_riders.fare_amount'
        );


        $totalTransactions = (
            clone $statsQuery
        )->count();


        $averageFare = (
            clone $statsQuery
        )->avg(
            'trip_riders.fare_amount'
        );


        return view(
            'admin.trip-transactions.index',
            compact(
                'transactions',
                'totalRevenue',
                'totalTransactions',
                'averageFare'
            )
        );
    }

    private function transactionQuery(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Driver Payment Methods
        |--------------------------------------------------------------------------
        */

        $paymentMethodsSub =
            DB::table(
                'driver_payment_methods as dpm'
            )

            ->join(
                'payment_methods as pm',
                'pm.id',
                '=',
                'dpm.payment_method_id'
            )

            ->select(
                'dpm.user_id',

                DB::raw(
                    "GROUP_CONCAT(
                        DISTINCT pm.name
                        ORDER BY pm.name
                        SEPARATOR ', '
                    ) as payment_methods"
                )
            )

            ->where(
                'pm.is_active',
                1
            )

            ->groupBy(
                'dpm.user_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query =
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
                'users as rider',
                'rider.id',
                '=',
                'tr.rider_id'
            )

            ->join(
                'users as driver',
                'driver.id',
                '=',
                't.driver_id'
            )

            ->leftJoinSub(
                $paymentMethodsSub,
                'driver_methods',
                function ($join) {

                    $join->on(
                        'driver_methods.user_id',
                        '=',
                        'driver.id'
                    );
                }
            )

            ->select(
                'tr.id as trip_rider_id',

                't.id as trip_id',
                't.trip_date',

                'rider.full_name as rider_name',
                'driver.full_name as driver_name',

                'tr.fare_amount',
                'tr.booking_status',

                'driver_methods.payment_methods'
            )


            /*
            |--------------------------------------------------------------------------
            | Completed Transactions Only
            |--------------------------------------------------------------------------
            */

            ->where(
                'tr.booking_status',
                'completed'
            );


        /*
        |--------------------------------------------------------------------------
        | From Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from')) {

            $query->whereDate(
                't.trip_date',
                '>=',
                $request->from
            );
        }


        /*
        |--------------------------------------------------------------------------
        | To Date
        |--------------------------------------------------------------------------
        */

        if ($request->filled('to')) {

            $query->whereDate(
                't.trip_date',
                '<=',
                $request->to
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('q')) {

            $search =
                trim(
                    $request->q
                );


            $query->where(
                function ($q) use ($search) {

                    $q
                        ->where(
                            't.id',
                            'like',
                            '%' . $search . '%'
                        )

                        ->orWhere(
                            'rider.full_name',
                            'like',
                            '%' . $search . '%'
                        )

                        ->orWhere(
                            'driver.full_name',
                            'like',
                            '%' . $search . '%'
                        );
                }
            );
        }


        return $query;
    }

    public function exportCsv(Request $request)
    {
        $request->validate([

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],

            'q' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);


        $transactions =
            $this->transactionQuery(
                $request
            )

            ->orderByDesc(
                't.trip_date'
            )

            ->orderByDesc(
                't.id'
            )

            ->get();


        $fileName =
            'trip-transactions-'
            . now()->format(
                'Y-m-d-His'
            )
            . '.csv';


        return response()->streamDownload(
            function () use ($transactions) {

                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );


                /*
                |--------------------------------------------------------------------------
                | UTF-8 BOM
                |--------------------------------------------------------------------------
                */

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );


                /*
                |--------------------------------------------------------------------------
                | CSV Header
                |--------------------------------------------------------------------------
                */

                fputcsv(
                    $handle,
                    [
                        'Trip ID',
                        'Date',
                        'Rider',
                        'Driver',
                        'Payment Method',
                        'Fare',
                        'Status',
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | CSV Rows
                |--------------------------------------------------------------------------
                */

                foreach ($transactions as $transaction) {

                    $status =
                        $transaction->booking_status === 'completed'
                            ? 'Paid'
                            : ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $transaction->booking_status
                                )
                            );


                    fputcsv(
                        $handle,
                        [
                            $transaction->trip_id,

                            $transaction->trip_date,

                            $transaction->rider_name,

                            $transaction->driver_name,

                            $transaction->payment_methods
                                ?? '',

                            number_format(
                                (float)
                                $transaction->fare_amount,
                                2,
                                '.',
                                ''
                            ),

                            $status,
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