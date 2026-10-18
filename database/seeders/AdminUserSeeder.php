<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'admin@ridude.in'
            ],
            [
                'role' => 'admin',

                'full_name' => 'Admin',

                'country_code' => '+91',

                'phone_number' => '9999999999',

                /*
                 * User model has:
                 * 'password' => 'hashed'
                 */
                'password' => '123456',

                'terms_accepted' => true,

                'status' => 'approved',

                'submitted_at' => now(),

                'approved_at' => now(),
            ]
        );
    }
}