<?php

namespace Tests\Unit\Models\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_label_accessor_returns_target_for_income_and_budget_for_expense(): void
    {
        $incomeCat = new Category(['type' => 'income']);
        $expenseCat = new Category(['type' => 'expense']);
        $this->assertEquals('Target', $incomeCat->amount_label);
        $this->assertEquals('Budget', $expenseCat->amount_label);
    }

    public function test_amount_is_cast_to_decimal(): void
    {
        $category = Category::create([
            'name' => 'Salary',
            'type' => 'income',
            'amount' => 5000000.50,
        ]);
        $this->assertEquals('5000000.50', $category->amount);
    }

    public function test_scope_of_type_filters_correctly(): void
    {
        Category::create(['name' => 'Cat Income', 'type' => 'income']);
        Category::create(['name' => 'Cat Expense', 'type' => 'expense']);
        $incomeCats = Category::ofType('income')->get();
        $this->assertCount(1, $incomeCats);
        $this->assertEquals('Cat Income', $incomeCats->first()->name);
        $expenseCats = Category::ofType('expense')->get();
        $this->assertCount(1, $expenseCats);
        $this->assertEquals('Cat Expense', $expenseCats->first()->name);
    }

    public function test_get_actual_and_spent_for_month(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'name' => 'Cash',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $category = Category::create([
            'name' => 'Groceries',
            'type' => 'expense',
            'amount' => 500000,
        ]);
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150000,
            'transaction_date' => '2026-03-05',
        ]);
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100000,
            'transaction_date' => '2026-03-15',
        ]);
        // Different month
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 200000,
            'transaction_date' => '2026-04-01',
        ]);
        $spentMarch = $category->getSpentForMonth('2026-03');
        $this->assertEquals(250000, $spentMarch);
        $actualMarch = $category->getActualForMonth('2026-03');
        $this->assertEquals(250000, $actualMarch);
    }
}