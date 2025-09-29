<?php

namespace Pterodactyl\Http\Controllers\Admin\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AIProviderService
{
    protected $providers = [
        'openai' => OpenAIProvider::class,
        'claude' => ClaudeProvider::class,
        'deepseek' => DeepSeekProvider::class,
        'gemini' => GeminiProvider::class,
        'groq' => GroqProvider::class,
        'ollama' => OllamaProvider::class,
    ];

    public function getProvider(string $providerName): AIProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new Exception("Unsupported AI provider: {$providerName}");
        }

        $providerClass = $this->providers[$providerName];
        return new $providerClass();
    }

    public function getBestProvider(string $type = 'chat', array $requirements = []): AIProviderInterface
    {
        // Get active providers from database
        $configs = AIConfig::where('is_active', true)
            ->where('model_type', $type)
            ->orderBy('is_default', 'desc')
            ->orderBy('cost_per_1k_tokens', 'asc')
            ->get();

        foreach ($configs as $config) {
            try {
                $provider = $this->getProvider($config->provider);
                $provider->setConfig($config);
                
                // Check if provider is available
                if ($provider->isAvailable()) {
                    return $provider;
                }
            } catch (Exception $e) {
                Log::warning("Provider {$config->provider} unavailable: " . $e->getMessage());
                continue;
            }
        }

        throw new Exception('No available AI providers found');
    }

    public function generateResponse(string $prompt, array $context = [], string $type = 'chat'): array
    {
        $provider = $this->getBestProvider($type, $context);
        
        $cacheKey = 'ai_response_' . md5($prompt . serialize($context));
        
        return Cache::remember($cacheKey, 300, function () use ($provider, $prompt, $context) {
            return $provider->generateResponse($prompt, $context);
        });
    }

    public function generateCode(string $request, array $userContext = []): array
    {
        $provider = $this->getBestProvider('code');
        
        // Build comprehensive context
        $context = array_merge($userContext, [
            'user_permissions' => $this->getUserPermissions($userContext['user_id'] ?? null),
            'server_info' => $this->getServerContext($userContext['server_id'] ?? null),
            'safety_requirements' => $this->getSafetyRequirements($userContext['user_id'] ?? null),
        ]);

        $response = $provider->generateCode($request, $context);
        
        // Validate and score the generated code
        $response['safety_score'] = $this->validateCodeSafety($response['code'], $response['language']);
        
        return $response;
    }

    protected function getUserPermissions($userId): array
    {
        if (!$userId) return ['role' => 'guest'];
        
        $user = User::find($userId);
        return [
            'role' => $user->root_admin ? 'admin' : 'user',
            'is_admin' => $user->root_admin,
            'permissions' => $user->permissions ?? [],
        ];
    }

    protected function getServerContext($serverId): array
    {
        if (!$serverId) return [];
        
        $server = Server::find($serverId);
        if (!$server) return [];
        
        return [
            'id' => $server->id,
            'name' => $server->name,
            'memory' => $server->memory,
            'disk' => $server->disk,
            'cpu' => $server->cpu,
            'egg' => $server->egg->name ?? 'unknown',
            'docker_image' => $server->image,
        ];
    }

    protected function getSafetyRequirements($userId): array
    {
        $user = User::find($userId);
        
        return [
            'allow_system_commands' => $user && $user->root_admin,
            'allow_file_operations' => $user && $user->root_admin,
            'allow_network_operations' => $user && $user->root_admin,
            'require_approval' => !($user && $user->root_admin),
        ];
    }

    protected function validateCodeSafety(string $code, string $language): array
    {
        $dangerousPatterns = [
            'bash' => [
                '/rm\s+-rf\s+\//',
                '/sudo\s+/',
                '/chmod\s+777/',
                '/wget\s+.*\|\s*sh/',
                '/curl\s+.*\|\s*sh/',
                '/dd\s+if=/',
                '/mkfs\./',
                '/fdisk/',
                '/format\s+/',
            ],
            'python' => [
                '/import\s+os/',
                '/subprocess\./',
                '/exec\s*\(/',
                '/eval\s*\(/',
                '/open\s*\(.*["\']w["\']/',
                '/__import__/',
            ],
            'php' => [
                '/exec\s*\(/',
                '/system\s*\(/',
                '/shell_exec\s*\(/',
                '/passthru\s*\(/',
                '/eval\s*\(/',
                '/file_get_contents\s*\(.*http/',
            ],
        ];

        $warnings = [];
        $score = 100;

        if (isset($dangerousPatterns[$language])) {
            foreach ($dangerousPatterns[$language] as $pattern) {
                if (preg_match($pattern, $code)) {
                    $warnings[] = "Potentially dangerous pattern detected: " . $pattern;
                    $score -= 20;
                }
            }
        }

        return [
            'score' => max(0, $score),
            'level' => $score >= 80 ? 'safe' : ($score >= 50 ? 'caution' : 'dangerous'),
            'warnings' => $warnings,
        ];
    }
}

interface AIProviderInterface
{
    public function setConfig($config): void;
    public function isAvailable(): bool;
    public function generateResponse(string $prompt, array $context = []): array;
    public function generateCode(string $request, array $context = []): array;
}

class OpenAIProvider implements AIProviderInterface
{
    protected $config;

    public function setConfig($config): void
    {
        $this->config = $config;
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($this->config->api_key_encrypted),
                'Content-Type' => 'application/json',
            ])->timeout(5)->get('https://api.openai.com/v1/models');

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    public function generateResponse(string $prompt, array $context = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . decrypt($this->config->api_key_encrypted),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->config->model_name,
            'messages' => [
                ['role' => 'system', 'content' => $this->config->system_prompt ?? 'You are a helpful AI assistant for Pterodactyl Panel.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $this->config->max_tokens,
            'temperature' => $this->config->model_config['temperature'] ?? 0.7,
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }

        $data = $response->json();
        
        return [
            'content' => $data['choices'][0]['message']['content'],
            'tokens_used' => $data['usage']['total_tokens'],
            'cost' => ($data['usage']['total_tokens'] / 1000) * $this->config->cost_per_1k_tokens,
            'model' => $this->config->model_name,
            'provider' => 'openai',
        ];
    }

    public function generateCode(string $request, array $context = []): array
    {
        $codePrompt = $this->config->code_generation_prompt ?? 
            "Generate secure, well-commented code based on the user's request. Include explanations and safety considerations.";
        
        $fullPrompt = $codePrompt . "\n\nUser Request: " . $request;
        
        if (!empty($context['server_info'])) {
            $fullPrompt .= "\n\nServer Context: " . json_encode($context['server_info']);
        }
        
        if (!empty($context['safety_requirements'])) {
            $fullPrompt .= "\n\nSafety Requirements: " . json_encode($context['safety_requirements']);
        }

        $response = $this->generateResponse($fullPrompt, $context);
        
        // Parse code blocks from response
        preg_match('/```(\w+)?\n(.*?)\n```/s', $response['content'], $matches);
        
        return [
            'code' => $matches[2] ?? $response['content'],
            'language' => $matches[1] ?? 'text',
            'explanation' => $response['content'],
            'tokens_used' => $response['tokens_used'],
            'cost' => $response['cost'],
            'model' => $response['model'],
            'provider' => $response['provider'],
        ];
    }
}

// Additional provider classes would be implemented similarly
class ClaudeProvider implements AIProviderInterface
{
    protected $config;

    public function setConfig($config): void { $this->config = $config; }
    public function isAvailable(): bool { return true; } // Implement actual check
    public function generateResponse(string $prompt, array $context = []): array { return []; } // Implement
    public function generateCode(string $request, array $context = []): array { return []; } // Implement
}

class DeepSeekProvider implements AIProviderInterface
{
    protected $config;

    public function setConfig($config): void { $this->config = $config; }
    public function isAvailable(): bool { return true; } // Implement actual check
    public function generateResponse(string $prompt, array $context = []): array { return []; } // Implement
    public function generateCode(string $request, array $context = []): array { return []; } // Implement
}

class GeminiProvider implements AIProviderInterface
{
    protected $config;

    public function setConfig($config): void { $this->config = $config; }
    public function isAvailable(): bool { return true; } // Implement actual check
    public function generateResponse(string $prompt, array $context = []): array { return []; } // Implement
    public function generateCode(string $request, array $context = []): array { return []; } // Implement
}

class GroqProvider implements AIProviderInterface
{
    protected $config;

    public function setConfig($config): void { $this->config = $config; }
    public function isAvailable(): bool { return true; } // Implement actual check
    public function generateResponse(string $prompt, array $context = []): array { return []; } // Implement
    public function generateCode(string $request, array $context = []): array { return []; } // Implement
}

class OllamaProvider implements AIProviderInterface
{
    protected $config;

    public function setConfig($config): void { $this->config = $config; }
    public function isAvailable(): bool { return true; } // Implement actual check
    public function generateResponse(string $prompt, array $context = []): array { return []; } // Implement
    public function generateCode(string $request, array $context = []): array { return []; } // Implement
}