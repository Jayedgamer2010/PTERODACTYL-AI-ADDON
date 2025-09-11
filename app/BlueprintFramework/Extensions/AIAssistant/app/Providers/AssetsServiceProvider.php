<?php

namespace Blueprint\Extensions\AIAssistant\Providers;

use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/ai-assistant.php' => config_path('ai-assistant.php'),
        ], 'ai-assistant-config');

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ai-assistant'),
        ], 'ai-assistant-assets');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ai-assistant-migrations');
    }
}
