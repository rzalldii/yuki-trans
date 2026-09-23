<?php

declare(strict_types=1);

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
        $response = $this->actingAs($admin)->postJson(route('finance-recurrings.store'), [
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

    public function test_pausing_and_reactivating_due_recurring_preserves_due_date_and_generates_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Main Wallet',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $category = Category::create([
            'name' => 'Electricity',
            'type' => 'expense',
        ]);
        $dueDate = now()->subDay()->toDateString();
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100000,
            'frequency' => 'monthly',
            'start_date' => now()->subMonth()->toDateString(),
            'next_due_date' => $dueDate,
            'is_active' => true,
        ]);
        $toggleResp1 = $this->actingAs($admin)->patchJson(route('finance-recurrings.toggle-status', $recurring));
        $toggleResp1->assertOk()->assertJson(['success' => true, 'is_active' => false]);
        $this->assertFalse($recurring->fresh()->is_active);
        $this->assertEquals($dueDate, $recurring->fresh()->next_due_date->toDateString());
        $toggleResp2 = $this->actingAs($admin)->patchJson(route('finance-recurrings.toggle-status', $recurring));
        $toggleResp2->assertOk()->assertJson(['success' => true, 'is_active' => true]);
        $this->assertTrue($recurring->fresh()->is_active);
        $this->assertEquals($dueDate, $recurring->fresh()->next_due_date->toDateString());
        $genResp = $this->actingAs($admin)->postJson(route('finance-recurrings.generate'));
        $genResp->assertOk()->assertJson(['success' => true, 'generated' => 1]);
        $this->assertDatabaseHas('finance_transactions', [
            'recurring_id' => $recurring->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 100000,
            'type' => 'expense',
        ]);
        $this->assertEquals(400000, $wallet->fresh()->current_balance);
        $this->assertEquals($dueDate, $recurring->fresh()->last_generated_at->toDateString());
        $this->assertTrue($recurring->fresh()->next_due_date->greaterThan(now()));
    }

    public function test_admin_can_create_edit_update_and_delete_recurring(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'BCA',
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
        ]);
        $category = Category::create([
            'name' => 'Electricity',
            'type' => 'expense',
        ]);
        $createRes = $this->actingAs($admin)->postJson(route('finance-recurrings.store'), [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 125000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'description' => 'Monthly Electricity Bill',
            'tags' => ['Bills', 'Utilities'],
        ]);
        $createRes->assertStatus(201);
        $this->assertDatabaseHas('finance_recurrings', [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 125000,
            'description' => 'Monthly Electricity Bill',
        ]);
        $recurring = Recurring::where('description', 'Monthly Electricity Bill')->first();
        $editRes = $this->actingAs($admin)->getJson(route('finance-recurrings.edit', $recurring));
        $editRes->assertOk();
        $editRes->assertJson([
            'id' => $recurring->id,
            'amount' => 125000.0,
            'description' => 'Monthly Electricity Bill',
        ]);
        $updateRes = $this->actingAs($admin)->putJson(route('finance-recurrings.update', $recurring), [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 150000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'description' => 'Updated Electricity Bill',
            'is_active' => true,
            'tags' => ['Bills'],
        ]);
        $updateRes->assertOk();
        $this->assertDatabaseHas('finance_recurrings', [
            'id' => $recurring->id,
            'amount' => 150000,
            'description' => 'Updated Electricity Bill',
        ]);
        $deleteRes = $this->actingAs($admin)->deleteJson(route('finance-recurrings.destroy', $recurring), [
            'delete_transactions' => 0,
        ]);
        $deleteRes->assertOk();
        $this->assertDatabaseMissing('finance_recurrings', ['id' => $recurring->id]);
    }

    public function test_admin_update_recurring_no_changes_returns_204(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'BCA 2',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $category = Category::create([
            'name' => 'Water',
            'type' => 'expense',
        ]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $res = $this->actingAs($admin)->putJson(route('finance-recurrings.update', $recurring), [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $res->assertStatus(204);
    }

    public function test_non_admin_cannot_access_recurring_mutations(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create([
            'name' => 'Wallet A',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $category = Category::create([
            'name' => 'Cat A',
            'type' => 'expense',
        ]);
        $recurring = Recurring::create([
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $resStore = $this->actingAs($user)->postJson(route('finance-recurrings.store'), [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
        ]);
        $resStore->assertStatus(403);
        $resUpdate = $this->actingAs($user)->putJson(route('finance-recurrings.update', $recurring), [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 60000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $resUpdate->assertStatus(403);
        $resToggle = $this->actingAs($user)->patchJson(route('finance-recurrings.toggle-status', $recurring));
        $resToggle->assertStatus(403);
        $resDelete = $this->actingAs($user)->deleteJson(route('finance-recurrings.destroy', $recurring));
        $resDelete->assertStatus(403);
    }
}