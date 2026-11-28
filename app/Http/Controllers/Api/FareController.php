<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\FareCalculationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class FareController extends Controller
{
    public function calculate(Request $request)
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated =
                $request->validate([

                    'pickup_address' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'dropoff_address' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ]);


            /*
            |--------------------------------------------------------------------------
            | API Key
            |--------------------------------------------------------------------------
            */

            $apiKey =
                config(
                    'services.google_maps.key'
                );


            if (!$apiKey) {

                return response()->json([
                    'status' => false,
                    'message' =>
                        'Google Maps API key is not configured.',
                ], 500);
            }


            /*
            |--------------------------------------------------------------------------
            | Routes API
            |--------------------------------------------------------------------------
            */

            $response =
                Http::timeout(20)

                ->withHeaders([

                    'X-Goog-Api-Key' =>
                        $apiKey,

                    'X-Goog-FieldMask' =>
                        'routes.distanceMeters,routes.duration',

                    'Content-Type' =>
                        'application/json',
                ])

                ->post(
                    'https://routes.googleapis.com/directions/v2:computeRoutes',
                    [

                        'origin' => [

                            'address' =>
                                $validated[
                                    'pickup_address'
                                ],
                        ],

                        'destination' => [

                            'address' =>
                                $validated[
                                    'dropoff_address'
                                ],
                        ],

                        'travelMode' =>
                            'DRIVE',

                        'routingPreference' =>
                            'TRAFFIC_UNAWARE',

                        'computeAlternativeRoutes' =>
                            false,

                        'languageCode' =>
                            'en-US',

                        'units' =>
                            'IMPERIAL',
                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | Google API Failed
            |--------------------------------------------------------------------------
            */

            if (
                !$response->successful()
            ) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        'Unable to calculate route.',

                    'google_error' =>
                        $response->json(),

                ], $response->status());
            }


            $googleData =
                $response->json();


            /*
            |--------------------------------------------------------------------------
            | Route
            |--------------------------------------------------------------------------
            */

            $route =
                $googleData[
                    'routes'
                ][0]
                ?? null;


            if (!$route) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        'No route found between pickup and dropoff.',

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Distance
            |--------------------------------------------------------------------------
            */

            $distanceMeters =
                (float)
                (
                    $route[
                        'distanceMeters'
                    ]
                    ?? 0
                );


            if (
                $distanceMeters <= 0
            ) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        'Invalid route distance.',

                ], 422);
            }


            /*
             * Meter -> Miles
             */

            $distanceMiles =
                $distanceMeters
                / 1609.344;


            $distanceMiles =
                round(
                    $distanceMiles,
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | Duration
            |--------------------------------------------------------------------------
            |
            | Routes API returns:
            |
            | "duration": "1234s"
            |
            */

            $duration =
                $route[
                    'duration'
                ]
                ?? '0s';


            $durationSeconds =
                (int)
                str_replace(
                    's',
                    '',
                    $duration
                );


            $durationMinutes =
                (int)
                ceil(
                    $durationSeconds
                    / 60
                );


            /*
            |--------------------------------------------------------------------------
            | Fare Calculation
            |--------------------------------------------------------------------------
            */

            $fare =
                FareCalculationController::calculateFare(
                    $distanceMiles
                );


            if (
                !$fare[
                    'status'
                ]
            ) {

                return response()->json([

                    'status' =>
                        false,

                    'message' =>
                        $fare[
                            'message'
                        ],

                    'distance_miles' =>
                        $distanceMiles,

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' =>
                    true,

                'message' =>
                    'Fare calculated successfully.',

                'data' => [

                    'pickup_address' =>
                        $validated[
                            'pickup_address'
                        ],

                    'dropoff_address' =>
                        $validated[
                            'dropoff_address'
                        ],

                    'distance_miles' =>
                        $fare[
                            'distance'
                        ],

                    'duration_minutes' =>
                        $durationMinutes,

                    'fare_level' => [

                        'id' =>
                            $fare[
                                'level_id'
                            ],

                        'level_no' =>
                            $fare[
                                'level_no'
                            ],

                        'level_name' =>
                            $fare[
                                'level_name'
                            ],
                    ],

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
                            'fare'
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