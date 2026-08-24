<?php

use App\Http\Controllers\Finance\FinanceRecurringTransactionController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('finance:process-recurring', function () {
    $response = app(FinanceRecurringTransactionController::class)->generate();
    $data = $response->getData(true);
    $this->info('Processed ' . ($data['generated'] ?? 0) . ' due recurring transaction(s).');
})->purpose('Process all due recurring transactions');

Schedule::command('finance:process-recurring')->dailyAt('00:01');