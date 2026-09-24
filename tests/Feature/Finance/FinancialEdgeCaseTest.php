<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Enums\Finance\CategoryType;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use App\Services\Finance\TransactionService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    }

    public function test_concurrent_expenses_cannot_overdraw_wallet(): void
    {
        $wallet = Wallet::create(['name' => 'Edge Wallet', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $category = Category::create(['name' => 'Shopping', 'type' => CategoryType::Expense]);
        $res1 = $this->actingAs($this->user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 40000,
            'transaction_date' => now()->toDateString(),
        ]);
        $res1->assertStatus(201);
        $this->assertEquals(10000.0, (float) $wallet->fresh()->current_balance);
        $res2 = $this->actingAs($this->user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
        ]);
        $res2->assertStatus(422);
        $this->assertEquals(10000.0, (float) $wallet->fresh()->current_balance);
    }

    public function test_transfer_to_same_wallet_rejected(): void
    {
        $wallet = Wallet::create(['name' => 'Only Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $res = $this->actingAs($this->admin)->postJson(route('finance-transactions.transfer.store'), [
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet->id,
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['to_wallet_id']);
    }

    public function test_zero_amount_transaction_rejected(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Misc', 'type' => CategoryType::Expense]);
        $res = $this->actingAs($this->user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 0,
            'transaction_date' => now()->toDateString(),
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['amount']);
    }

    public function test_negative_amount_transaction_rejected(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Misc', 'type' => CategoryType::Expense]);
        $res = $this->actingAs($this->user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => -500,
            'transaction_date' => now()->toDateString(),
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['amount']);
    }

    public function test_update_expense_with_wallet_change_checks_new_wallet_balance(): void
    {
        $richWallet = Wallet::create(['name' => 'Rich', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $poorWallet = Wallet::create(['name' => 'Poor', 'initial_balance' => 10000, 'current_balance' => 10000]);
        $category = Category::create(['name' => 'Travel', 'type' => CategoryType::Expense]);
        $service = app(TransactionService::class);
        $tx = $service->createTransaction([
            'wallet_id' => $richWallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $res = $this->actingAs($this->user)->putJson(route('finance-transactions.update', $tx), [
            'wallet_id' => $poorWallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ]);
        $res->assertStatus(422);
        $richWallet->refresh();
        $poorWallet->refresh();
        $this->assertEquals(50000.0, (float) $richWallet->current_balance);
        $this->assertEquals(10000.0, (float) $poorWallet->current_balance);
    }

    public function test_delete_transfer_restores_both_wallet_balances(): void
    {
        $w1 = Wallet::create(['name' => 'W1', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $w2 = Wallet::create(['name' => 'W2', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $createRes = $this->actingAs($this->admin)->postJson(route('finance-transactions.transfer.store'), [
            'from_wallet_id' => $w1->id,
            'to_wallet_id' => $w2->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ]);
        $createRes->assertStatus(201);
        $w1->refresh();
        $w2->refresh();
        $this->assertEquals(150000.0, (float) $w1->current_balance);
        $this->assertEquals(150000.0, (float) $w2->current_balance);
        $txId = $createRes->json('data.id');
        $tx = Transaction::findOrFail($txId);
        $delRes = $this->actingAs($this->admin)->deleteJson(route('finance-transactions.destroy', $tx));
        $delRes->assertStatus(200);
        $w1->refresh();
        $w2->refresh();
        $this->assertEquals(200000.0, (float) $w1->current_balance);
        $this->assertEquals(100000.0, (float) $w2->current_balance);
    }

    public function test_create_expense_exactly_equal_to_balance_succeeds(): void
    {
        $wallet = Wallet::create(['name' => 'Exact', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $category = Category::create(['name' => 'Exact', 'type' => CategoryType::Expense]);
        $res = $this->actingAs($this->user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ]);
        $res->assertStatus(201);
        $wallet->refresh();
        $this->assertEquals(0.0, (float) $wallet->current_balance);
    }

    public function test_create_expense_one_cent_over_balance_fails(): void
    {
        $wallet = Wallet::create(['name' => 'Penny Over', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $category = Category::create(['name' => 'Penny Over', 'type' => CategoryType::Expense]);
        $res = $this->actingAs($this->user)->postJson(route('finance-transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000.01,
            'transaction_date' => now()->toDateString(),
        ]);
        $res->assertStatus(422);
        $wallet->refresh();
        $this->assertEquals(50000.0, (float) $wallet->current_balance);
    }

    public function test_decimal_precision_maintained_after_multiple_operations(): void
    {
        $wallet = Wallet::create(['name' => 'Decimal Test', 'initial_balance' => 100.00, 'current_balance' => 100.00]);
        $categoryExpense = Category::create(['name' => 'Cents Out', 'type' => CategoryType::Expense]);
        $categoryIncome = Category::create(['name' => 'Cents In', 'type' => CategoryType::Income]);
        $service = app(TransactionService::class);
        $service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $categoryExpense->id,
            'amount' => 33.33,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(66.67, (float) $wallet->current_balance);
        $service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $categoryExpense->id,
            'amount' => 33.33,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(33.34, (float) $wallet->current_balance);
        $service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $categoryIncome->id,
            'amount' => 66.66,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(100.00, (float) $wallet->current_balance);
    }

    public function test_wallet_recalculate_balance_matches_manual_sum(): void
    {
        $wallet = Wallet::create(['name' => 'Audit Recalc', 'initial_balance' => 500000, 'current_balance' => 500000]);
        $incomeCat = Category::create(['name' => 'Bonus', 'type' => CategoryType::Income]);
        $expenseCat = Category::create(['name' => 'Supplies', 'type' => CategoryType::Expense]);
        $service = app(TransactionService::class);
        $service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $incomeCat->id,
            'amount' => 150000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $expenseCat->id,
            'amount' => 75000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $expenseCat->id,
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $wallet->updateQuietly(['current_balance' => 999999]);
        $this->assertEquals(999999.0, (float) $wallet->fresh()->current_balance);
        $wallet->recalculateBalance();
        $this->assertEquals(550000.0, (float) $wallet->fresh()->current_balance);
    }
}