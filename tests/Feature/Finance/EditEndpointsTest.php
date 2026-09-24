<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Enums\Finance\CategoryType;
use App\Enums\Finance\RecurringType;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use App\Services\Finance\TransactionService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_category_edit_response_shape(): void
    {
        $category = Category::create([
            'name' => 'Groceries',
            'type' => CategoryType::Expense,
            'amount' => 1500000,
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('finance-categories.edit', $category));
        $res->assertOk();
        $res->assertJson([
            'id' => $category->id,
            'name' => 'Groceries',
            'type' => 'expense',
            'amount' => 1500000.0,
            'has_transactions' => false,
        ]);
    }

    public function test_tag_edit_response_shape(): void
    {
        $tag = Tag::create([
            'name' => 'Urgent',
            'color' => '#ff3e1d',
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('finance-tags.edit', $tag));
        $res->assertOk();
        $res->assertJson([
            'id' => $tag->id,
            'name' => 'Urgent',
            'color' => '#ff3e1d',
        ]);
    }

    public function test_wallet_edit_response_shape(): void
    {
        $wallet = Wallet::create([
            'name' => 'BCA Account',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('finance-wallets.edit', $wallet));
        $res->assertOk();
        $res->assertJson([
            'id' => $wallet->id,
            'name' => 'BCA Account',
            'initial_balance' => 500000.0,
            'current_balance' => 500000.0,
            'has_transactions' => false,
        ]);
    }

    public function test_recurring_edit_response_shape(): void
    {
        $wallet = Wallet::create(['name' => 'W1', 'initial_balance' => 1000000, 'current_balance' => 1000000]);
        $category = Category::create(['name' => 'Rent', 'type' => CategoryType::Expense]);
        $tag = Tag::create(['name' => 'Home', 'color' => '#696cff']);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => RecurringType::Expense->value,
            'amount' => 750000,
            'frequency' => 'monthly',
            'start_date' => '2026-03-01',
            'next_due_date' => '2026-03-01',
            'description' => 'Monthly House Rent',
            'is_active' => true,
        ]);
        $recurring->tags()->attach($tag->id);
        $res = $this->actingAs($this->admin)->getJson(route('finance-recurrings.edit', $recurring));
        $res->assertOk();
        $res->assertJson([
            'id' => $recurring->id,
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 750000.0,
            'frequency' => 'monthly',
            'start_date' => '2026-03-01',
            'description' => 'Monthly House Rent',
            'is_active' => true,
            'has_transactions' => false,
        ]);
        $tags = $res->json('tags');
        $this->assertIsArray($tags);
        $this->assertContains('Home', $tags);
    }

    public function test_transaction_edit_response_shape(): void
    {
        $wallet = Wallet::create(['name' => 'Cash', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $category = Category::create(['name' => 'Lunch', 'type' => CategoryType::Expense]);
        $tag = Tag::create(['name' => 'Food', 'color' => '#71dd37']);
        $tx = Transaction::create([
            'user_id' => $this->admin->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => TransactionType::Expense->value,
            'amount' => 35000,
            'transaction_date' => '2026-03-15',
            'description' => 'Steak Lunch',
        ]);
        $tx->tags()->attach($tag->id);
        $res = $this->actingAs($this->admin)->getJson(route('finance-transactions.edit', $tx));
        $res->assertOk();
        $res->assertJson([
            'id' => $tx->id,
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 35000.0,
            'transaction_date' => '2026-03-15',
            'description' => 'Steak Lunch',
        ]);
        $tags = $res->json('tags');
        $this->assertIsArray($tags);
        $this->assertContains('Food', $tags);
    }

    public function test_transfer_edit_response_shape(): void
    {
        $wFrom = Wallet::create(['name' => 'WFrom', 'initial_balance' => 500000, 'current_balance' => 500000]);
        $wTo = Wallet::create(['name' => 'WTo', 'initial_balance' => 100000, 'current_balance' => 100000]);
        $service = app(TransactionService::class);
        $pair = $service->createTransfer([
            'from_wallet_id' => $wFrom->id,
            'to_wallet_id' => $wTo->id,
            'amount' => 150000,
            'transaction_date' => '2026-03-20',
            'description' => 'Transfer To Savings',
        ], ['TransferTag'], $this->admin->id);
        $resOut = $this->actingAs($this->admin)->getJson(route('finance-transactions.edit', $pair['out']));
        $resOut->assertOk();
        $resOut->assertJson([
            'id' => $pair['out']->id,
            'amount' => 150000.0,
            'from_wallet_id' => $wFrom->id,
            'to_wallet_id' => $wTo->id,
            'transaction_date' => '2026-03-20',
            'description' => 'Transfer To Savings',
        ]);
        $this->assertContains('TransferTag', $resOut->json('tags'));
        $resIn = $this->actingAs($this->admin)->getJson(route('finance-transactions.edit', $pair['in']));
        $resIn->assertOk();
        $resIn->assertJson([
            'id' => $pair['in']->id,
            'amount' => 150000.0,
            'from_wallet_id' => $wFrom->id,
            'to_wallet_id' => $wTo->id,
            'transaction_date' => '2026-03-20',
            'description' => 'Transfer To Savings',
        ]);
        $this->assertContains('TransferTag', $resIn->json('tags'));
    }
}