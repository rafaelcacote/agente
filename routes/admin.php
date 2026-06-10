<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {

    Route::middleware('admin.guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('tenants', TenantController::class)->except(['show']);
        Route::post('tenants/{tenant}/api-key', [TenantController::class, 'rotateApiKey'])->name('tenants.api-key');
        Route::delete('tenants/{tenant}/api-key', [TenantController::class, 'revokeApiKey'])->name('tenants.api-key.revoke');

        Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversation}/close', [ConversationController::class, 'close'])->name('conversations.close');
    });
});
