<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CategoryType;
use App\Enums\Frequency;
use App\Enums\RecurringType;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_authenticated_views_render_successfully(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);
        $wallet1 = Wallet::create([
            'name' => 'Main Wallet',
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
        ]);
        $wallet2 = Wallet::create([
            'name' => 'Savings',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $catIncome = Category::create([
            'name' => 'Salary',
            'type' => CategoryType::Income,
            'amount' => 5000000,
        ]);
        $catExpense = Category::create([
            'name' => 'Food',
            'type' => CategoryType::Expense,
            'amount' => 2000000,
        ]);
        $tag = Tag::create([
            'name' => 'Groceries',
            'color' => '#10B981',
        ]);
        $tIncome = Transaction::create([
            'user_id' => $admin->id,
            'wallet_id' => $wallet1->id,
            'category_id' => $catIncome->id,
            'type' => TransactionType::Income,
            'amount' => 500000,
            'transaction_date' => now(),
        ]);
        $tIncome->tags()->attach($tag);
        $tExpense = Transaction::create([
            'user_id' => $admin->id,
            'wallet_id' => $wallet1->id,
            'category_id' => $catExpense->id,
            'type' => TransactionType::Expense,
            'amount' => 100000,
            'transaction_date' => now(),
        ]);
        $tExpense->tags()->attach($tag);
        $rec = Recurring::create([
            'wallet_id' => $wallet1->id,
            'category_id' => $catExpense->id,
            'type' => RecurringType::Expense,
            'frequency' => Frequency::Monthly,
            'amount' => 50000,
            'start_date' => now()->subMonth(),
            'next_due_date' => now()->addDays(5),
            'is_active' => true,
        ]);
        $rec->tags()->attach($tag);
        $responseDashboard = $this->actingAs($admin)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseTransactions = $this->actingAs($admin)->get(route('finance-transactions.index'));
        $responseTransactions->assertOk();
        $responseTransactions->assertSee('+ Rp 500.000', false);
        $responseTransactions->assertSee('- Rp 100.000', false);
        $responseTransactions->assertSee('Income');
        $responseTransactions->assertSee('Expense');
        $responseMasterData = $this->actingAs($admin)->get(route('finance-master-data.index'));
        $responseMasterData->assertOk();
        $responseMasterData->assertSee('Salary');
        $responseMasterData->assertSee('Food');
        $responseMasterData->assertDontSee('No income categories available.');
        $responseMasterData->assertDontSee('No expense categories available.');
        $responseUsers = $this->actingAs($admin)->get(route('users.index'));
        $responseUsers->assertOk();
        $responseUsers->assertSee('Admin');
        $responseProfile = $this->actingAs($admin)->get(route('profile.show'));
        $responseProfile->assertOk();
        $responseProfile->assertSee('Admin');
        $responseUserProfile = $this->actingAs($admin)->get(route('users.profile', $user));
        $responseUserProfile->assertOk();
        $responseUserProfile->assertSee('User');
        $responseAuditLogs = $this->actingAs($admin)->get(route('audit-logs.index'));
        $responseAuditLogs->assertOk();
    }
}