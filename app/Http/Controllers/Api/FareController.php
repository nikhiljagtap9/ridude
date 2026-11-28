<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DriverRouteService;
use App\Services\FareCalculationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class FareController extends Controller
{
    protected FareCalculationService $fareService;

    protected DriverRouteService $routeService;


    public function __construct(
        FareCalculationService $fareService,
        DriverRouteService $routeService
    ) {
        $this->fareService =
            $fareService;

        $this->routeService =
            $routeService;
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Fare
    |--------------------------------------------------------------------------
    |
    | Mobile sends:
    |
    | pickup_address
    | pickup_latitude
    | pickup_longitude
    |
    | dropoff_address
    | dropoff_latitude
    | dropoff_longitude
    |
    | Address is only returned for display.
    | Fare calculation uses latitude / longitude.
    |
    */

    public function calculate(
        Request $request
    ) {
        try {

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated =
                $request->validate([

                    /*
                     * Pickup
                     */
                    'pickup_address' => [
                        'nullable',
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


                    /*
                     * Dropoff
                     */
                    'dropoff_address' => [
                        'nullable',
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
            | Calculate Pickup -> Dropoff Route
            |--------------------------------------------------------------------------
            */

            $route =
                $this
                    ->routeService
                    ->calculate(

                        (float)
                        $validated[
                            'pickup_latitude'
                        ],

                        (float)
                        $validated[
                            'pickup_longitude'
                        ],

                        (float)
                        $validated[
                            'dropoff_latitude'
                        ],

                        (float)
                        $validated[
                            'dropoff_longitude'
                        ]
                    );


            /*
            |--------------------------------------------------------------------------
            | Route Failed
            |--------------------------------------------------------------------------
            */

            if (
                empty(
                    $route[
                        'status'
                    ]
                )
            ) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        $route[
                            'message'
                        ]
                        ?? 'Unable to calculate route.',

                    'error' =>
                        $route[
                            'error'
                        ]
                        ?? null,

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Distance
            |--------------------------------------------------------------------------
            */

            $distanceMiles =
                (float)
                (
                    $route[
                        'distance_miles'
                    ]
                    ?? 0
                );


            if (
                $distanceMiles <= 0
            ) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        'Invalid route distance.',

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Fare Calculation
            |--------------------------------------------------------------------------
            */

            $fare =
                $this
                    ->fareService
                    ->calculate(
                        $distanceMiles
                    );


            /*
            |--------------------------------------------------------------------------
            | Fare Failed
            |--------------------------------------------------------------------------
            */

            if (
                empty(
                    $fare[
                        'status'
                    ]
                )
            ) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        $fare[
                            'message'
                        ]
                        ?? 'Unable to calculate fare.',

                    'data' => [

                        'distance_miles' =>
                            round(
                                $distanceMiles,
                                2
                            ),

                        'duration_minutes' =>
                            $route[
                                'duration_minutes'
                            ]
                            ?? null,
                    ],

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' =>
                    true,

                'message' =>
                    'Fare calculated successfully.',

                'data' => [

                    /*
                     * Pickup
                     */

                    'pickup_address' =>
                        $validated[
                            'pickup_address'
                        ]
                        ?? null,

                    'pickup_latitude' =>
                        (float)
                        $validated[
                            'pickup_latitude'
                        ],

                    'pickup_longitude' =>
                        (float)
                        $validated[
                            'pickup_longitude'
                        ],


                    /*
                     * Dropoff
                     */

                    'dropoff_address' =>
                        $validated[
                            'dropoff_address'
                        ]
                        ?? null,

                    'dropoff_latitude' =>
                        (float)
                        $validated[
                            'dropoff_latitude'
                        ],

                    'dropoff_longitude' =>
                        (float)
                        $validated[
                            'dropoff_longitude'
                        ],


                    /*
                     * Route
                     */

                    'distance_miles' =>
                        $fare[
                            'distance_miles'
                        ],

                    'duration_minutes' =>
                        $route[
                            'duration_minutes'
                        ]
                        ?? null,


                    /*
                     * Fare Level
                     */

                    'fare_level_no' =>
                        $fare[
                            'level_no'
                        ],

                    'fare_level_name' =>
                        $fare[
                            'level_name'
                        ],


                    /*
                     * Fare
                     */

                    'base_price' =>
                        $fare[
                            'base_price'
                        ],

                    'price_per_mile' =>
                        $fare[
                            'price_per_mile'
                        ],

                    'fare_amount' =>
                        $fare[
                            'fare_amount'
                        ],

                    'currency' =>
                        'USD',
                ],

            ], 200);


        } catch (
            ValidationException $e
        ) {

            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Validation failed.',

                'errors' =>
                    $e->errors(),

            ], 422);


        } catch (
            Throwable $e
        ) {

            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Unable to calculate fare.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }
}