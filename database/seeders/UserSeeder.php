<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        DB::table('users')->insert([
            'first_name'       => 'Super',
            'last_name'        => 'Admin',
            'email'            => 'admin@gmail.com',
            'mobile'           => '9827508795',
            'designation'      => 'Global Administrator',
            'role_id'          => 1,
            'department_id'    => null,
            'location_id'      => null,
            'password'         => Hash::make('12345678'),
            'activation_token' => null,
            'two_factor_auth'  => false,
            'remember_token'   => null,
            'email_verified_at'=> now(),
            'status'           => 1,
            'notice'           => null,
            'profile_picture'  => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}