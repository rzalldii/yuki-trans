<?php

declare(strict_types=1);

namespace App\Jobs\Audit;

use App\Models\Audit\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAuditLogJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public array $logData,
    ) {}

    public function handle(): void
    {
        AuditLog::create($this->logData);
    }
}