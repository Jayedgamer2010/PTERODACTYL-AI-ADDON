<?php

namespace Pterodactyl\Services\QueueAI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache key prefixes for different data types
     */
    const CACHE_PREFIXES = [
        'user_dashboard' => 'queueai_dashboard_user_',
        'queue_status' => 'queue_status_user_',
        'user_stats' => 'user_stats_',
        'ai_providers' => 'ai_providers_list',
        'ai_stats' => 'ai_stats',
        'recent_activity' => 'recent_activity_user_',
        'rate_limit' => 'queueai_rate_limit_',
    ];

    /**
     * Cache TTL values in seconds
     */
    const CACHE_TTL = [
        'dashboard' => 300,      // 5 minutes
        'queue_status' => 30,    // 30 seconds
        'user_stats' => 600,     // 10 minutes
        'ai_providers' => 300,   // 5 minutes
        'ai_stats' => 300,       // 5 minutes
        'recent_activity' => 120, // 2 minutes
        'rate_limit' => 3600,    // 1 hour
    ];

    /**
     * Get cached data with automatic key generation
     */
    public function get(string $type, $identifier = null, callable $callback = null, int $ttl = null): mixed
    {
        $key = $this->generateKey($type, $identifier);
        $ttl = $ttl ?? $this->getTTL($type);

        if ($callback) {
            return Cache::remember($key, $ttl, $callback);
        }

        return Cache::get($key);
    }

    /**
     * Store data in cache with automatic key generation
     */
    public function put(string $type, $identifier = null, $data = null, int $ttl = null): bool
    {
        $key = $this->generateKey($type, $identifier);
        $ttl = $ttl ?? $this->getTTL($type);

        return Cache::put($key, $data, $ttl);
    }

    /**
     * Forget cached data
     */
    public function forget(string $type, $identifier = null): bool
    {
        $key = $this->generateKey($type, $identifier);
        return Cache::forget($key);
    }

    /**
     * Clear all cache for a specific user
     */
    public function clearUserCache(int $userId): void
    {
        $userKeys = [
            'user_dashboard',
            'queue_status',
            'user_stats',
            'recent_activity'
        ];

        foreach ($userKeys as $type) {
            $this->forget($type, $userId);
        }
    }

    /**
     * Clear all QueueAI related cache
     */
    public function clearAll(): void
    {
        try {
            Cache::tags(['queueai'])->flush();
        } catch (\Exception $e) {
            // Fallback: clear individual keys
            $this->clearIndividualKeys();
        }
    }

    /**
     * Increment rate limit counter
     */
    public function incrementRateLimit(string $type, int $userId, int $ttl = 3600): int
    {
        $key = $this->generateRateLimitKey($type, $userId);
        $current = Cache::get($key, 0);
        $new = $current + 1;
        
        Cache::put($key, $new, $ttl);
        
        return $new;
    }

    /**
     * Get current rate limit count
     */
    public function getRateLimit(string $type, int $userId): int
    {
        $key = $this->generateRateLimitKey($type, $userId);
        return Cache::get($key, 0);
    }

    /**
     * Check if rate limit is exceeded
     */
    public function isRateLimitExceeded(string $type, int $userId, int $maxAttempts): bool
    {
        return $this->getRateLimit($type, $userId) >= $maxAttempts;
    }

    /**
     * Cache AI response for common questions
     */
    public function cacheAIResponse(string $message, array $response, int $ttl = 300): void
    {
        $key = 'ai_response_' . md5(strtolower(trim($message)));
        Cache::put($key, $response, $ttl);
    }

    /**
     * Get cached AI response
     */
    public function getCachedAIResponse(string $message): ?array
    {
        $key = 'ai_response_' . md5(strtolower(trim($message)));
        return Cache::get($key);
    }

    /**
     * Cache statistics with tags for easy clearing
     */
    public function cacheStats(string $type, array $stats, int $ttl = 300): void
    {
        $key = "queueai_stats_{$type}";
        Cache::tags(['queueai', 'stats'])->put($key, $stats, $ttl);
    }

    /**
     * Get cached statistics
     */
    public function getStats(string $type): ?array
    {
        $key = "queueai_stats_{$type}";
        return Cache::tags(['queueai', 'stats'])->get($key);
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUp(): void
    {
        try {
            // Warm up AI providers list
            $this->get('ai_providers', null, function () {
                return \DB::table('ai_configs')
                    ->where('is_active', true)
                    ->select('id', 'provider', 'model_name', 'max_tokens', 'cost_per_1k_tokens', 'is_default')
                    ->orderBy('is_default', 'desc')
                    ->get();
            });

            // Warm up global stats
            $this->get('ai_stats', null, function () {
                return [
                    'active_providers' => \DB::table('ai_configs')->where('is_active', true)->count(),
                    'total_conversations' => \DB::table('ai_conversations')->distinct('session_id')->count(),
                    'code_generated' => \DB::table('generated_code')->count(),
                    'monthly_cost' => \DB::table('ai_conversations')
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->sum('cost') ?? 0
                ];
            });

            Log::info('QueueAI cache warmed up successfully');

        } catch (\Exception $e) {
            Log::error('Failed to warm up QueueAI cache: ' . $e->getMessage());
        }
    }

    /**
     * Generate cache key based on type and identifier
     */
    protected function generateKey(string $type, $identifier = null): string
    {
        $prefix = self::CACHE_PREFIXES[$type] ?? "queueai_{$type}_";
        
        if ($identifier !== null) {
            return $prefix . $identifier;
        }
        
        return rtrim($prefix, '_');
    }

    /**
     * Generate rate limit key
     */
    protected function generateRateLimitKey(string $type, int $userId): string
    {
        return self::CACHE_PREFIXES['rate_limit'] . "{$type}_user_{$userId}";
    }

    /**
     * Get TTL for cache type
     */
    protected function getTTL(string $type): int
    {
        return self::CACHE_TTL[$type] ?? 300; // Default 5 minutes
    }

    /**
     * Clear individual cache keys as fallback
     */
    protected function clearIndividualKeys(): void
    {
        $patterns = [
            'queueai_dashboard_user_*',
            'queue_status_user_*',
            'user_stats_*',
            'ai_providers_list',
            'ai_stats',
            'recent_activity_user_*',
            'ai_response_*',
            'queueai_stats_*'
        ];

        foreach ($patterns as $pattern) {
            try {
                // This is a simplified approach - in production, you might want to use Redis SCAN
                Cache::forget($pattern);
            } catch (\Exception $e) {
                Log::warning("Failed to clear cache pattern {$pattern}: " . $e->getMessage());
            }
        }
    }
}