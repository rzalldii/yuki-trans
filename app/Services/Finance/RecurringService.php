<?php

namespace App\Services\Finance;

use App\Models\Audit\AuditLog;
use App\Models\Finance\Recurring;

class RecurringService
{
    public function processDueRecurrings(?int $userId = null): int
    {
        $dueRecurrings = Recurring::active()
            ->dueOn(now()->toDateString())
            ->with(['wallet', 'toWallet', 'category', 'tags'])
            ->get();
        $generated = 0;
        foreach ($dueRecurrings as $recurring) {
            try {
                $maxIterations = 366;
                $iterations = 0;
                $todayDate = now()->toDateString();
                while (
                    $recurring->is_active &&
                    $recurring->next_due_date &&
                    $recurring->next_due_date->lte($todayDate) &&
                    $iterations < $maxIterations
                ) {
                    $prevDueDate = $recurring->next_due_date->toDateString();
                    $tx = $recurring->executeTransaction($userId);
                    if ($tx) {
                        $generated++;
                    } else {
                        break;
                    }
                    $recurring->refresh();
                    if ($recurring->next_due_date && $recurring->next_due_date->toDateString() === $prevDueDate) {
                        break;
                    }
                    $iterations++;
                }
            } catch (\Throwable $e) {
                AuditLog::record('recurring_failed', null, null, [
                    'recurring_id' => $recurring->id,
                    'type' => $recurring->type,
                    'wallet' => $recurring->wallet->name ?? 'Unknown',
                    'to_wallet' => $recurring->toWallet->name ?? null,
                    'category' => $recurring->category->name ?? null,
                    'amount' => $recurring->amount,
                    'reason' => $e->getMessage(),
                ]);
            }
        }
        if ($generated > 0) {
            AuditLog::record('recurring_generated', null, null, [
                'count' => $generated,
                'date' => now()->toDateString(),
            ]);
        }
        return $generated;
    }
}