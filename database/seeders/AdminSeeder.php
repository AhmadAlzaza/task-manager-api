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
        if (app()->environment('production') && ! config('admin.password')) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD must be set in .env to run AdminSeeder in production.'
            );
        }

        $admin = User::firstOrNew(['email' => config('admin.email')]);

        $admin->forceFill([
            'name' => config('admin.name'),
            'password' => Hash::make(config('admin.password') ?? 'password'),
            'role' => 'admin',
        ])->save();
    }
}
