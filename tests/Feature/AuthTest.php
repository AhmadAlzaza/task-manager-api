<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

        $response = $this->postJson('/api/v1/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => Str::random(12),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['token', 'user'],
                'success',
                'message',
            ]);
    }

    public function test_user_can_login()
    {
        $password = Str::random(12);
        $user = User::factory()->create([
            'password' => $password,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user'],
                'success',
                'message',
            ]);
    }

    public function test_user_can_login_without_session_authentication()
    {
        Event::fake([Login::class]);

        $password = Str::random(12);

        $user = User::factory()->create([
            'password' => $password,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user'],
                'success',
                'message',
            ]);

        Event::assertNotDispatched(Login::class);
    }

    public function test_registration_normalizes_email(): void
    {
        $password = Str::random(12);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Ahmad',
            'email' => '  AHMAD@Example.COM  ',
            'password' => $password,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmad@example.com',
        ]);
    }

    public function test_user_can_login_with_normalized_email_input(): void
    {
        $password = Str::random(12);

        User::factory()->create([
            'email' => 'ahmad@example.com',
            'password' => $password,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => '  AHMAD@EXAMPLE.COM  ',
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user'],
                'success',
                'message',
            ]);
    }

    public function test_user_cannot_register_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Weak Password User',
            'email' => 'weak@example.com',
            'password' => 'Password12',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_user_can_logout_only_current_token()
    {
        $user = User::factory()->create();

        $currentToken = $user->createToken('current-token')->plainTextToken;
        $otherToken = $user->createToken('other-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$currentToken,
        ])->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
                'data' => null,
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        Auth::forgetGuards();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$currentToken,
        ])->getJson('/api/v1/tasks')
            ->assertUnauthorized();

        Auth::forgetGuards();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$otherToken,
        ])->getJson('/api/v1/tasks')
            ->assertOk();
    }

    public function test_user_cannot_register_with_duplicate_email()
    {
        $existingUser = User::factory()->create();

        $response = $this->postJson('/api/v1/register', [
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

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_welcome_email_job_is_dispatched_on_register()
    {
        Bus::fake();

        $user = User::factory()->make();

        $this->postJson('/api/v1/register', [
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
        Event::fake([UserRegistered::class]);

        $user = User::factory()->make();

        $this->postJson('/api/v1/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => Str::random(12),
        ])->assertStatus(201);

        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
            return $event->user->email === $user->email;
        });
    }

    public function test_user_tokens_are_deleted_when_user_is_deleted()
    {
        $user = User::factory()->create();
        $user->createToken('test-token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $user->delete();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_other_users_tokens_not_affected_when_user_is_deleted()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $userA->createToken('token-a');
        $userB->createToken('token-b');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $userA->delete();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $userB->id,
        ]);
    }

    public function test_user_cannot_register_as_admin_via_mass_assignment()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Hacker User',
            'email' => 'hacker@example.com',
            'password' => 'password1234',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertNotNull($user);

        $this->assertEquals(UserRole::USER, $user->role);
    }

    public function test_login_ip_rate_limit_returns_429(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => "user{$i}@example.com",
                'password' => 'wrongpassword',
            ]);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'user10@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(429);
    }
}
