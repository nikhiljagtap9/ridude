<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkplaceController extends Controller
{
    public function index(Request $request)
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Optional Search
            |--------------------------------------------------------------------------
            */

            $search =
                $request->filled('search')
                    ? trim($request->search)
                    : null;


            /*
            |--------------------------------------------------------------------------
            | Workplace + Branch Query
            |--------------------------------------------------------------------------
            */

            $query =
                DB::table('workplace_branches as wb')

                    ->join(
                        'workplaces as w',
                        'w.id',
                        '=',
                        'wb.workplace_id'
                    )

                    ->where(
                        'w.is_active',
                        1
                    )

                    ->where(
                        'wb.is_active',
                        1
                    );


            /*
            |--------------------------------------------------------------------------
            | Search Company Name OR Address
            |--------------------------------------------------------------------------
            */

            if ($search) {

                $query->where(
                    function ($q) use ($search) {

                        $q->where(
                            'w.company_name',
                            'like',
                            '%' . $search . '%'
                        )

                        ->orWhere(
                            'wb.address',
                            'like',
                            '%' . $search . '%'
                        );
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Get Data
            |--------------------------------------------------------------------------
            */

            $results =
                $query

                    ->select(

                        'w.id as workplace_id',

                        'w.company_name',

                     //   'wb.id as branch_id',

                        'wb.address',

                        'wb.latitude',

                        'wb.longitude'
                    )

                    ->orderBy(
                        'w.company_name',
                        'asc'
                    )

                    ->orderBy(
                        'wb.address',
                        'asc'
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
                    'Workplaces fetched successfully.',

                'search' =>
                    $search,

                'total' =>
                    $results->count(),

                'data' =>
                    $results,

            ]);


        } catch (\Throwable $e) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Unable to fetch workplaces.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }
}
