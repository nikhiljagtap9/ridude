<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DriverRouteService
{
    public function calculate(
        float $startLat,
        float $startLng,
        float $destinationLat,
        float $destinationLng
    ): array {

        try {

            $apiKey = config(
                'services.google_maps.key'
            );

            if (!$apiKey) {

                return [
                    'status' => false,
                    'message' =>
                        'Google Maps API key not configured.',
                ];
            }


            $response = Http::withHeaders([

                'Content-Type' =>
                    'application/json',

                'X-Goog-Api-Key' =>
                    $apiKey,

                'X-Goog-FieldMask' =>
                    'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline',

            ])
            ->post(
                'https://routes.googleapis.com/directions/v2:computeRoutes',
                [

                    'origin' => [
                        'location' => [
                            'latLng' => [
                                'latitude' =>
                                    $startLat,

                                'longitude' =>
                                    $startLng,
                            ],
                        ],
                    ],

                    'destination' => [
                        'location' => [
                            'latLng' => [
                                'latitude' =>
                                    $destinationLat,

                                'longitude' =>
                                    $destinationLng,
                            ],
                        ],
                    ],

                    'travelMode' =>
                        'DRIVE',

                    'computeAlternativeRoutes' =>
                        false,

                    'languageCode' =>
                        'en-US',
                ]
            );


            if (!$response->successful()) {

                Log::error(
                    'Google route API error',
                    [
                        'response' =>
                            $response->body(),
                    ]
                );

                return [
                    'status' => false,
                    'message' =>
                        'Unable to calculate route.',
                ];
            }


            $data = $response->json();

            if (
                empty(
                    $data['routes'][0]
                )
            ) {

                return [
                    'status' => false,
                    'message' =>
                        'Route not found.',
                ];
            }


            $route =
                $data['routes'][0];


            $distanceMeters =
                (float)
                ($route['distanceMeters'] ?? 0);


            if ($distanceMeters <= 0) {

                return [
                    'status' => false,
                    'message' =>
                        'Invalid route distance.',
                ];
            }


            $distanceMiles =
                $distanceMeters
                / 1609.344;


            $durationSeconds = 0;

            if (
                !empty(
                    $route['duration']
                )
            ) {

                $durationSeconds =
                    (int)
                    str_replace(
                        's',
                        '',
                        $route['duration']
                    );
            }


            return [

                'status' =>
                    true,

                'distance_miles' =>
                    round(
                        $distanceMiles,
                        2
                    ),

                'duration_minutes' =>
                    (int)
                    ceil(
                        $durationSeconds
                        / 60
                    ),

                'polyline' =>
                    $route['polyline']
                    ['encodedPolyline']
                    ?? null,
            ];


        } catch (\Throwable $e) {

            Log::error(
                'Route calculation failed',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return [
                'status' => false,
                'message' =>
                    'Route calculation failed.',
            ];
        }
    }
}