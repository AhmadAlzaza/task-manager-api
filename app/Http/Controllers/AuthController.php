<?php

namespace App\Http\Controllers;

use App\Events\UserRegistered;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @group Authentication */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * @unauthenticated
     */
    public function register(RegisterRequest $request)
    {
        [$user, $token] = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [$user, $token];
        });

        event(new UserRegistered($user));

        return $this->successResponse([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'User registered successfully', 201);
    }

    /**
     * @unauthenticated
     *
     * @bodyParam email string required User email. Example: admin@test.com
     * @bodyParam password string required User password. Example: password
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Logged in successfully');
    }

    /**
     * Revoke the current authentication token.
     *
     * Other tokens owned by the user remain active.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}
