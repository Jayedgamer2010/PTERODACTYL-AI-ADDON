<?php

if (!function_exists('config')) {
    function config($key = null, $default = null) {
        static $config = [
            'ai-assistant.providers.openai.enabled' => true,
            'ai-assistant.rate_limit.max_requests' => 10
        ];

        if (is_null($key)) {
            return $config;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $config[$k] = $v;
            }
            return;
        }

        return $config[$key] ?? $default;
    }
}

if (!function_exists('app')) {
    function app($abstract = null) {
        static $container = [];

        if (is_null($abstract)) {
            return $container;
        }

        if (!isset($container[$abstract])) {
            switch ($abstract) {
                case 'App\BlueprintFramework\Extensions\AIAssistant\Services\SecurityService':
                    $container[$abstract] = new App\BlueprintFramework\Extensions\AIAssistant\Services\SecurityService();
                    break;
                case 'App\BlueprintFramework\Extensions\AIAssistant\Services\CacheService':
                    $container[$abstract] = new App\BlueprintFramework\Extensions\AIAssistant\Services\CacheService();
                    break;
            }
        }

        return $container[$abstract] ?? null;
    }
}
