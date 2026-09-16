<?php

namespace Tests\Feature;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ErrorContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // إعداد مسارات وهمية داخل مساحة /api/ لاختبار الاستثناءات
        Route::get('/api/test-validation', function () {
            throw ValidationException::withMessages(['email' => ['The email field is invalid.']]);
        });

        Route::get('/api/test-unauthenticated', function () {
            throw new AuthenticationException;
        });

        Route::get('/api/test-forbidden', function () {
            throw new AuthorizationException;
        });

        Route::get('/api/test-throttle', function () {
            throw new ThrottleRequestsException;
        });

        Route::get('/api/test-server-error', function () {
            throw new \Exception('Something went completely wrong!');
        });
    }

    public function test_404_not_found_follows_contract(): void
    {
        // طلب مسار غير موجود إطلاقاً
        $response = $this->getJson('/api/this-route-does-not-exist-12345');

        $response->assertStatus(404)
            ->assertExactJson([
                'success' => false,
                'message' => 'Resource not found',
                'errors' => null,
            ]);
    }

    public function test_422_validation_error_follows_contract(): void
    {
        $response = $this->getJson('/api/test-validation');

        $response->assertStatus(422)
            ->assertExactJson([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => [
                    'email' => ['The email field is invalid.'],
                ],
            ]);
    }

    public function test_401_unauthenticated_follows_contract(): void
    {
        $response = $this->getJson('/api/test-unauthenticated');

        $response->assertStatus(401)
            ->assertExactJson([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => null,
            ]);
    }

    public function test_403_forbidden_follows_contract(): void
    {
        $response = $this->getJson('/api/test-forbidden');

        $response->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'errors' => null,
            ]);
    }

    public function test_429_too_many_requests_follows_contract(): void
    {
        $response = $this->getJson('/api/test-throttle');

        $response->assertStatus(429)
            ->assertExactJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'errors' => null,
            ]);
    }

    public function test_500_internal_server_error_follows_contract(): void
    {
        $response = $this->getJson('/api/test-server-error');

        $response->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJsonPath('success', false);
    }
}
