<?php

namespace Blueprint\Extensions\AIAssistant\Database\Seeders;

use Illuminate\Database\Seeder;
use Blueprint\Extensions\AIAssistant\Models\AISetting;

class AIAssistantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            'openai.model' => [
                'value' => 'gpt-4',
                'description' => 'Default OpenAI model to use'
            ],
            'openai.max_tokens' => [
                'value' => '2048',
                'description' => 'Maximum tokens per request'
            ],
            'openai.temperature' => [
                'value' => '0.7',
                'description' => 'Response temperature (0-1)'
            ],
            'websocket.enabled' => [
                'value' => 'true',
                'description' => 'Enable WebSocket server'
            ],
            'websocket.port' => [
                'value' => '8080',
                'description' => 'WebSocket server port'
            ],
            'rate_limit.enabled' => [
                'value' => 'true',
                'description' => 'Enable rate limiting'
            ],
            'rate_limit.requests' => [
                'value' => '60',
                'description' => 'Maximum requests per minute'
            ],
            'cache.ttl' => [
                'value' => '3600',
                'description' => 'Cache TTL in seconds'
            ]
        ];

        foreach ($defaultSettings as $key => $setting) {
            AISetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description']
                ]
            );
        }
    }
}
