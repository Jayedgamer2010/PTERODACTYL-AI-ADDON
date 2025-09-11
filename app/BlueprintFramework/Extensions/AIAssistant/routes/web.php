<?php

use Illuminate\Support\Facades\Route;
use Blueprint\Extensions\AIAssistant\Controllers\ChatController;
use Blueprint\Extensions\AIAssistant\Controllers\AdminController;

Route::middleware(['web', 'auth'])->group(function () {
    // User routes
    Route::get('/ai-chat', [ChatController::class, 'show'])->name('ai.chat');
    Route::post('/ai-chat/message', [ChatController::class, 'sendMessage'])->name('ai.chat.message');
    
    // Admin routes
    Route::middleware(['admin'])->prefix('admin/ai')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.ai.index');
        Route::get('/settings', [AdminController::class, 'settings'])->name('admin.ai.settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.ai.settings.update');
        Route::get('/logs', [AdminController::class, 'logs'])->name('admin.ai.logs');
    });
});
