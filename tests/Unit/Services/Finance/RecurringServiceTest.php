<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Enums\Finance\CategoryType;
use App\Enums\Finance\Frequency;
use App\Enums\Finance\RecurringType;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use App\Services\Finance\RecurringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecurringService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecurringService::class);
        $this->user = User::factory()->create();
    }

    public function test_create_recurring_sets_next_due_date_to_start_date(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000]);
        $category = Category::create(['name' => 'Internet', 'type' => CategoryType::Expense]);
        $startDate = now()->addDays(5)->toDateString();
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 300000,
            'frequency' => 'monthly',
            'start_date' => $startDate,
            'description' => 'Monthly WiFi',
        ]);
        $this->assertEquals($startDate, $recurring->next_due_date?->toDateString());
        $this->assertTrue($recurring->is_active);
    }

    public function test_create_transfer_recurring_nullifies_category(): void
    {
        $from = Wallet::create(['name' => 'Checking', 'initial_balance' => 500000]);
        $to = Wallet::create(['name' => 'Savings', 'initial_balance' => 100000]);
        $category = Category::create(['name' => 'Should Be Null', 'type' => CategoryType::Expense]);
        $recurring = $this->service->createRecurring([
            'wallet_id' => $from->id,
            'to_wallet_id' => $to->id,
            'category_id' => $category->id,
            'type' => 'transfer',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'description' => 'Auto-save',
        ]);
        $this->assertNull($recurring->category_id);
        $this->assertEquals($to->id, $recurring->to_wallet_id);
        $this->assertEquals(RecurringType::Transfer, $recurring->type);
    }

    public function test_update_recurring_returns_null_when_nothing_changed(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000]);
        $category = Category::create(['name' => 'Rent', 'type' => CategoryType::Expense]);
        $startDate = now()->toDateString();
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 500000,
            'frequency' => 'monthly',
            'start_date' => $startDate,
            'description' => 'Monthly Rent',
        ]);
        $recurring->load('tags');
        $result = $this->service->updateRecurring($recurring, [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 500000,
            'frequency' => 'monthly',
            'start_date' => $startDate,
            'description' => 'Monthly Rent',
        ], false);
        $this->assertNull($result);
    }

    public function test_update_recurring_recalculates_next_due_date(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000]);
        $category = Category::create(['name' => 'Gym', 'type' => CategoryType::Expense]);
        $startDate = now()->addDays(2)->toDateString();
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150000,
            'frequency' => 'monthly',
            'start_date' => $startDate,
        ]);
        $newStartDate = now()->addDays(10)->toDateString();
        $updated = $this->service->updateRecurring($recurring, [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150000,
            'frequency' => 'monthly',
            'start_date' => $newStartDate,
        ], false);
        $this->assertNotNull($updated);
        $this->assertEquals($newStartDate, $updated->next_due_date?->toDateString());
    }

    public function test_delete_recurring_with_cascade_deletes_transactions(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 500000]);
        $category = Category::create(['name' => 'Streaming', 'type' => CategoryType::Expense]);
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
        ]);
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'recurring_id' => $recurring->id,
            'type' => 'expense',
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ]);
        $this->service->deleteRecurring($recurring, true);
        $this->assertDatabaseMissing('finance_recurrings', ['id' => $recurring->id]);
        $this->assertSoftDeleted('finance_transactions', ['id' => $tx->id]);
    }

    public function test_delete_recurring_without_cascade_keeps_transactions(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 500000]);
        $category = Category::create(['name' => 'Streaming', 'type' => CategoryType::Expense]);
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
        ]);
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'recurring_id' => $recurring->id,
            'type' => 'expense',
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
        ]);
        $this->service->deleteRecurring($recurring, false);
        $this->assertDatabaseMissing('finance_recurrings', ['id' => $recurring->id]);
        $this->assertDatabaseHas('finance_transactions', ['id' => $tx->id, 'deleted_at' => null]);
    }

    public function test_toggle_status_deactivates(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000]);
        $category = Category::create(['name' => 'Bills', 'type' => CategoryType::Expense]);
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 20000,
            'frequency' => 'daily',
            'start_date' => now()->toDateString(),
        ]);
        $this->assertTrue($recurring->is_active);
        $this->service->toggleStatus($recurring);
        $recurring->refresh();
        $this->assertFalse($recurring->is_active);
    }

    public function test_toggle_status_reactivates_and_calculates_next_due(): void
    {
        $wallet = Wallet::create(['name' => 'Wallet', 'initial_balance' => 100000]);
        $category = Category::create(['name' => 'Bills', 'type' => CategoryType::Expense]);
        $recurring = $this->service->createRecurring([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 20000,
            'frequency' => 'daily',
            'start_date' => now()->subDays(3)->toDateString(),
        ]);
        $recurring->update(['is_active' => false, 'next_due_date' => null]);
        $this->service->toggleStatus($recurring);
        $recurring->refresh();
        $this->assertTrue($recurring->is_active);
        $this->assertNotNull($recurring->next_due_date);
    }
}