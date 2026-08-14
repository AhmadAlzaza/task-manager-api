<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_auth_routes_are_available(): void
    {
        $this->postJson('/api/v1/register', [])
            ->assertStatus(422);

        $this->postJson('/api/v1/login', [])
            ->assertStatus(422);
    }

    public function test_v1_protected_routes_are_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks')
            ->assertStatus(200);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories')
            ->assertStatus(200);
    }

    public function test_unversioned_api_routes_are_not_available(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/register', [])
            ->assertStatus(404);

        $this->postJson('/api/login', [])
            ->assertStatus(404);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tasks')
            ->assertStatus(404);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/categories')
            ->assertStatus(404);
    }
}
