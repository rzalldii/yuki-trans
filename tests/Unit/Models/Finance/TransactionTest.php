<?php

namespace Tests\Unit\Models\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_transfer_and_is_recurring_helpers(): void
    {
        $normalTx = new Transaction([
            'type' => 'expense',
            'recurring_id' => null,
        ]);
        $this->assertFalse($normalTx->isTransfer());
        $this->assertFalse($normalTx->isRecurring());
        $transferTx = new Transaction([
            'type' => 'transfer_out',
            'recurring_id' => null,
        ]);
        $this->assertTrue($transferTx->isTransfer());
        $recurringTx = new Transaction([
            'type' => 'income',
            'recurring_id' => 5,
        ]);
        $this->assertTrue($recurringTx->isRecurring());
    }

    public function test_scopes_filter_transactions_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $wallet = Wallet::create(['name' => 'W', 'initial_balance' => 0, 'current_balance' => 0]);
        $cat = Category::create(['name' => 'C', 'type' => 'income']);
        Transaction::create([
            'user_id' => $user1->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => 100000,
            'transaction_date' => '2026-03-01',
        ]);
        Transaction::create([
            'user_id' => $user2->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 50000,
            'transaction_date' => '2026-03-10',
        ]);
        $incomeOnly = Transaction::ofType('income')->get();
        $this->assertCount(1, $incomeOnly);
        $forUser1 = Transaction::forUser($user1->id)->get();
        $this->assertCount(1, $forUser1);
        $this->assertEquals($user1->id, $forUser1->first()->user_id);
        $betweenDates = Transaction::betweenDates('2026-03-05', '2026-03-15')->get();
        $this->assertCount(1, $betweenDates);
        $this->assertEquals(50000, (float) $betweenDates->first()->amount);
    }
}