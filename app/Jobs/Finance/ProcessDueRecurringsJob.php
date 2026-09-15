<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Services\Finance\RecurringExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDueRecurringsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $userId = null,
    ) {}

    public function handle(RecurringExecutionService $service): void
    {
        $service->processDueRecurrings($this->userId);
    }
}