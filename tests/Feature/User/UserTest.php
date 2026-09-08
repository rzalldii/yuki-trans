<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    public function test_primary_admin_can_create_admin_and_user(): void
    {
        $primaryAdmin = User::factory()->admin()->create([
            'is_primary' => true,
        ]);
        $responseAdmin = $this->actingAs($primaryAdmin)->postJson(route('users.store'), [
            'username' => 'new.admin',
            'password' => 'Password123',
            'role' => 'admin',
        ]);
        $responseAdmin->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'username' => 'new.admin',
            'role' => 'admin',
        ]);
        $responseUser = $this->actingAs($primaryAdmin)->postJson(route('users.store'), [
            'username' => 'new.user',
            'password' => 'Password123',
            'role' => 'user',
        ]);
        $responseUser->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'username' => 'new.user',
            'role' => 'user',
        ]);
    }

    public function test_regular_admin_cannot_create_admin_but_can_create_user(): void
    {
        $admin = User::factory()->admin()->create([
            'is_primary' => false,
        ]);
        $responseAdmin = $this->actingAs($admin)->postJson(route('users.store'), [
            'username' => 'sub.admin',
            'password' => 'Password123',
            'role' => 'admin',
        ]);
        $responseAdmin->assertStatus(422);
        $responseAdmin->assertJsonValidationErrors(['role']);
        $responseUser = $this->actingAs($admin)->postJson(route('users.store'), [
            'username' => 'sub.user',
            'password' => 'Password123',
            'role' => 'user',
        ]);
        $responseUser->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'username' => 'sub.user',
            'role' => 'user',
        ]);
    }

    public function test_regular_admin_cannot_edit_or_delete_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create(['is_primary' => false]);
        $admin2 = User::factory()->admin()->create(['is_primary' => false]);
        $responseEdit = $this->actingAs($admin1)->getJson(route('users.edit', $admin2));
        $responseEdit->assertStatus(403);
        $responseUpdate = $this->actingAs($admin1)->putJson(route('users.update', $admin2), [
            'username' => 'admin2.mod',
            'role' => 'admin',
        ]);
        $responseUpdate->assertStatus(403);
        $responseDelete = $this->actingAs($admin1)->deleteJson(route('users.destroy', $admin2));
        $responseDelete->assertStatus(403);
    }

    public function test_primary_admin_can_update_and_delete_other_admins(): void
    {
        $primaryAdmin = User::factory()->admin()->create(['is_primary' => true]);
        $admin2 = User::factory()->admin()->create(['is_primary' => false]);
        $responseUpdate = $this->actingAs($primaryAdmin)->putJson(route('users.update', $admin2), [
            'username' => 'admin2.updated',
            'role' => 'admin',
        ]);
        $responseUpdate->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $admin2->id,
            'username' => 'admin2.updated',
        ]);
        $responseDelete = $this->actingAs($primaryAdmin)->deleteJson(route('users.destroy', $admin2));
        $responseDelete->assertStatus(200);
        $this->assertSoftDeleted('users', [
            'id' => $admin2->id,
        ]);
    }

    public function test_non_admin_cannot_access_user_routes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertStatus(403);
    }

    public function test_validation_rejects_invalid_username_regex_and_weak_password(): void
    {
        $admin = User::factory()->admin()->create(['is_primary' => true]);
        $response = $this->actingAs($admin)->postJson(route('users.store'), [
            'username' => 'bad user name!',
            'password' => '123',
            'role' => 'user',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'password']);
    }
}