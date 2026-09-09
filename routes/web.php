<?php

use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\MasterDataController;
use App\Http\Controllers\Finance\RecurringController;
use App\Http\Controllers\Finance\TagController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\WalletController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserController;
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

    Route::resource('finance-transactions', TransactionController::class)->except(['create', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'create']);
        Route::get('/users/{user}/profile', [ProfileController::class, 'showUser'])->name('users.profile');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');
        Route::get('audit-logs/{id}/detail', [AuditLogController::class, 'detail'])->name('audit-logs.detail');

        Route::get('/finance/master-data', [MasterDataController::class, 'index'])->name('finance-master-data.index');
        Route::resource('finance-wallets', WalletController::class)->except(['create', 'show']);
        Route::resource('finance-categories', CategoryController::class)->except(['create', 'show']);
        Route::resource('finance-tags', TagController::class)->except(['create', 'show']);
        Route::resource('finance-recurring', RecurringController::class)->except(['create', 'show']);
        Route::post('finance-recurring/generate', [RecurringController::class, 'generate'])
            ->middleware('throttle:finance.action')
            ->name('finance-recurring.generate');
        Route::patch('finance-recurring/{finance_recurring}/toggle-status', [RecurringController::class, 'toggleStatus'])->name('finance-recurring.toggle-status');

        Route::post('finance-transactions/transfer', [TransactionController::class, 'storeTransfer'])->name('finance-transactions.transfer.store');
        Route::put('finance-transactions/{finance_transaction}/transfer', [TransactionController::class, 'updateTransfer'])->name('finance-transactions.transfer.update');
    });
});