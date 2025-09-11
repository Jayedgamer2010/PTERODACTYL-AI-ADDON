<?php

namespace Blueprint\Extensions\AIAssistant;

use Illuminate\Support\ServiceProvider;
use Blueprint\Extensions\AIAssistant\Services\AIService;
use Blueprint\Extensions\AIAssistant\Services\WebSocketService;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\ChatHistoryRepositoryInterface;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\MetricsRepositoryInterface;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\SettingsRepositoryInterface;
use Blueprint\Extensions\AIAssistant\Repositories\ChatHistoryRepository;
use Blueprint\Extensions\AIAssistant\Repositories\MetricsRepository;
use Blueprint\Extensions\AIAssistant\Repositories\SettingsRepository;

class AIAssistantServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(AIService::class, function ($app) {
            return new AIService(config('ai-assistant.providers'));
        });

        $this->app->singleton(WebSocketService::class, function ($app) {
            return new WebSocketService();
        });
    }

    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'ai-assistant');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'ai-assistant');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Publish assets
        $this->publishes([
            __DIR__ . '/resources/assets' => public_path('vendor/ai-assistant'),
        ], 'ai-assistant-assets');

        // Publish config
        $this->publishes([
            __DIR__ . '/config.php' => config_path('ai-assistant.php'),
        ], 'ai-assistant-config');
    }
}
