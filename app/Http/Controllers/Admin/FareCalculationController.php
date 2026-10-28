<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FareCalculationController extends Controller
{
    /**
     * Fare calculation settings page.
     */
    public function index()
    {
        $fareLevels = DB::table('fare_levels')
            ->whereIn('level_no', [1, 2, 3])
            ->where('is_active', 1)
            ->orderBy('level_no')
            ->get();


        return view(
            'admin.fare-calculations.index',
            compact('fareLevels')
        );
    }


    /**
     * Update pricing only.
     *
     * Distance intervals are fixed and cannot
     * be changed from admin.
     */
    public function update(
        Request $request,
        int $id
    ) {
        $fareLevel = DB::table('fare_levels')
            ->where('id', $id)
            ->whereIn('level_no', [1, 2, 3])
            ->first();


        if (!$fareLevel) {

            return response()->json([
                'status' => false,
                'message' => 'Fare level not found.',
            ], 404);
        }


        $validated = $request->validate([
            'base_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'price_per_mile' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);


        DB::table('fare_levels')
            ->where('id', $id)
            ->update([
                'base_price' =>
                    round(
                        (float) $validated['base_price'],
                        2
                    ),

                'price_per_mile' =>
                    round(
                        (float) $validated['price_per_mile'],
                        2
                    ),

                'updated_at' =>
                    now(),
            ]);


        return response()->json([
            'status' => true,
            'message' =>
                $fareLevel->level_name .
                ' updated successfully.',
        ]);
    }


    /**
     * Reusable fare calculation.
     *
     * Fare =
     * fixed pickup amount
     * +
     * distance × mileage rate
     */
    public static function calculateFare(
        float $distance
    ): array {

        if ($distance <= 0) {

            return [
                'status' => false,
                'message' => 'Invalid trip distance.',
                'fare' => 0,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Determine Level
        |--------------------------------------------------------------------------
        |
        | Level 1 = 0.10 - 5.00
        | Level 2 = 5.01 - 10.00
        | Level 3 = > 10.00
        |
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

        } elseif (
            $distance > 10.00
        ) {

            $levelNo = 3;

        } else {

            /*
             * Example:
             * distance between 0 and 0.10
             */
            return [
                'status' => false,
                'message' =>
                    'Distance is below the minimum fare distance.',
                'fare' => 0,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Get Level
        |--------------------------------------------------------------------------
        */

        $level = DB::table('fare_levels')
            ->where('level_no', $levelNo)
            ->where('is_active', 1)
            ->first();


        if (!$level) {

            return [
                'status' => false,
                'message' =>
                    'Fare configuration not found.',
                'fare' => 0,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Fare calculation
        |--------------------------------------------------------------------------
        */

        $base =
            (float) $level->base_price;

        $rate =
            (float) $level->price_per_mile;


        $fare =
            $base +
            (
                $distance *
                $rate
            );


        $fare =
            round(
                $fare,
                2
            );


        return [
            'status' => true,

            'level_id' =>
                $level->id,

            'level_no' =>
                $level->level_no,

            'level_name' =>
                $level->level_name,

            'distance' =>
                round($distance, 2),

            'base_price' =>
                $base,

            'price_per_mile' =>
                $rate,

            'fare' =>
                $fare,
        ];
    }
}