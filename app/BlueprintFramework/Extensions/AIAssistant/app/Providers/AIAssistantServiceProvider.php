<?php

namespace Blueprint\Extensions\AIAssistant\Providers;

use Illuminate\Support\ServiceProvider;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\ChatHistoryRepositoryInterface;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\MetricsRepositoryInterface;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\SettingsRepositoryInterface;
use Blueprint\Extensions\AIAssistant\Repositories\ChatHistoryRepository;
use Blueprint\Extensions\AIAssistant\Repositories\MetricsRepository;
use Blueprint\Extensions\AIAssistant\Repositories\SettingsRepository;

class AIAssistantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Repositories
        $this->app->singleton(ChatHistoryRepositoryInterface::class, ChatHistoryRepository::class);
        $this->app->singleton(MetricsRepositoryInterface::class, MetricsRepository::class);
        $this->app->singleton(SettingsRepositoryInterface::class, SettingsRepository::class);

        // Register Assets Service Provider
        $this->app->register(AssetsServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-assistant');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ai-assistant');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            ChatHistoryRepositoryInterface::class,
            MetricsRepositoryInterface::class,
            SettingsRepositoryInterface::class,
        ];
    }
}
