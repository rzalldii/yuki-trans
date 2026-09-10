<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Finance;

use App\Enums\TransactionType;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_next_due_date_for_frequencies(): void
    {
        $baseDate = Carbon::parse('2026-01-01');
        $daily = new Recurring(['frequency' => 'daily', 'start_date' => $baseDate]);
        $this->assertEquals('2026-01-02', $daily->calculateNextDueDate($baseDate)->toDateString());
        $weekly = new Recurring(['frequency' => 'weekly', 'start_date' => $baseDate]);
        $this->assertEquals('2026-01-08', $weekly->calculateNextDueDate($baseDate)->toDateString());
        $monthly = new Recurring(['frequency' => 'monthly', 'start_date' => $baseDate]);
        $this->assertEquals('2026-02-01', $monthly->calculateNextDueDate($baseDate)->toDateString());
        $yearly = new Recurring(['frequency' => 'yearly', 'start_date' => $baseDate]);
        $this->assertEquals('2027-01-01', $yearly->calculateNextDueDate($baseDate)->toDateString());
    }

    public function test_calculate_next_due_date_returns_null_when_exceeding_end_date(): void
    {
        $baseDate = Carbon::parse('2026-01-15');
        $recurring = new Recurring([
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]);
        $this->assertNull($recurring->calculateNextDueDate($baseDate));
    }

    public function test_execute_transaction_creates_expense_transaction_and_updates_balance(): void
    {
        $user = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Main Wallet',
            'initial_balance' => 200000,
            'current_balance' => 200000,
        ]);
        $category = Category::create([
            'name' => 'Subscription',
            'type' => 'expense',
        ]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-01-01',
            'is_active' => true,
        ]);
        $tx = $recurring->executeTransaction($user->id);
        $this->assertNotNull($tx);
        $this->assertEquals(50000, $tx->amount);
        $this->assertEquals(TransactionType::Expense, $tx->type);
        $this->assertEquals(150000, $wallet->fresh()->current_balance);
        $this->assertEquals('2026-02-01', $recurring->fresh()->next_due_date->toDateString());
    }

    public function test_scope_active_and_due_on(): void
    {
        $wallet = Wallet::create(['name' => 'W', 'initial_balance' => 0, 'current_balance' => 0]);
        $category = Category::create(['name' => 'C', 'type' => 'income']);
        Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 10000,
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-01-05',
            'is_active' => true,
        ]);
        Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 20000,
            'frequency' => 'daily',
            'start_date' => '2026-01-01',
            'next_due_date' => '2026-01-10',
            'is_active' => false,
        ]);
        $activeRecurrings = Recurring::active()->get();
        $this->assertCount(1, $activeRecurrings);
        $dueOnJan5 = Recurring::active()->dueOn('2026-01-05')->get();
        $this->assertCount(1, $dueOnJan5);
        $dueOnJan4 = Recurring::active()->dueOn('2026-01-04')->get();
        $this->assertCount(0, $dueOnJan4);
    }
}