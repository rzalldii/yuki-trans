<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Finance\FinanceCategoryController;
use App\Http\Controllers\Finance\FinanceRecurringTransactionController;
use App\Http\Controllers\Finance\FinanceSettingController;
use App\Http\Controllers\Finance\FinanceTagController;
use App\Http\Controllers\Finance\FinanceTransactionController;
use App\Http\Controllers\Finance\FinanceWalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.dashboard');
})->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'auth.session', 'remember.expiry'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::singleton('profile', ProfileController::class)->only(['show', 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profile/audit-logs', [AuditLogController::class, 'myData'])->name('profile.audit-logs.data');
    Route::get('/profile/audit-logs/{id}/detail', [AuditLogController::class, 'detail'])->name('profile.audit-logs.detail');

    Route::resource('finance-transactions', FinanceTransactionController::class)->except(['create', 'show']);
    Route::post('finance-transactions/transfer', [FinanceTransactionController::class, 'storeTransfer'])->name('finance-transactions.transfer.store');
    Route::put('finance-transactions/{financeTransaction}/transfer', [FinanceTransactionController::class, 'updateTransfer'])->name('finance-transactions.transfer.update');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'create']);
        Route::get('/users/{user}/profile', [ProfileController::class, 'showUser'])->name('users.profile');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');
        Route::get('audit-logs/{id}/detail', [AuditLogController::class, 'detail'])->name('audit-logs.detail');

        Route::get('/finance-settings', [FinanceSettingController::class, 'index'])->name('finance-settings.index');
        Route::resource('finance-wallets', FinanceWalletController::class)->except(['create', 'show']);
        Route::resource('finance-categories', FinanceCategoryController::class)->except(['create', 'show']);
        Route::resource('finance-tags', FinanceTagController::class)->except(['create', 'show']);
        Route::resource('finance-recurring', FinanceRecurringTransactionController::class)->except(['create', 'show']);
        Route::post('finance-recurring/generate', [FinanceRecurringTransactionController::class, 'generate'])->name('finance-recurring.generate');
    });
});