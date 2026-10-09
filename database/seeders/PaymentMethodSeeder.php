<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Cash',
                'slug' => 'cash',
                'sort_order' => 1,
            ],
            [
                'name' => 'Venmo',
                'slug' => 'venmo',
                'sort_order' => 2,
            ],
            [
                'name' => 'Zelle Pay',
                'slug' => 'zelle',
                'sort_order' => 3,
            ],
            [
                'name' => 'Cash App',
                'slug' => 'cash_app',
                'sort_order' => 4,
            ],
            [
                'name' => 'PayPal',
                'slug' => 'paypal',
                'sort_order' => 5,
            ],
            [
                'name' => 'Apple Pay',
                'slug' => 'apple_pay',
                'sort_order' => 6,
            ],
            [
                'name' => 'Google Pay',
                'slug' => 'google_pay',
                'sort_order' => 7,
            ],
            [
                'name' => 'Credit / Debit Card',
                'slug' => 'card',
                'sort_order' => 8,
            ],
        ];

        foreach ($methods as $method) {
            DB::table('payment_methods')->insert([
                'name' => $method['name'],
                'slug' => $method['slug'],
                'is_active' => true,
                'sort_order' => $method['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}