<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Interface de teste local do chat — remover ou proteger em produção
Route::get('/chat/test', [ChatController::class, 'index'])->name('chat.test');
