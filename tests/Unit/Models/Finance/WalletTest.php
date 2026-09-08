<?php

namespace Tests\Unit\Models\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adjusts_balance_correctly_for_income_and_expense(): void
    {
        $wallet = Wallet::create([
            'name' => 'Main Wallet',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $wallet->adjustBalance('income', 50000);
        $this->assertEquals(150000, $wallet->fresh()->current_balance);
        $wallet->adjustBalance('expense', 20000);
        $this->assertEquals(130000, $wallet->fresh()->current_balance);
        $wallet->adjustBalance('transfer_in', 10000);
        $this->assertEquals(140000, $wallet->fresh()->current_balance);
        $wallet->adjustBalance('transfer_out', 30000);
        $this->assertEquals(110000, $wallet->fresh()->current_balance);
    }

    public function test_it_reverts_balance_correctly(): void
    {
        $wallet = Wallet::create([
            'name' => 'Secondary Wallet',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $wallet->revertBalance('income', 50000);
        $this->assertEquals(50000, $wallet->fresh()->current_balance);
        $wallet->revertBalance('expense', 20000);
        $this->assertEquals(70000, $wallet->fresh()->current_balance);
        $wallet->revertBalance('transfer_in', 10000);
        $this->assertEquals(60000, $wallet->fresh()->current_balance);
        $wallet->revertBalance('transfer_out', 30000);
        $this->assertEquals(90000, $wallet->fresh()->current_balance);
    }

    public function test_it_recalculates_balance_from_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'name' => 'Recalc Wallet',
            'initial_balance' => 50000,
            'current_balance' => 50000,
        ]);
        $incomeCat = Category::create(['name' => 'Gaji', 'type' => 'income']);
        $expenseCat = Category::create(['name' => 'Makan', 'type' => 'expense']);
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $incomeCat->id,
            'type' => 'income',
            'amount' => 100000,
            'transaction_date' => now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $expenseCat->id,
            'type' => 'expense',
            'amount' => 30000,
            'transaction_date' => now()->toDateString(),
        ]);
        $wallet->update(['current_balance' => 0]);
        $this->assertEquals(0, $wallet->fresh()->current_balance);
        $wallet->recalculateBalance();
        $this->assertEquals(120000, $wallet->fresh()->current_balance);
    }
}