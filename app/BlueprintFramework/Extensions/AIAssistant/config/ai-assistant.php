<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Assistant General Configuration
    |--------------------------------------------------------------------------
    */
    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    'default_provider' => env('AI_ASSISTANT_DEFAULT_PROVIDER', 'openai'),
    'cache_ttl' => env('AI_ASSISTANT_CACHE_TTL', 3600),
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => env('AI_ASSISTANT_RATE_LIMIT', 100),
        'window' => 60 // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'enabled' => true,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'streaming' => true,
            'cost_per_token' => 0.00002,
        ],
        'anthropic' => [
            'name' => 'Claude',
            'enabled' => true,
            'api_key' => env('CLAUDE_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-3-opus-20240229'),
            'max_tokens' => 4000,
            'cost_per_token' => 0.00003,
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'enabled' => true,
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-coder'),
            'cost_per_token' => 0.00001,
        ],
        'groq' => [
            'name' => 'Groq',
            'enabled' => true,
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'mixtral-8x7b-32768'),
            'cost_per_token' => 0.00001,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    */
    'websocket' => [
        'port' => env('AI_ASSISTANT_WEBSOCKET_PORT', 6001),
        'host' => env('AI_ASSISTANT_WEBSOCKET_HOST', '0.0.0.0'),
        'max_connections' => env('AI_ASSISTANT_MAX_CONNECTIONS', 1000),
        'ping_interval' => env('AI_ASSISTANT_PING_INTERVAL', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encryption_key' => env('AI_ASSISTANT_ENCRYPTION_KEY'),
        'audit_logging' => true,
        'ip_whitelist' => explode(',', env('AI_ASSISTANT_IP_WHITELIST', '')),
        'allowed_origins' => explode(',', env('AI_ASSISTANT_ALLOWED_ORIGINS', '*')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Widget Configuration
    |--------------------------------------------------------------------------
    */
    'widget' => [
        'position' => env('AI_ASSISTANT_WIDGET_POSITION', 'bottom-right'),
        'theme_color' => env('AI_ASSISTANT_THEME_COLOR', '#0066FF'),
        'show_avatar' => true,
        'show_typing_indicator' => true,
        'enable_file_upload' => true,
        'max_file_size' => 5242880, // 5MB
        'allowed_file_types' => ['txt', 'log', 'json', 'yml', 'yaml', 'conf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Configuration
    |--------------------------------------------------------------------------
    */
    'features' => [
        'server_monitoring' => true,
        'performance_analysis' => true,
        'security_scanning' => true,
        'log_analysis' => true,
        'automated_backups' => true,
        'plugin_suggestions' => true,
        'educational_content' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Handlers
    |--------------------------------------------------------------------------
    */
    'action_handlers' => [
        'restart_server' => \App\BlueprintFramework\Extensions\AIAssistant\Actions\RestartServerAction::class,
        'update_config' => \App\BlueprintFramework\Extensions\AIAssistant\Actions\UpdateConfigAction::class,
        'install_plugin' => \App\BlueprintFramework\Extensions\AIAssistant\Actions\InstallPluginAction::class,
        'backup_server' => \App\BlueprintFramework\Extensions\AIAssistant\Actions\BackupServerAction::class,
        'optimize_server' => \App\BlueprintFramework\Extensions\AIAssistant\Actions\OptimizeServerAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => true,
        'tracking_id' => env('AI_ASSISTANT_ANALYTICS_ID'),
        'track_events' => true,
        'track_users' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver' => env('AI_ASSISTANT_CACHE_DRIVER', 'redis'),
        'prefix' => 'ai_assistant:',
        'ttl' => [
            'server_metrics' => 300,    // 5 minutes
            'chat_history' => 86400,    // 24 hours
            'ai_responses' => 3600,     // 1 hour
            'security_status' => 1800,  // 30 minutes
        ],
    ],
];
