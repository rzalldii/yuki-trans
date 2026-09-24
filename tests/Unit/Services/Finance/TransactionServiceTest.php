<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Enums\Finance\CategoryType;
use App\Enums\Finance\TransactionType;
use App\Exceptions\Finance\InsufficientBalanceException;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use App\Services\Finance\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);
        $this->user = User::factory()->create();
    }

    public function test_create_transaction_adjusts_wallet_balance(): void
    {
        $wallet = Wallet::create(['name' => 'Main Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $categoryIncome = Category::create(['name' => 'Salary', 'type' => CategoryType::Income]);
        $categoryExpense = Category::create(['name' => 'Food', 'type' => CategoryType::Expense]);
        $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $categoryIncome->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Bonus',
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(150000.0, (float) $wallet->current_balance);
        $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $categoryExpense->id,
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Lunch',
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(120000.0, (float) $wallet->current_balance);
    }

    public function test_create_transaction_with_tags(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Dining', 'type' => CategoryType::Expense]);
        $tx = $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
        ], ['Food', 'Dinner'], $this->user->id);
        $this->assertCount(2, $tx->tags);
        $this->assertTrue($tx->tags->pluck('name')->contains('Food'));
        $this->assertTrue($tx->tags->pluck('name')->contains('Dinner'));
    }

    public function test_create_expense_throws_when_insufficient_balance(): void
    {
        $wallet = Wallet::create(['name' => 'Poor Wallet', 'initial_balance' => 10000, 'current_balance' => 10000]);
        $category = Category::create(['name' => 'Gadgets', 'type' => CategoryType::Expense]);
        $this->expectException(InsufficientBalanceException::class);
        $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
    }

    public function test_update_transaction_reverts_old_and_applies_new_balance(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Groceries', 'type' => CategoryType::Expense]);
        $tx = $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 20000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Old',
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(80000.0, (float) $wallet->current_balance);
        $updated = $this->service->updateTransaction($tx, [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'description' => 'New',
        ], null);
        $this->assertNotNull($updated);
        $wallet->refresh();
        $this->assertEquals(50000.0, (float) $wallet->current_balance);
    }

    public function test_update_transaction_returns_null_when_nothing_changed(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Groceries', 'type' => CategoryType::Expense]);
        $tx = $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 20000,
            'transaction_date' => '2026-03-01',
            'description' => 'Groceries',
        ], ['Food'], $this->user->id);
        $tx->load('tags');
        $result = $this->service->updateTransaction($tx, [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 20000,
            'transaction_date' => '2026-03-01',
            'description' => 'Groceries',
        ], ['Food']);
        $this->assertNull($result);
    }

    public function test_update_transaction_with_wallet_change(): void
    {
        $wallet1 = Wallet::create(['name' => 'Wallet 1', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $wallet2 = Wallet::create(['name' => 'Wallet 2', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Books', 'type' => CategoryType::Expense]);
        $tx = $this->service->createTransaction([
            'wallet_id' => $wallet1->id,
            'category_id' => $category->id,
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $wallet1->refresh();
        $this->assertEquals(70000.0, (float) $wallet1->current_balance);
        $this->service->updateTransaction($tx, [
            'wallet_id' => $wallet2->id,
            'category_id' => $category->id,
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
        ], null);
        $wallet1->refresh();
        $wallet2->refresh();
        $this->assertEquals(100000.0, (float) $wallet1->current_balance);
        $this->assertEquals(70000.0, (float) $wallet2->current_balance);
    }

    public function test_delete_transaction_reverts_balance(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Income', 'type' => CategoryType::Income]);
        $tx = $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 40000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $wallet->refresh();
        $this->assertEquals(140000.0, (float) $wallet->current_balance);
        $this->service->deleteTransaction($tx);
        $wallet->refresh();
        $this->assertEquals(100000.0, (float) $wallet->current_balance);
        $this->assertSoftDeleted('finance_transactions', ['id' => $tx->id]);
    }

    public function test_create_transfer_adjusts_both_wallets(): void
    {
        $from = Wallet::create(['name' => 'Sender', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $to = Wallet::create(['name' => 'Receiver', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $result = $this->service->createTransfer([
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 75000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Transfer test',
        ], ['TransferTag'], $this->user->id);
        $from->refresh();
        $to->refresh();
        $this->assertEquals(125000.0, (float) $from->current_balance);
        $this->assertEquals(125000.0, (float) $to->current_balance);
        $this->assertEquals($result['out']->id, $result['in']->transfer_pair_id);
        $this->assertEquals($result['in']->id, $result['out']->transfer_pair_id);
    }

    public function test_create_transfer_throws_when_insufficient_balance(): void
    {
        $from = Wallet::create(['name' => 'Sender', 'initial_balance' => 10000, 'current_balance' => 10000]);
        $to = Wallet::create(['name' => 'Receiver', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $this->expectException(InsufficientBalanceException::class);
        $this->service->createTransfer([
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
    }

    public function test_update_transfer_adjusts_all_wallets(): void
    {
        $from = Wallet::create(['name' => 'Sender', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $to = Wallet::create(['name' => 'Receiver', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $pair = $this->service->createTransfer([
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $this->service->updateTransfer($pair['out'], [
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 80000,
            'transaction_date' => now()->toDateString(),
        ], null);
        $from->refresh();
        $to->refresh();
        $this->assertEquals(120000.0, (float) $from->current_balance);
        $this->assertEquals(130000.0, (float) $to->current_balance);
    }

    public function test_delete_transfer_reverts_both_wallets(): void
    {
        $from = Wallet::create(['name' => 'Sender', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $to = Wallet::create(['name' => 'Receiver', 'initial_balance' => 50000, 'current_balance' => 50000]);
        $pair = $this->service->createTransfer([
            'from_wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ], null, $this->user->id);
        $this->service->deleteTransaction($pair['out']);
        $from->refresh();
        $to->refresh();
        $this->assertEquals(200000.0, (float) $from->current_balance);
        $this->assertEquals(50000.0, (float) $to->current_balance);
        $this->assertSoftDeleted('finance_transactions', ['id' => $pair['out']->id]);
        $this->assertSoftDeleted('finance_transactions', ['id' => $pair['in']->id]);
    }

    public function test_create_transaction_creates_audit_log(): void
    {
        $wallet = Wallet::create(['name' => 'Audit Wallet', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Consulting', 'type' => CategoryType::Income]);
        $this->service->createTransaction([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 60000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Audit test',
        ], null, $this->user->id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_created',
        ]);
    }
}