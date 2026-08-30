<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T-15: Token is valid within 24 hours.
     */
    public function test_token_is_valid_within_24_hours(): void
    {
        $user = User::factory()->create();

        // Explicitly set expiration to 24 hours from now
        $expiresAt = Carbon::now()->addMinutes(1440);
        $token = $user->createToken('test-token', ['*'], $expiresAt)->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/tasks')
            ->assertStatus(200);
    }

    /**
     * T-16: Token expires after 24 hours.
     */
    public function test_token_expires_after_24_hours(): void
    {
        $user = User::factory()->create();

        // Explicitly set expiration to 24 hours from now
        $expiresAt = Carbon::now()->addMinutes(1440);

        $token = $user->createToken(
            'test-token',
            ['*'],
            $expiresAt
        )->plainTextToken;

        // Travel 25 hours into the future
        $this->travel(25)->hours();

        // Re-request with the expired token
        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/tasks')
            ->assertStatus(401);

        $this->travelBack();
    }

    /**
     * T-17: Config file is correctly updated.
     */
    public function test_sanctum_config_expiration_is_1440(): void
    {
        $this->assertEquals(1440, config('sanctum.expiration'));
    }
}
