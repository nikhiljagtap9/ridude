<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CommonController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | State Listing
    |--------------------------------------------------------------------------
    */

    public function states()
    {
        try {

            $selectedStateIds =
                config(
                    'ridude.selected_state_ids',
                    []
                );


            $states =
                DB::table('states')

                ->whereIn(
                    'id',
                    $selectedStateIds
                )

                ->orderBy(
                    'name',
                    'asc'
                )

                ->select(
                    'id',
                    'name'
                )

                ->get();


            return response()->json([

                'status' =>
                    true,

                'message' =>
                    'States fetched successfully.',

                'data' =>
                    $states,

            ], 200);


        } catch (\Throwable $e) {

            \Log::error(
                'State API Error',
                [
                    'error' =>
                        $e->getMessage(),
                ]
            );


            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Unable to fetch states.',

            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | City Listing
    |--------------------------------------------------------------------------
    */
    public function cities(Request $request)
    {
        try {

            $request->validate([
                'state_id' => [
                    'required',
                    'integer',
                    'exists:states,id',
                ],
            ]);


            $cities =
                DB::table('cities')

                ->where(
                    'state_id',
                    $request->state_id
                )

                ->orderBy(
                    'name',
                    'asc'
                )

                ->select(
                    'id',
                    'name',
                    'state_id'
                )

                ->get();


            return response()->json([

                'status' =>
                    true,

                'message' =>
                    'Cities fetched successfully.',

                'data' =>
                    $cities,

            ], 200);


        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (\Throwable $e) {

            \Log::error(
                'City API Error',
                [
                    'error' =>
                        $e->getMessage(),
                ]
            );


            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Unable to fetch cities.',

            ], 500);
        }
    }
}