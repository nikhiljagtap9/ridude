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
    /*
    |--------------------------------------------------------------------------
    | Calculate Fare
    |--------------------------------------------------------------------------
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
                     * Address only for display/reference.
                     * Not used for Google route calculation.
                     */
                    'pickup_address' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'dropoff_address' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],


                    /*
                     * Pickup Coordinates
                     */
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
                     * Dropoff Coordinates
                     */
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
            | Google API Key
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
            |
            | IMPORTANT:
            |
            | Address is NOT used.
            |
            | Google route is calculated using:
            |
            | pickup_latitude
            | pickup_longitude
            | dropoff_latitude
            | dropoff_longitude
            |
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

                        /*
                        |--------------------------------------------------------------------------
                        | Origin - Pickup Coordinates
                        |--------------------------------------------------------------------------
                        */

                        'origin' => [

                            'location' => [

                                'latLng' => [

                                    'latitude' =>
                                        (float)
                                        $validated[
                                            'pickup_latitude'
                                        ],

                                    'longitude' =>
                                        (float)
                                        $validated[
                                            'pickup_longitude'
                                        ],
                                ],
                            ],
                        ],


                        /*
                        |--------------------------------------------------------------------------
                        | Destination - Dropoff Coordinates
                        |--------------------------------------------------------------------------
                        */

                        'destination' => [

                            'location' => [

                                'latLng' => [

                                    'latitude' =>
                                        (float)
                                        $validated[
                                            'dropoff_latitude'
                                        ],

                                    'longitude' =>
                                        (float)
                                        $validated[
                                            'dropoff_longitude'
                                        ],
                                ],
                            ],
                        ],


                        /*
                        |--------------------------------------------------------------------------
                        | Route Settings
                        |--------------------------------------------------------------------------
                        */

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
            | Google API Error
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

                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | Google Response
            |--------------------------------------------------------------------------
            */

            $googleData =
                $response->json();


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
            | Distance In Meters
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
            |--------------------------------------------------------------------------
            | Convert Meters -> Miles
            |--------------------------------------------------------------------------
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
            | Google example:
            |
            | "duration": "1250s"
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

                    'data' => [

                        'distance_miles' =>
                            $distanceMiles,

                        'duration_minutes' =>
                            $durationMinutes,
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
                     * Address returned only for display.
                     * Not used in calculation.
                     */

                    'pickup_address' =>
                        $validated[
                            'pickup_address'
                        ]
                        ?? null,

                    'dropoff_address' =>
                        $validated[
                            'dropoff_address'
                        ]
                        ?? null,


                    /*
                     * Coordinates
                     */

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
                            'distance'
                        ],

                    'duration_minutes' =>
                        $durationMinutes,


                    /*
                     * Fare Level
                     */

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