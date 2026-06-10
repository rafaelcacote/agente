<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('dev.only')->group(function () {
    // Interface de teste local — bloqueada em APP_ENV=production
    Route::get('/chat/test', [ChatController::class, 'index'])->name('chat.test');

    // Demonstração do widget embeddable
    Route::get('/widget/demo', function () {
        return view('widget.demo', [
            'apiKey' => env('DEFAULT_TENANT_API_KEY', ''),
        ]);
    })->name('widget.demo');
});
