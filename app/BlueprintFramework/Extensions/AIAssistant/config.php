<?php

return [
    'name' => 'AI Assistant',
    'description' => 'Transform your Pterodactyl panel with an intelligent AI-powered chat assistant',
    'version' => '1.0.0',
    'author' => 'Jayedgamer2010',
    'provides' => [
        'chat_widget',
        'admin_panel',
        'ai_services'
    ],
    'permissions' => [
        'admin.ai.settings',
        'admin.ai.logs',
        'user.ai.chat',
    ],
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'enabled' => true,
            'api_key' => env('OPENAI_API_KEY', ''),
        ],
        'anthropic' => [
            'name' => 'Claude',
            'enabled' => true,
            'api_key' => env('CLAUDE_API_KEY', ''),
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'enabled' => true,
            'api_key' => env('DEEPSEEK_API_KEY', ''),
        ],
        'groq' => [
            'name' => 'Groq',
            'enabled' => true,
            'api_key' => env('GROQ_API_KEY', ''),
        ],
    ],
    'settings' => [
        'widget_position' => 'bottom-right',
        'theme_color' => '#0066FF',
        'enable_websockets' => true,
        'enable_audit_logging' => true,
        'max_context_length' => 2000,
        'response_timeout' => 30,
    ]
];
