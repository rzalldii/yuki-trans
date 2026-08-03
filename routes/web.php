<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'create']);
        Route::get('/users/{user}/profile', [ProfileController::class, 'showUser'])->name('users.profile');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/data', [AuditLogController::class, 'data'])->name('audit-logs.data');
        Route::get('audit-logs/{id}/detail', [AuditLogController::class, 'detail'])->name('audit-logs.detail');
    });
});