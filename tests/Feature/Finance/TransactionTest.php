<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_user_can_create_transaction_with_valid_tags_and_balance_is_adjusted(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'name' => 'Cash',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $category = Category::create([
            'name' => 'Income Cat',
            'type' => 'income',
        ]);
        $response = $this->actingAs($user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Test Income',
            'tags' => ['tag1', 'tag2', 'tag3'],
        ]);
        $response->assertStatus(201);
        $this->assertEquals(150000, $wallet->fresh()->current_balance);
        $this->assertDatabaseHas('finance_transactions', [
            'amount' => 50000,
            'description' => 'Test Income',
        ]);
    }

    public function test_transaction_fails_validation_when_tags_exceed_limit_of_10(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'name' => 'Cash',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $category = Category::create([
            'name' => 'Expense Cat',
            'type' => 'expense',
        ]);
        $tags = array_map(fn($i) => "tag_{$i}", range(1, 11)); // 11 tags
        $response = $this->actingAs($user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 10000,
            'transaction_date' => now()->toDateString(),
            'tags' => $tags,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_user_cannot_update_or_delete_another_users_transaction(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $wallet = Wallet::create([
            'name' => 'Wallet A',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
        ]);
        $tx = Transaction::create([
            'user_id' => $userA->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 20000,
            'transaction_date' => now()->toDateString(),
        ]);
        $wallet->adjustBalance('expense', 20000);
        $this->actingAs($userB)->getJson(route('finance-transactions.edit', $tx))
            ->assertStatus(403);
        $this->actingAs($userB)->putJson(route('finance-transactions.update', $tx), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
        ])->assertStatus(403);
        $this->actingAs($userB)->deleteJson(route('finance-transactions.destroy', $tx))
            ->assertStatus(403);
    }

    public function test_admin_can_update_and_delete_any_users_transaction(): void
    {
        $userA = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Wallet Admin',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
        ]);
        $tx = Transaction::create([
            'user_id' => $userA->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 20000,
            'transaction_date' => now()->toDateString(),
        ]);
        $wallet->adjustBalance('expense', 20000);
        $this->actingAs($admin)->putJson(route('finance-transactions.update', $tx), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
        ])->assertStatus(200);
        $this->assertEquals(470000, $wallet->fresh()->current_balance);
        $this->actingAs($admin)->deleteJson(route('finance-transactions.destroy', $tx))
            ->assertStatus(200);
        $this->assertEquals(500000, $wallet->fresh()->current_balance);
        $this->assertModelMissing($tx);
    }

    public function test_transfer_between_wallets_adjusts_balances_and_reverts_on_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $walletA = Wallet::create([
            'name' => 'Source',
            'initial_balance' => 200000,
            'current_balance' => 200000,
        ]);
        $walletB = Wallet::create([
            'name' => 'Destination',
            'initial_balance' => 50000,
            'current_balance' => 50000,
        ]);
        $response = $this->actingAs($admin)->postJson(route('finance-transactions.transfer.store'), [
            'from_wallet_id' => $walletA->id,
            'to_wallet_id' => $walletB->id,
            'amount' => 75000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Transfer Test',
        ]);
        $response->assertStatus(201);
        $this->assertEquals(125000, $walletA->fresh()->current_balance);
        $this->assertEquals(125000, $walletB->fresh()->current_balance);
        $outTx = Transaction::where('type', 'transfer_out')->first();
        $this->assertNotNull($outTx);
        $deleteResponse = $this->actingAs($admin)->deleteJson(route('finance-transactions.destroy', $outTx));
        $deleteResponse->assertStatus(200);
        $this->assertEquals(200000, $walletA->fresh()->current_balance);
        $this->assertEquals(50000, $walletB->fresh()->current_balance);
    }
}