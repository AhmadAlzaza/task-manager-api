<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $user = User::factory()->make();

        $response = $this->postJson('/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => Str::random(12),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_user_can_login()
    {
        $password = Str::random(12);
        $user = User::factory()->create([
            'password' => $password,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_user_cannot_register_with_duplicate_email()
    {
        $existingUser = User::factory()->create();

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => $existingUser->email,
            'password' => Str::random(12),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_user_cannot_login_with_wrong_password()
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_welcome_email_job_is_dispatched_on_register()
    {
        Bus::fake();  // @phpstan-ignore staticMethod.notFound

        $user = User::factory()->make();

        $this->postJson('/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => Str::random(12),
        ])->assertStatus(201);

        Bus::assertDispatched(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->email === $user->email;
        });
    }

    public function test_user_registered_event_is_dispatched_on_register()
    {
        Event::fake([UserRegistered::class]); // @phpstan-ignore staticMethod.notFound

        $user = User::factory()->make();

        $this->postJson('/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => Str::random(12),
        ])->assertStatus(201);

        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
            return $event->user->email === $user->email;
        });
    }
}
