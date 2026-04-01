<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Chat Agent
|--------------------------------------------------------------------------
| Prefixo automático: /api  (configurado em bootstrap/app.php)
| Middleware padrão: throttle:api, substitutebindings
|
| EXTENSÃO FUTURA: adicionar autenticação via Sanctum ou API Key
|   Route::middleware('auth:sanctum')->group(function () { ... });
*/

Route::prefix('chat')->name('api.chat.')->group(function () {

    // Envia mensagem e recebe resposta da IA
    Route::post('/message', [ChatController::class, 'send'])->name('send');

    // Recupera histórico de uma conversa pelo UUID público
    Route::get('/conversation/{uuid}/history', [ChatController::class, 'history'])
        ->name('history')
        ->whereUuid('uuid');
});
