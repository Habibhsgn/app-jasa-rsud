<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'admin_Andika',
                'email' => 'biotikhumaniora@gmail.com',
                'role' => 'admin', // default bisa kamu ubah
                'is_active' => 1,
                'password' => Hash::make('dika1989'),
            ],
        ]);
    }
}