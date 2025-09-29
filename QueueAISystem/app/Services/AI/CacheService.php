<?php

namespace Pterodactyl\Http\Controllers\Admin\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache duration in seconds
     */
    const CACHE_DURATIONS = [
        'ai_response' => 300,      // 5 minutes for AI responses
        'user_permissions' => 600, // 10 minutes for user permissions
        'ai_stats' => 300,         // 5 minutes for AI statistics
        'common_responses' => 1800 // 30 minutes for very common responses
    ];

    /**
     * Get cached AI response
     */
    public static function getAIResponse(string $message): ?array
    {
        $key = self::generateResponseKey($message);
        return Cache::get($key);
    }

    /**
     * Cache AI response
     */
    public static function cacheAIResponse(string $message, array $response, bool $isCommon = false): void
    {
        $key = self::generateResponseKey($message);
        $duration = $isCommon ? self::CACHE_DURATIONS['common_responses'] : self::CACHE_DURATIONS['ai_response'];
        
        Cache::put($key, $response, $duration);
    }

    /**
     * Get cached user permissions
     */
    public static function getUserPermissions(int $userId): ?array
    {
        $key = "user_permissions_{$userId}";
        return Cache::get($key);
    }

    /**
     * Cache user permissions
     */
    public static function cacheUserPermissions(int $userId, array $permissions): void
    {
        $key = "user_permissions_{$userId}";
        Cache::put($key, $permissions, self::CACHE_DURATIONS['user_permissions']);
    }

    /**
     * Clear user-specific cache
     */
    public static function clearUserCache(int $userId): void
    {
        $patterns = [
            "user_permissions_{$userId}",
            "recent_activity_user_{$userId}",
            "queueai_dashboard_user_{$userId}",
            "ai_rate_limit_user_{$userId}"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Generate cache key for AI response
     */
    protected static function generateResponseKey(string $message): string
    {
        $normalized = strtolower(trim($message));
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return 'ai_response_' . md5($normalized);
    }

    /**
     * Warm up cache with common responses
     */
    public static function warmUpCache(): void
    {
        $commonQuestions = [
            'hello' => [
                'content' => "Hello! I'm your AI assistant for Pterodactyl Panel. How can I help you today?",
                'metadata' => ['tokens_used' => 50, 'cost' => 0.001, 'model' => 'cached', 'provider' => 'internal']
            ],
            'help' => [
                'content' => "I can help you with server management, code generation, troubleshooting, and more. What do you need assistance with?",
                'metadata' => ['tokens_used' => 60, 'cost' => 0.001, 'model' => 'cached', 'provider' => 'internal']
            ],
            'optimize server' => [
                'content' => "I can help optimize your server! Tell me your server type (Minecraft, etc.) and RAM amount for specific recommendations.",
                'metadata' => ['tokens_used' => 70, 'cost' => 0.001, 'model' => 'cached', 'provider' => 'internal']
            ],
            'backup script' => [
                'content' => "I can generate backup scripts for you! What type of server do you want to backup? (Minecraft, web server, etc.)",
                'metadata' => ['tokens_used' => 65, 'cost' => 0.001, 'model' => 'cached', 'provider' => 'internal']
            ]
        ];

        foreach ($commonQuestions as $question => $response) {
            self::cacheAIResponse($question, $response, true);
        }

        Log::info('AI Cache warmed up with common responses');
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        // This would require Redis or another cache driver that supports stats
        return [
            'hit_rate' => 'N/A',
            'total_keys' => 'N/A',
            'memory_usage' => 'N/A'
        ];
    }

    /**
     * Clear all AI-related cache
     */
    public static function clearAllAICache(): void
    {
        $patterns = [
            'ai_response_*',
            'ai_stats',
            'user_permissions_*',
            'recent_activity_user_*',
            'queueai_dashboard_user_*'
        ];

        // Note: This is a simplified version. In production, you'd want to use
        // cache tags or a more sophisticated cache clearing mechanism
        Cache::flush();
        
        Log::info('All AI cache cleared');
    }
}