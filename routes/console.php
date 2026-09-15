<?php

declare(strict_types=1);

use App\Jobs\Finance\ProcessDueRecurringsJob;
use App\Services\Finance\RecurringExecutionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('finance:process-recurring {--queue : Dispatch to queue instead of processing synchronously}', function (RecurringExecutionService $service) {
    if ($this->option('queue')) {
        ProcessDueRecurringsJob::dispatch();
        $this->info('Dispatched recurring processing job to queue.');
        return 0;
    }
    $generated = $service->processDueRecurrings();
    $this->info("Processed {$generated} due recurring transaction(s).");
    return 0;
})->purpose('Process all due recurring transactions');

Schedule::job(new ProcessDueRecurringsJob())
    ->dailyAt('00:01')
    ->withoutOverlapping()
    ->onOneServer();