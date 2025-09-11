<?php

use Illuminate\Support\Facades\Route;
use Blueprint\Extensions\AIAssistant\Controllers\Api\ChatController;

Route::middleware(['api', 'auth:api'])->prefix('api')->group(function () {
    Route::post('/ai/chat', [ChatController::class, 'message']);
    Route::get('/ai/history', [ChatController::class, 'history']);
});
