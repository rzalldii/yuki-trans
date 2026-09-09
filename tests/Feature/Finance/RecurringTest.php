<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use App\Services\Finance\RecurringService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_it_processes_due_recurring_transactions(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Cash',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $category = Category::create([
            'name' => 'Internet',
            'type' => 'expense',
        ]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150000,
            'frequency' => 'monthly',
            'start_date' => now()->subDay()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $service = app(RecurringService::class);
        $count = $service->processDueRecurrings($admin->id);
        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('finance_transactions', [
            'recurring_id' => $recurring->id,
            'amount' => 150000,
            'type' => 'expense',
        ]);
        $this->assertEquals(350000, $wallet->fresh()->current_balance);
        $this->assertTrue($recurring->fresh()->next_due_date->greaterThan(now()));
    }

    public function test_artisan_command_finance_process_recurring(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Bank',
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
        ]);
        $category = Category::create([
            'name' => 'Sewa Kantor',
            'type' => 'expense',
        ]);
        Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 200000,
            'frequency' => 'monthly',
            'start_date' => now()->subDays(2)->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $this->artisan('finance:process-recurring')
            ->expectsOutputToContain('Processed 1 due recurring transaction(s).')
            ->assertExitCode(0);
        $this->assertEquals(800000, $wallet->fresh()->current_balance);
    }

    public function test_cannot_create_recurring_with_soft_deleted_wallet_or_category(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Deleted Wallet',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $category = Category::create([
            'name' => 'Deleted Category',
            'type' => 'expense',
        ]);
        $wallet->delete();
        $category->delete();
        $response = $this->actingAs($admin)->postJson(route('finance-recurring.store'), [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['wallet_id', 'category_id']);
    }
}