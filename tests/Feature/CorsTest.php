<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CorsTest extends TestCase
{
    use RefreshDatabase;

    private const ALLOWED_ORIGIN = 'http://localhost:3000';
    private const UNTRUSTED_ORIGIN = 'https://evil.com';

    /**
     * T-18 & T-23: Allowed Origin can access API (Unauthenticated & Authenticated scenarios)
     */
    public function test_cors_allows_requests_from_allowed_origin(): void
    {
        // 1. Unauthenticated request (Expect 401, but CORS headers should be present)
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
        ])->getJson('/api/v1/tasks');

        $response->assertStatus(401)
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);

        // 2. Authenticated request (Expect 200, and CORS headers should be present)
        Sanctum::actingAs(User::factory()->create());

        $authResponse = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
        ])->getJson('/api/v1/tasks');

        $authResponse->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    /**
     * T-19 & T-22: Untrusted Origin is blocked from reading responses
     */
    public function test_cors_blocks_requests_from_untrusted_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::UNTRUSTED_ORIGIN,
        ])->getJson('/api/v1/tasks');

        // The request might hit the server (401), but the browser won't read it due to missing header
        $response->assertStatus(401)
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /**
     * T-20: OPTIONS preflight request is successful and returns CORS headers
     */
    public function test_cors_preflight_request_is_successful(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/tasks', [], [], [], [
            'HTTP_ORIGIN' => self::ALLOWED_ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        // Assert the request is generally successful (usually 200 or 204 in Laravel)
        $response->assertSuccessful();

        // Assert the core CORS headers are present for the preflight
        $response->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
        $response->assertHeader('Access-Control-Allow-Methods');
    }

    /**
     * T-21: Preflight includes Authorization header in allowed headers
     */
    public function test_cors_preflight_includes_authorization_header(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/tasks', [], [], [], [
            'HTTP_ORIGIN' => self::ALLOWED_ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type',
        ]);

        $response->assertSuccessful();

        $allowedHeaders = $response->headers->get('Access-Control-Allow-Headers');
        $this->assertNotNull($allowedHeaders, 'Access-Control-Allow-Headers header is missing');

        // Use strtolower to make the check case-insensitive (HTTP headers are case-insensitive)
        $this->assertStringContainsString('authorization', strtolower($allowedHeaders));
        $this->assertStringContainsString('content-type', strtolower($allowedHeaders));
    }
}
