<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Enums\CategoryType;
use App\Enums\Frequency;
use App\Enums\RecurringType;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Http\Resources\Audit\AuditLogResource;
use App\Http\Resources\Finance\CategoryResource;
use App\Http\Resources\Finance\RecurringResource;
use App\Http\Resources\Finance\TagResource;
use App\Http\Resources\Finance\TransactionResource;
use App\Http\Resources\Finance\WalletResource;
use App\Http\Resources\User\UserResource;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResourceSerializationTest extends TestCase
{
    public function test_wallet_resource_serialization(): void
    {
        $wallet = new Wallet([
            'name' => 'GoPay',
            'initial_balance' => 150000.50,
            'current_balance' => 200000.75,
        ]);
        $wallet->id = 1;
        $wallet->created_at = Carbon::parse('2026-01-01 10:00:00');
        $wallet->updated_at = Carbon::parse('2026-01-02 11:00:00');
        $data = (new WalletResource($wallet))->toArray(Request::create('/'));
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('GoPay', $data['name']);
        $this->assertSame(150000.50, $data['initial_balance']);
        $this->assertSame(200000.75, $data['current_balance']);
        $this->assertNotNull($data['created_at']);
    }

    public function test_category_resource_serialization(): void
    {
        $cat = new Category([
            'name' => 'Food & Drink',
            'type' => CategoryType::Expense,
            'amount' => 500000.0,
        ]);
        $cat->id = 2;
        $data = (new CategoryResource($cat))->toArray(Request::create('/'));
        $this->assertEquals(2, $data['id']);
        $this->assertEquals('Food & Drink', $data['name']);
        $this->assertEquals('expense', $data['type']);
        $this->assertSame(500000.0, $data['amount']);
        $this->assertEquals('Budget', $data['amount_label']);
    }

    public function test_tag_resource_serialization(): void
    {
        $tag = new Tag([
            'name' => 'Urgent',
            'color' => '#ff3e1d',
        ]);
        $tag->id = 3;
        $data = (new TagResource($tag))->toArray(Request::create('/'));
        $this->assertEquals(3, $data['id']);
        $this->assertEquals('Urgent', $data['name']);
        $this->assertEquals('#ff3e1d', $data['color']);
        $this->assertEquals('tag-badge-red', $data['badge_class']);
    }

    public function test_transaction_resource_serialization(): void
    {
        $tx = new Transaction([
            'user_id' => 1,
            'wallet_id' => 1,
            'category_id' => 2,
            'type' => TransactionType::Income,
            'amount' => 100000.0,
            'description' => 'Salary bonus',
            'transaction_date' => Carbon::parse('2026-01-15'),
        ]);
        $tx->id = 10;
        $data = (new TransactionResource($tx))->toArray(Request::create('/'));
        $this->assertEquals(10, $data['id']);
        $this->assertEquals('income', $data['type']);
        $this->assertSame(100000.0, $data['amount']);
        $this->assertEquals('Salary bonus', $data['description']);
        $this->assertEquals('2026-01-15', $data['transaction_date']);
    }

    public function test_recurring_resource_serialization(): void
    {
        $rec = new Recurring([
            'wallet_id' => 1,
            'category_id' => 2,
            'type' => RecurringType::Expense,
            'amount' => 25000.0,
            'description' => 'Netflix subscription',
            'frequency' => Frequency::Monthly,
            'start_date' => Carbon::parse('2026-01-01'),
            'next_due_date' => Carbon::parse('2026-02-01'),
            'is_active' => true,
        ]);
        $rec->id = 5;
        $data = (new RecurringResource($rec))->toArray(Request::create('/'));
        $this->assertEquals(5, $data['id']);
        $this->assertEquals('expense', $data['type']);
        $this->assertEquals('monthly', $data['frequency']);
        $this->assertSame(25000.0, $data['amount']);
        $this->assertTrue($data['is_active']);
        $this->assertEquals('2026-01-01', $data['start_date']);
        $this->assertEquals('2026-02-01', $data['next_due_date']);
    }

    public function test_user_resource_serialization(): void
    {
        $user = new User([
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'phone_number' => '08123456789',
            'role' => UserRole::Admin,
        ]);
        $user->id = 1;
        $user->is_primary = true;
        $data = (new UserResource($user))->toArray(Request::create('/'));
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('johndoe', $data['username']);
        $this->assertEquals('john@example.com', $data['email']);
        $this->assertEquals('admin', $data['role']);
        $this->assertEquals('Administrator', $data['role_label']);
        $this->assertTrue($data['is_primary']);
    }

    public function test_audit_log_resource_serialization(): void
    {
        $log = new AuditLog([
            'action' => 'user_login',
            'causer_id' => 1,
            'causer_username' => 'johndoe',
            'ip_address' => '127.0.0.1',
            'method' => 'POST',
            'url' => 'http://localhost/login',
        ]);
        $log->id = 100;
        $data = (new AuditLogResource($log))->toArray(Request::create('/'));
        $this->assertEquals(100, $data['id']);
        $this->assertEquals('user_login', $data['action']);
        $this->assertEquals('johndoe', $data['causer_username']);
        $this->assertEquals('127.0.0.1', $data['ip_address']);
        $this->assertArrayHasKey('action_label', $data);
        $this->assertArrayHasKey('action_badge_class', $data);
    }
}