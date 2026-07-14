<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production') && ! env('ADMIN_PASSWORD')) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD must be set in .env to run AdminSeeder in production.'
            );
        }

        User::forceCreate([
            'name' => env('ADMIN_NAME', 'Admin'),
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'role' => 'admin',
        ]);
    }
}
