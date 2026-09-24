<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Enums\Finance\CategoryType;
use App\Enums\Finance\RecurringType;
use App\Enums\Finance\TransactionType;
use App\Exceptions\Finance\InsufficientBalanceException;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use App\Services\Finance\RecurringExecutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecurringExecutionService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecurringExecutionService::class);
        $this->user = User::factory()->create();
    }

    public function test_execute_recurring_creates_transaction_and_updates_next_due(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $category = Category::create(['name' => 'Subscription', 'type' => CategoryType::Expense]);
        $today = now()->toDateString();
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => $today,
            'next_due_date' => $today,
            'is_active' => true,
        ]);
        $tx = $this->service->executeRecurring($recurring, $this->user->id);
        $this->assertNotNull($tx);
        $this->assertEquals(50000.0, (float) $tx->amount);
        $this->assertEquals($recurring->id, $tx->recurring_id);
        $wallet->refresh();
        $this->assertEquals(150000.0, (float) $wallet->current_balance);
        $recurring->refresh();
        $this->assertNotNull($recurring->next_due_date);
        $this->assertTrue(Carbon::parse($recurring->next_due_date)->gt(Carbon::parse($today)));
    }

    public function test_execute_recurring_skips_inactive(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $category = Category::create(['name' => 'Sub', 'type' => CategoryType::Expense]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => false,
        ]);
        $tx = $this->service->executeRecurring($recurring, $this->user->id);
        $this->assertNull($tx);
    }

    public function test_execute_recurring_skips_future_due_date(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $category = Category::create(['name' => 'Sub', 'type' => CategoryType::Expense]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->addDays(5)->toDateString(),
            'next_due_date' => now()->addDays(5)->toDateString(),
            'is_active' => true,
        ]);
        $tx = $this->service->executeRecurring($recurring, $this->user->id, false);
        $this->assertNull($tx);
    }

    public function test_execute_recurring_transfer_creates_pair(): void
    {
        $from = Wallet::create(['name' => 'From', 'initial_balance' => 300000, 'current_balance' => 300000]);
        $to = Wallet::create(['name' => 'To', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $recurring = Recurring::create([
            'wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'type' => RecurringType::Transfer->value,
            'amount' => 80000,
            'frequency' => 'weekly',
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $tx = $this->service->executeRecurring($recurring, $this->user->id);
        $this->assertNotNull($tx);
        $this->assertEquals(TransactionType::TransferOut, $tx->type);
        $this->assertNotNull($tx->transfer_pair_id);
        $from->refresh();
        $to->refresh();
        $this->assertEquals(220000.0, (float) $from->current_balance);
        $this->assertEquals(180000.0, (float) $to->current_balance);
    }

    public function test_execute_recurring_throws_on_insufficient_balance(): void
    {
        $wallet = Wallet::create(['name' => 'Broke Wallet', 'initial_balance' => 1000, 'current_balance' => 1000]);
        $category = Category::create(['name' => 'Costly', 'type' => CategoryType::Expense]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $this->expectException(InsufficientBalanceException::class);
        $this->service->executeRecurring($recurring, $this->user->id);
    }

    public function test_process_due_recurrings_catches_multiple_due(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 1000000, 'current_balance' => 1000000]);
        $cat1 = Category::create(['name' => 'Cat 1', 'type' => CategoryType::Expense]);
        $cat2 = Category::create(['name' => 'Cat 2', 'type' => CategoryType::Income]);
        Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $cat1->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->subDay()->toDateString(),
            'next_due_date' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);
        Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $cat2->id,
            'type' => 'income',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $processed = $this->service->processDueRecurrings($this->user->id);
        $this->assertGreaterThanOrEqual(2, $processed);
    }

    public function test_execute_recurring_deactivates_after_end_date(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 200000, 'current_balance' => 200000]);
        $category = Category::create(['name' => 'Finite', 'type' => CategoryType::Expense]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 20000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(), // monthly frequency will jump 1 month > 15 days
            'is_active' => true,
        ]);
        $tx = $this->service->executeRecurring($recurring, $this->user->id);
        $this->assertNotNull($tx);
        $recurring->refresh();
        $this->assertFalse($recurring->is_active);
        $this->assertNull($recurring->next_due_date);
    }
}