<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin\QueueAIController;
use Pterodactyl\Http\Middleware\ValidateQueueAIRequest;

// Optimized route group with proper middleware stacking
Route::group([
    'prefix' => 'admin',
    'middleware' => ['web', 'auth', 'admin'],
    'as' => 'admin.queueaisystem.'
], function () {
    Route::group(['prefix' => 'queueaisystem'], function () {
        
        // Main dashboard - cached and optimized
        Route::get('/', [QueueAIController::class, 'index'])
            ->name('index')
            ->middleware(['cache.headers:public;max_age=300;etag']);
        
        // Queue management routes with specific validation
        Route::group([
            'prefix' => 'queue',
            'as' => 'queue.',
            'middleware' => [ValidateQueueAIRequest::class . ':queue_action']
        ], function () {
            Route::post('/join', [QueueAIController::class, 'joinQueue'])
                ->name('join')
                ->middleware(['throttle:10,1']); // 10 requests per minute
                
            Route::post('/leave', [QueueAIController::class, 'leaveQueue'])
                ->name('leave')
                ->middleware(['throttle:10,1']);
        });
        
        // AI feature routes with enhanced validation and rate limiting
        Route::group([
            'prefix' => 'ai',
            'as' => 'ai.',
        ], function () {
            
            // AI Chat - high frequency, moderate rate limiting
            Route::post('/chat', [QueueAIController::class, 'aiChat'])
                ->name('chat')
                ->middleware([
                    ValidateQueueAIRequest::class . ':ai_chat',
                    'throttle:30,1' // 30 requests per minute
                ]);
            
            // Code Generation - resource intensive, strict rate limiting
            Route::post('/generate-code', [QueueAIController::class, 'generateCode'])
                ->name('generate-code')
                ->middleware([
                    ValidateQueueAIRequest::class . ':code_generation',
                    'throttle:5,1' // 5 requests per minute
                ]);
            
            // Admin-only AI provider management
            Route::post('/add-provider', [QueueAIController::class, 'addAIProvider'])
                ->name('add-provider')
                ->middleware([
                    ValidateQueueAIRequest::class . ':admin_action',
                    'throttle:10,1' // 10 requests per minute
                ]);
        });
        
        // API routes for AJAX requests with JSON responses
        Route::group([
            'prefix' => 'api',
            'as' => 'api.',
            'middleware' => ['api']
        ], function () {
            
            // Queue status endpoint for real-time updates
            Route::get('/queue/status', [QueueAIController::class, 'getQueueStatus'])
                ->name('queue.status')
                ->middleware(['throttle:60,1']); // 60 requests per minute
            
            // AI provider status
            Route::get('/ai/providers', [QueueAIController::class, 'getAIProviders'])
                ->name('ai.providers')
                ->middleware(['throttle:30,1']);
            
            // User statistics
            Route::get('/stats', [QueueAIController::class, 'getUserStats'])
                ->name('stats')
                ->middleware(['throttle:20,1']);
        });
    });
});