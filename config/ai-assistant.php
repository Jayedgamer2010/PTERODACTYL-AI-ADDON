<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Assistant Configuration
    |--------------------------------------------------------------------------
    */

    // API Provider Settings
    'providers' => [
        'openai' => [
            'enabled' => env('AI_OPENAI_ENABLED', true),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('AI_OPENAI_MODEL', 'gpt-4-turbo-preview'),
            'max_tokens' => env('AI_OPENAI_MAX_TOKENS', 2000),
            'temperature' => env('AI_OPENAI_TEMPERATURE', 0.7),
        ],
    ],

    // WebSocket Configuration
    'websocket' => [
        'enabled' => env('AI_WEBSOCKET_ENABLED', true),
        'port' => env('AI_WEBSOCKET_PORT', 6001),
        'host' => env('AI_WEBSOCKET_HOST', '0.0.0.0'),
    ],

    // Rate Limiting
    'rate_limit' => [
        'enabled' => env('AI_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('AI_RATE_LIMIT_MAX', 60),
        'window_minutes' => env('AI_RATE_LIMIT_WINDOW', 60),
    ],

    // Cache Configuration
    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
    ],

    // Security
    'security' => [
        'allowed_commands' => [
            'start',
            'stop',
            'restart',
            'kill',
            'status',
            'list',
            'help',
        ],
        'blocked_patterns' => [
            'rm -rf',
            'sudo',
            'chmod',
            'chown',
        ],
    ],

    // Chat Widget Settings
    'chat' => [
        'max_history' => env('AI_CHAT_MAX_HISTORY', 50),
        'user_prompt_tokens' => env('AI_CHAT_USER_TOKENS', 500),
        'system_prompt_tokens' => env('AI_CHAT_SYSTEM_TOKENS', 1000),
    ],

    // Admin Settings
    'admin' => [
        'metrics_update_interval' => env('AI_METRICS_INTERVAL', 30), // seconds
        'log_retention_days' => env('AI_LOG_RETENTION', 30),
    ],
];
