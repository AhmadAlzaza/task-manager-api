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

    // T-11: request within limit succeeds
    public function test_request_within_limit_succeeds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200);
    }

    // T-05: 6th login attempt returns 429
    public function test_login_rate_limit_returns_429(): void
    {
        $payload = ['email' => 'test@example.com', 'password' => 'wrongpassword'];

        // Hit the limit 5 times
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', $payload);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/login', $payload);

        $response->assertStatus(429);
    }

    // T-09: 61st API request returns 429
    public function test_api_rate_limit_returns_429(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Hit the limit 60 times
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }

        // 61st attempt should be rate limited
        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(429);
    }

    // T-10: 429 response matches error contract and doesn't leak info
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

    // T-12: User A exhausts limit, User B is not affected (even from same IP)
    public function test_user_limit_is_isolated_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Sanctum::actingAs($userA);
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }
        $this->getJson('/api/v1/tasks')->assertStatus(429);

        // User B should be unaffected even if coming from the same IP (127.0.0.1 in tests)
        Sanctum::actingAs($userB);
        $this->getJson('/api/v1/tasks')->assertStatus(200);
    }

    // T-13: Unauthenticated request to protected route returns 401, not 429
    public function test_unauthenticated_request_returns_401_not_429(): void
    {
        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(401);
    }

    // T-14: API and Auth limits are isolated (hitting API limit doesn't block login)
    public function test_api_and_auth_limits_are_isolated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Exhaust the API limit
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tasks');
        }
        $this->getJson('/api/v1/tasks')->assertStatus(429);

        // Hitting the register endpoint (different limiter) should be fine (422 due to validation, but not 429)
        $response = $this->postJson('/api/v1/register', []);
        $response->assertStatus(422);
    }
}
