<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Clear the rate limiter cache between tests.
     * We flush the cache store directly instead of calling artisan:cache:clear
     * because it's faster and targets exactly what the RateLimiter uses.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['cache']->store()->flush();
    }

    public function test_request_within_limit_succeeds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200);
    }

    public function test_login_rate_limit_returns_429(): void
    {
        $payload = ['email' => 'test@example.com', 'password' => 'wrongpassword'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', $payload);
        }

        $response = $this->postJson('/api/v1/login', $payload);

        $response->assertStatus(429);
    }

    public function test_api_rate_limit_returns_429(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(429);
    }

    public function test_login_email_limit_is_isolated_per_email(): void
    {
        $payload = [
            'email' => 'first@example.com',
            'password' => 'wrongpassword',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', $payload);
        }

        $this->postJson('/api/v1/login', $payload)
            ->assertStatus(429);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'second@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_429_response_matches_error_contract(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(429)
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('file')
            ->assertJsonMissingPath('line')
            ->assertJsonPath('message', 'Too many requests. Please try again later.');
    }

    public function test_user_limit_is_isolated_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Sanctum::actingAs($userA);
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }
        $this->getJson('/api/v1/tasks')->assertStatus(429);

        Sanctum::actingAs($userB);
        $this->getJson('/api/v1/tasks')->assertStatus(200);
    }

    public function test_unauthenticated_request_returns_401_not_429(): void
    {
        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(401);
    }

    public function test_api_and_auth_limits_are_isolated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }
        $this->getJson('/api/v1/tasks')->assertStatus(429);

        $response = $this->postJson('/api/v1/register', []);
        $response->assertStatus(422);
    }
}
