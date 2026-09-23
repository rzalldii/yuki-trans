<?php

declare(strict_types=1);

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
    Route::get('login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.post');
});

Route::middleware(['auth', 'auth.session', 'remember.expiry'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('profile', [ProfileController::class, 'show'])
        ->name('profile.show');
    Route::middleware('throttle:user.action')->group(function () {
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password');
    });

    Route::get('audit-logs/my-data', [AuditLogController::class, 'myData'])
        ->middleware('throttle:60,1')
        ->name('audit-logs.my-data');
    Route::get('audit-logs/{audit_log}/detail', [AuditLogController::class, 'detail'])
        ->name('audit-logs.detail');

    Route::get('finance-transactions', [TransactionController::class, 'index'])
        ->name('finance-transactions.index');
    Route::get('finance-transactions/{finance_transaction}/edit', [TransactionController::class, 'edit'])
        ->name('finance-transactions.edit');
    Route::middleware('throttle:finance.action')->group(function () {
        Route::post('finance-transactions', [TransactionController::class, 'store'])
            ->name('finance-transactions.store');
        Route::put('finance-transactions/{finance_transaction}', [TransactionController::class, 'update'])
            ->name('finance-transactions.update');
        Route::delete('finance-transactions/{finance_transaction}', [TransactionController::class, 'destroy'])
            ->name('finance-transactions.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->name('users.show');
        Route::get('users/{user}/profile', [UserController::class, 'show'])
            ->name('users.profile');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit');
        Route::middleware('throttle:user.action')->group(function () {
            Route::post('users', [UserController::class, 'store'])
                ->name('users.store');
            Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])
                ->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])
                ->name('users.destroy');
        });

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
        Route::get('audit-logs/data', [AuditLogController::class, 'data'])
            ->middleware('throttle:60,1')
            ->name('audit-logs.data');

        Route::get('finance-master-data', [MasterDataController::class, 'index'])
            ->name('finance-master-data.index');

        Route::get('finance-wallets', [WalletController::class, 'index'])
            ->name('finance-wallets.index');
        Route::get('finance-wallets/{finance_wallet}/edit', [WalletController::class, 'edit'])
            ->name('finance-wallets.edit');
        Route::middleware('throttle:finance.action')->group(function () {
            Route::post('finance-wallets', [WalletController::class, 'store'])
                ->name('finance-wallets.store');
            Route::match(['put', 'patch'], 'finance-wallets/{finance_wallet}', [WalletController::class, 'update'])
                ->name('finance-wallets.update');
            Route::delete('finance-wallets/{finance_wallet}', [WalletController::class, 'destroy'])
                ->name('finance-wallets.destroy');
        });

        Route::get('finance-categories', [CategoryController::class, 'index'])
            ->name('finance-categories.index');
        Route::get('finance-categories/{finance_category}/edit', [CategoryController::class, 'edit'])
            ->name('finance-categories.edit');
        Route::middleware('throttle:finance.action')->group(function () {
            Route::post('finance-categories', [CategoryController::class, 'store'])
                ->name('finance-categories.store');
            Route::match(['put', 'patch'], 'finance-categories/{finance_category}', [CategoryController::class, 'update'])
                ->name('finance-categories.update');
            Route::delete('finance-categories/{finance_category}', [CategoryController::class, 'destroy'])
                ->name('finance-categories.destroy');
        });

        Route::get('finance-tags', [TagController::class, 'index'])
            ->name('finance-tags.index');
        Route::get('finance-tags/{finance_tag}/edit', [TagController::class, 'edit'])
            ->name('finance-tags.edit');
        Route::middleware('throttle:finance.action')->group(function () {
            Route::post('finance-tags', [TagController::class, 'store'])
                ->name('finance-tags.store');
            Route::match(['put', 'patch'], 'finance-tags/{finance_tag}', [TagController::class, 'update'])
                ->name('finance-tags.update');
            Route::delete('finance-tags/{finance_tag}', [TagController::class, 'destroy'])
                ->name('finance-tags.destroy');
        });

        Route::get('finance-recurrings', [RecurringController::class, 'index'])
            ->name('finance-recurrings.index');
        Route::get('finance-recurrings/{finance_recurring}/edit', [RecurringController::class, 'edit'])
            ->name('finance-recurrings.edit');
        Route::middleware('throttle:finance.action')->group(function () {
            Route::post('finance-recurrings', [RecurringController::class, 'store'])
                ->name('finance-recurrings.store');
            Route::match(['put', 'patch'], 'finance-recurrings/{finance_recurring}', [RecurringController::class, 'update'])
                ->name('finance-recurrings.update');
            Route::delete('finance-recurrings/{finance_recurring}', [RecurringController::class, 'destroy'])
                ->name('finance-recurrings.destroy');
            Route::patch('finance-recurrings/{finance_recurring}/toggle-status', [RecurringController::class, 'toggleStatus'])
                ->name('finance-recurrings.toggle-status');
            Route::post('finance-recurrings/generate', [RecurringController::class, 'generate'])
                ->name('finance-recurrings.generate');
            Route::post('finance-transactions/transfer', [TransactionController::class, 'storeTransfer'])
                ->name('finance-transactions.transfer.store');
            Route::put('finance-transactions/{finance_transaction}/transfer', [TransactionController::class, 'updateTransfer'])
                ->name('finance-transactions.transfer.update');
        });
    });
});