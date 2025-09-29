<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin\QueueController;

Route::group([
    'prefix' => 'admin',
    'middleware' => ['web', 'auth', 'admin']
], function () {
    Route::group(['prefix' => 'queuesystem'], function () {
        Route::get('/', [QueueController::class, 'index'])->name('admin.queuesystem.index');
        Route::post('/join', [QueueController::class, 'join'])->name('admin.queuesystem.join');
        Route::post('/leave', [QueueController::class, 'leave'])->name('admin.queuesystem.leave');
    });
});