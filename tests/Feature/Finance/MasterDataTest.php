<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_admin_can_create_update_and_delete_wallet(): void
    {
        $admin = User::factory()->admin()->create();
        $createRes = $this->actingAs($admin)->postJson(route('finance-wallets.store'), [
            'name' => 'BCA Account',
            'initial_balance' => 500000,
        ]);
        $createRes->assertStatus(201);
        $this->assertDatabaseHas('finance_wallets', [
            'name' => 'BCA Account',
            'initial_balance' => 500000,
            'current_balance' => 500000,
        ]);
        $wallet = Wallet::where('name', 'BCA Account')->first();
        $editRes = $this->actingAs($admin)->getJson(route('finance-wallets.edit', $wallet));
        $editRes->assertStatus(200);
        $editRes->assertJson([
            'id' => $wallet->id,
            'name' => 'BCA Account',
            'has_transactions' => false,
        ]);
        $updateRes = $this->actingAs($admin)->putJson(route('finance-wallets.update', $wallet), [
            'name' => 'BCA Main',
            'initial_balance' => 750000,
        ]);
        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('finance_wallets', [
            'id' => $wallet->id,
            'name' => 'BCA Main',
            'initial_balance' => 750000,
            'current_balance' => 750000,
        ]);
        $deleteRes = $this->actingAs($admin)->deleteJson(route('finance-wallets.destroy', $wallet));
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('finance_wallets', ['id' => $wallet->id]);
    }

    public function test_cannot_delete_wallet_with_transactions(): void
    {
        $admin = User::factory()->admin()->create();
        $wallet = Wallet::create([
            'name' => 'Mandiri',
            'initial_balance' => 100000,
            'current_balance' => 100000,
        ]);
        $category = Category::create([
            'name' => 'Utilities',
            'type' => 'expense',
        ]);
        Transaction::create([
            'user_id' => $admin->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 25000,
            'transaction_date' => now()->toDateString(),
        ]);
        $deleteRes = $this->actingAs($admin)->deleteJson(route('finance-wallets.destroy', $wallet));
        $deleteRes->assertStatus(422);
        $this->assertDatabaseHas('finance_wallets', [
            'id' => $wallet->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_create_update_and_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $createRes = $this->actingAs($admin)->postJson(route('finance-categories.store'), [
            'name' => 'Groceries',
            'type' => 'expense',
            'amount' => 1500000,
        ]);
        $createRes->assertStatus(201);
        $this->assertDatabaseHas('finance_categories', [
            'name' => 'Groceries',
            'type' => 'expense',
        ]);
        $category = Category::where('name', 'Groceries')->first();
        $updateRes = $this->actingAs($admin)->putJson(route('finance-categories.update', $category), [
            'name' => 'Supermarket Groceries',
            'type' => 'expense',
            'amount' => 2000000,
        ]);
        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('finance_categories', [
            'id' => $category->id,
            'name' => 'Supermarket Groceries',
        ]);
        $deleteRes = $this->actingAs($admin)->deleteJson(route('finance-categories.destroy', $category));
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('finance_categories', ['id' => $category->id]);
    }

    public function test_admin_can_create_update_and_delete_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $createRes = $this->actingAs($admin)->postJson(route('finance-tags.store'), [
            'name' => 'Urgent',
            'color' => '#ff3e1d',
        ]);
        $createRes->assertStatus(201);
        $this->assertDatabaseHas('finance_tags', [
            'name' => 'Urgent',
            'color' => '#ff3e1d',
        ]);
        $tag = Tag::where('name', 'Urgent')->first();
        $updateRes = $this->actingAs($admin)->putJson(route('finance-tags.update', $tag), [
            'name' => 'Very Urgent',
            'color' => '#ffab00',
        ]);
        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('finance_tags', [
            'id' => $tag->id,
            'name' => 'Very Urgent',
            'color' => '#ffab00',
        ]);
        $deleteRes = $this->actingAs($admin)->deleteJson(route('finance-tags.destroy', $tag));
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('finance_tags', ['id' => $tag->id]);
    }

    public function test_non_admin_cannot_access_master_data_mutations(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $resWallet = $this->actingAs($user)->postJson(route('finance-wallets.store'), [
            'name' => 'Unauthorized Wallet',
            'initial_balance' => 1000,
        ]);
        $resWallet->assertStatus(403);
        $resCat = $this->actingAs($user)->postJson(route('finance-categories.store'), [
            'name' => 'Unauthorized Category',
            'type' => 'income',
        ]);
        $resCat->assertStatus(403);
        $resTag = $this->actingAs($user)->postJson(route('finance-tags.store'), [
            'name' => 'Unauthorized Tag',
        ]);
        $resTag->assertStatus(403);
    }
}