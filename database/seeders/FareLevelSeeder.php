<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FareLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [

            [
                'level_no' => 1,
                'level_name' => 'Level 1',
                'min_miles' => 0.10,
                'max_miles' => 5.00,
                'base_price' => 0.75,
                'price_per_mile' => 1.00,
            ],

            [
                'level_no' => 2,
                'level_name' => 'Level 2',
                'min_miles' => 5.01,
                'max_miles' => 10.00,
                'base_price' => 0.75,
                'price_per_mile' => 1.00,
            ],

            [
                'level_no' => 3,
                'level_name' => 'Level 3',
                'min_miles' => 10.01,
                'max_miles' => null,
                'base_price' => 0.75,
                'price_per_mile' => 1.00,
            ],

        ];


        foreach ($levels as $level) {

            DB::table('fare_levels')
                ->updateOrInsert(
                    [
                        'level_no' => $level['level_no'],
                    ],
                    [
                        'level_name' => $level['level_name'],
                        'min_miles' => $level['min_miles'],
                        'max_miles' => $level['max_miles'],
                        'base_price' => $level['base_price'],
                        'price_per_mile' => $level['price_per_mile'],
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
        }
    }
}