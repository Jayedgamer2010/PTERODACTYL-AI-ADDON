<?php

// Mock Laravel's global helper functions
if (!function_exists('config')) {
    function config($key = null) {
        static $config = [
            'ai-assistant' => [
                'providers' => [
                    'openai' => [
                        'enabled' => true,
                        'api_key' => 'test-key',
                        'model' => 'gpt-4-turbo-preview'
                    ],
                    'anthropic' => [
                        'enabled' => true,
                        'api_key' => 'test-key',
                        'model' => 'claude-3-opus-20240229'
                    ]
                ],
                'rate_limit' => [
                    'enabled' => true,
                    'max_requests' => 100,
                    'window' => 60
                ]
            ]
        ];
        
        if ($key === null) {
            return $config;
        }
        
        $parts = explode('.', $key);
        $value = $config;
        
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }
        
        return $value;
    }
}

if (!function_exists('app')) {
    function app($abstract = null) {
        static $container = [];
        
        if ($abstract === null) {
            return $container;
        }
        
        if (!isset($container[$abstract])) {
            $container[$abstract] = new $abstract();
        }
        
        return $container[$abstract];
    }
}

if (!function_exists('now')) {
    function now() {
        return new DateTime();
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
