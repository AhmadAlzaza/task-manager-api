<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::create([
            'name' => 'Admin',
            'email' => env('ADMIN_EMAIL', 'admin@admin.com'),
            'password' => env('ADMIN_PASSWORD', 'password'),
            'role' => 'admin',
        ]);
    }
}
