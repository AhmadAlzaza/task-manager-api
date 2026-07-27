<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Seeder Credentials
    |--------------------------------------------------------------------------
    |
    | Used by AdminSeeder to create the initial admin account. Required in
    | production; falls back to defaults in local/testing environments.
    |
    */

    'name' => env('ADMIN_NAME', 'Admin'),

    'email' => env('ADMIN_EMAIL', 'admin@example.com'),

    'password' => env('ADMIN_PASSWORD'),

];
