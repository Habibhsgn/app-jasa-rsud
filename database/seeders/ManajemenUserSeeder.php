<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ManajemenUserSeeder extends Seeder
{
    /**
     * Buat 1 akun dengan role manajemen.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'hsgn@rsud.go.id'],
            [
                'name' => 'Manajemen RSUD',
                'password' => Hash::make('12345678'),
                'role' => 'manajemen',
                'ruangan_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}