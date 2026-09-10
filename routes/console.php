<?php

declare(strict_types=1);

use App\Services\Finance\RecurringExecutionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('finance:process-recurring', function (RecurringExecutionService $service) {
    $generated = $service->processDueRecurrings();
    $this->info("Processed {$generated} due recurring transaction(s).");
})->purpose('Process all due recurring transactions');

Schedule::command('finance:process-recurring')->dailyAt('00:01');