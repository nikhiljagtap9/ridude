<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FareCalculationService
{
    /**
     * Calculate rider fare based on distance.
     *
     * Level 1: 0.10 - 5.00 miles
     * Level 2: 5.01 - 10.00 miles
     * Level 3: > 10.00 miles
     */
    public function calculate(float $distance): array
    {
        if ($distance < 0.10) {

            return [
                'status' => false,
                'message' => 'Distance is below minimum allowed distance.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Find Fare Level
        |--------------------------------------------------------------------------
        */

        if (
            $distance >= 0.10 &&
            $distance <= 5.00
        ) {

            $levelNo = 1;

        } elseif (
            $distance >= 5.01 &&
            $distance <= 10.00
        ) {

            $levelNo = 2;

        } else {

            $levelNo = 3;
        }


        /*
        |--------------------------------------------------------------------------
        | Get Fare Configuration
        |--------------------------------------------------------------------------
        */

        $fareLevel = DB::table('fare_levels')
            ->where('level_no', $levelNo)
            ->where('is_active', 1)
            ->first();


        if (!$fareLevel) {

            return [
                'status' => false,
                'message' => 'Fare level configuration not found.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate Fare
        |--------------------------------------------------------------------------
        |
        | Fare =
        | Fixed Pickup Amount +
        | (Distance × Price Per Mile)
        |
        */

        $basePrice =
            (float) $fareLevel->base_price;

        $pricePerMile =
            (float) $fareLevel->price_per_mile;


        $fare =
            $basePrice +
            (
                $distance *
                $pricePerMile
            );


        $fare =
            round(
                $fare,
                2
            );


        return [
            'status' => true,

            'level_no' =>
                (int) $fareLevel->level_no,

            'level_name' =>
                $fareLevel->level_name,

            'distance_miles' =>
                round($distance, 2),

            'base_price' =>
                $basePrice,

            'price_per_mile' =>
                $pricePerMile,

            'fare_amount' =>
                $fare,
        ];
    }
}