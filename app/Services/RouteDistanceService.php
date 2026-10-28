<?php

namespace App\Services;

class RouteDistanceService
{
    /**
     * Calculate minimum distance between
     * rider pickup point and driver's route polyline.
     *
     * Returns miles.
     */
    public function distanceFromPolyline(
        float $pickupLat,
        float $pickupLng,
        string $encodedPolyline
    ): array {

        if (empty($encodedPolyline)) {

            return [
                'status' => false,
                'message' => 'Driver route polyline not found.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Decode Polyline
        |--------------------------------------------------------------------------
        */

        $points =
            $this->decodePolyline(
                $encodedPolyline
            );


        if (empty($points)) {

            return [
                'status' => false,
                'message' => 'Unable to decode driver route.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Find Nearest Distance
        |--------------------------------------------------------------------------
        */

        $minimumMeters =
            null;


        foreach ($points as $point) {

            $distance =
                $this->haversineDistance(
                    $pickupLat,
                    $pickupLng,
                    $point['lat'],
                    $point['lng']
                );


            if (
                $minimumMeters === null ||
                $distance < $minimumMeters
            ) {

                $minimumMeters =
                    $distance;
            }
        }


        if ($minimumMeters === null) {

            return [
                'status' => false,
                'message' => 'Unable to calculate pickup distance from route.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Convert meters to miles
        |--------------------------------------------------------------------------
        */

        $miles =
            $minimumMeters
            / 1609.344;


        return [
            'status' => true,

            'distance_meters' =>
                round(
                    $minimumMeters,
                    2
                ),

            'distance_miles' =>
                round(
                    $miles,
                    2
                ),
        ];
    }


    /**
     * Decode Google encoded polyline.
     */
    private function decodePolyline(
        string $encoded
    ): array {

        $points = [];

        $index = 0;

        $lat = 0;

        $lng = 0;

        $length =
            strlen($encoded);


        while ($index < $length) {

            $result = 0;

            $shift = 0;


            do {

                if ($index >= $length) {
                    break 2;
                }


                $byte =
                    ord(
                        $encoded[$index++]
                    ) - 63;


                $result |=
                    ($byte & 0x1f)
                    << $shift;


                $shift += 5;

            } while ($byte >= 0x20);


            $deltaLat =
                ($result & 1)
                    ? ~($result >> 1)
                    : ($result >> 1);


            $lat +=
                $deltaLat;


            /*
            |--------------------------------------------------------------------------
            | Longitude
            |--------------------------------------------------------------------------
            */

            $result = 0;

            $shift = 0;


            do {

                if ($index >= $length) {
                    break 2;
                }


                $byte =
                    ord(
                        $encoded[$index++]
                    ) - 63;


                $result |=
                    ($byte & 0x1f)
                    << $shift;


                $shift += 5;

            } while ($byte >= 0x20);


            $deltaLng =
                ($result & 1)
                    ? ~($result >> 1)
                    : ($result >> 1);


            $lng +=
                $deltaLng;


            $points[] = [

                'lat' =>
                    $lat / 1e5,

                'lng' =>
                    $lng / 1e5,

            ];
        }


        return $points;
    }


    /**
     * Haversine distance.
     *
     * Return meters.
     */
    private function haversineDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {

        $earthRadius =
            6371000;


        $latFrom =
            deg2rad($lat1);

        $lonFrom =
            deg2rad($lng1);

        $latTo =
            deg2rad($lat2);

        $lonTo =
            deg2rad($lng2);


        $latDelta =
            $latTo -
            $latFrom;

        $lonDelta =
            $lonTo -
            $lonFrom;


        $a =
            sin($latDelta / 2)
            *
            sin($latDelta / 2)
            +
            cos($latFrom)
            *
            cos($latTo)
            *
            sin($lonDelta / 2)
            *
            sin($lonDelta / 2);


        $c =
            2 *
            atan2(
                sqrt($a),
                sqrt(1 - $a)
            );


        return
            $earthRadius
            *
            $c;
    }
}