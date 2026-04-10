<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
    }

    public function test_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('secret123')]);

        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'secret123'])
            ->assertStatus(200)
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('secret123')]);

        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'wrong'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        [$user, , $headers] = $this->createAuthenticatedUser();

        $this->getJson('/me', $headers)
            ->assertStatus(200)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/me')->assertStatus(401);
    }

    public function test_logout_revokes_token(): void
    {
        [, , $headers] = $this->createAuthenticatedUser();

        $this->postJson('/logout', [], $headers)->assertStatus(200);
        $this->app['auth']->forgetGuards();
        $this->getJson('/me', $headers)->assertStatus(401);
    }
}
