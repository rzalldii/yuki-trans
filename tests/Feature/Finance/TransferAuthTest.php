<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_admin_can_store_transfer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $w1 = Wallet::create(['name' => 'W1', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $w2 = Wallet::create(['name' => 'W2', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $response = $this->actingAs($admin)->postJson(route('finance-transactions.transfer.store'), [
            'from_wallet_id' => $w1->id,
            'to_wallet_id' => $w2->id,
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
        ]);
        $response->assertStatus(201);
        $this->assertEquals(75000, $w1->fresh()->current_balance);
        $this->assertEquals(75000, $w2->fresh()->current_balance);
    }

    public function test_non_admin_cannot_store_transfer(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $w1 = Wallet::create(['name' => 'W1', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $w2 = Wallet::create(['name' => 'W2', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $response = $this->actingAs($user)->postJson(route('finance-transactions.transfer.store'), [
            'from_wallet_id' => $w1->id,
            'to_wallet_id' => $w2->id,
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_generate_recurring(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->postJson(route('finance-recurring.generate'));
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_non_admin_cannot_generate_recurring(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->postJson(route('finance-recurring.generate'));
        $response->assertStatus(403);
    }
}