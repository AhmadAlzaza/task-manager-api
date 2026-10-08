<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('tasks', TaskController::class);

        Route::apiResource('categories', CategoryController::class)
            ->only([
                'index',
                'show',
                'store',
                'update',
                'destroy',
            ]);
    });
});
