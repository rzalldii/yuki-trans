<?php

declare(strict_types=1);

namespace App\Events\Audit;

use App\Models\Audit\AuditLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditLoggedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $logData,
    ) {}
}