<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Audit;

use App\Models\Audit\AuditLog;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_label_and_badge_class_accessors(): void
    {
        $log = new AuditLog(['action' => 'user_created']);
        $this->assertEquals('USER CREATED', $log->action_label);
        $this->assertEquals('bg-label-success', $log->action_badge_class);
        $logDeleted = new AuditLog(['action' => 'transaction_deleted']);
        $this->assertEquals('TRANSACTION DELETED', $logDeleted->action_label);
        $this->assertEquals('bg-label-danger', $logDeleted->action_badge_class);
    }

    public function test_audit_log_record_masks_sensitive_keys(): void
    {
        $user = User::factory()->create(['username' => 'audited_user']);
        $this->actingAs($user);
        $old = [
            'username' => 'old_name',
            'password' => 'secret_old_123',
        ];
        $new = [
            'username' => 'new_name',
            'password' => 'secret_new_456',
        ];
        $log = AuditLog::record('user_updated', $user, $old, $new);
        $this->assertEquals('••••••••', $log->old_values['password']);
        $this->assertEquals('••••••••', $log->new_values['password']);
        $this->assertEquals('old_name', $log->old_values['username']);
        $this->assertEquals('new_name', $log->new_values['username']);
    }

    public function test_created_event_clears_user_activity_cache(): void
    {
        $user = User::factory()->create();
        Cache::put("user_{$user->id}_activity_count", 5, 300);
        Cache::put("user_{$user->id}_audit_total", 10, 300);
        AuditLog::create([
            'causer_id' => $user->id,
            'causer_username' => $user->username,
            'action' => 'login',
        ]);
        $this->assertFalse(Cache::has("user_{$user->id}_activity_count"));
        $this->assertFalse(Cache::has("user_{$user->id}_audit_total"));
    }
}