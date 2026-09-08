<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('login-attempt:success_user|127.0.0.1');
        RateLimiter::clear('login-lock:success_user|127.0.0.1');
        RateLimiter::clear('login-attempt:wrongpass_user|127.0.0.1');
        RateLimiter::clear('login-lock:wrongpass_user|127.0.0.1');
        RateLimiter::clear('login-attempt:lockout_user|127.0.0.1');
        RateLimiter::clear('login-lock:lockout_user|127.0.0.1');
        parent::tearDown();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'success_user',
            'password' => 'Secret123',
        ]);
        $response = $this->post(route('login.post'), [
            'username' => 'Success_User',
            'password' => 'Secret123',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'wrongpass_user',
            'password' => 'Secret123',
        ]);
        $response = $this->post(route('login.post'), [
            'username' => 'wrongpass_user',
            'password' => 'WrongPassword',
        ]);
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_locks_out_after_multiple_failed_attempts(): void
    {
        User::factory()->create([
            'username' => 'lockout_user',
            'password' => 'Secret123',
        ]);
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.post'), [
                'username' => 'lockout_user',
                'password' => 'WrongPassword',
            ]);
        }
        $response = $this->post(route('login.post'), [
            'username' => 'lockout_user',
            'password' => 'Secret123',
        ]);
        $this->assertGuest();
    }
}