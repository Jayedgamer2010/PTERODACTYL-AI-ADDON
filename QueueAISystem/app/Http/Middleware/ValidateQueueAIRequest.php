<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ValidateQueueAIRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'general'): Response
    {
        try {
            // Check authentication
            if (!Auth::check()) {
                return $this->unauthorizedResponse('Authentication required');
            }

            $user = Auth::user();

            // Check if user is suspended
            if (isset($user->suspended) && $user->suspended) {
                return $this->forbiddenResponse('Account suspended');
            }

            // Apply rate limiting based on request type
            $rateLimitKey = $this->getRateLimitKey($request, $user, $type);
            $maxAttempts = $this->getMaxAttempts($type, $user);
            $decayMinutes = $this->getDecayMinutes($type);

            if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                return $this->rateLimitResponse($seconds);
            }

            RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

            // Validate request based on type
            $validation = $this->validateRequestType($request, $type, $user);
            if (!$validation['valid']) {
                return $this->badRequestResponse($validation['message']);
            }

            // Log request for monitoring
            $this->logRequest($request, $user, $type);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('QueueAI middleware error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_type' => $type,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->serverErrorResponse('Request validation failed');
        }
    }

    /**
     * Get rate limit key for the request
     */
    protected function getRateLimitKey(Request $request, $user, string $type): string
    {
        return "queueai_{$type}_user_{$user->id}";
    }

    /**
     * Get maximum attempts based on request type and user
     */
    protected function getMaxAttempts(string $type, $user): int
    {
        $limits = [
            'general' => $user->root_admin ? 100 : 50,
            'ai_chat' => $user->root_admin ? 60 : 30,
            'code_generation' => $user->root_admin ? 20 : 10,
            'queue_action' => $user->root_admin ? 50 : 25,
            'admin_action' => $user->root_admin ? 100 : 0, // Only admins
        ];

        return $limits[$type] ?? 10;
    }

    /**
     * Get decay minutes for rate limiting
     */
    protected function getDecayMinutes(string $type): int
    {
        $decayTimes = [
            'general' => 60,        // 1 hour
            'ai_chat' => 60,        // 1 hour
            'code_generation' => 60, // 1 hour
            'queue_action' => 15,    // 15 minutes
            'admin_action' => 60,    // 1 hour
        ];

        return $decayTimes[$type] ?? 60;
    }

    /**
     * Validate request based on type
     */
    protected function validateRequestType(Request $request, string $type, $user): array
    {
        switch ($type) {
            case 'ai_chat':
                return $this->validateAIChatRequest($request, $user);
            case 'code_generation':
                return $this->validateCodeGenerationRequest($request, $user);
            case 'queue_action':
                return $this->validateQueueActionRequest($request, $user);
            case 'admin_action':
                return $this->validateAdminActionRequest($request, $user);
            default:
                return ['valid' => true, 'message' => ''];
        }
    }

    /**
     * Validate AI chat request
     */
    protected function validateAIChatRequest(Request $request, $user): array
    {
        if (!$request->has('message')) {
            return ['valid' => false, 'message' => 'Message is required'];
        }

        $message = $request->input('message');
        
        if (empty(trim($message))) {
            return ['valid' => false, 'message' => 'Message cannot be empty'];
        }

        if (strlen($message) > 2000) {
            return ['valid' => false, 'message' => 'Message too long (max 2000 characters)'];
        }

        // Check for spam patterns
        if ($this->isSpamMessage($message)) {
            return ['valid' => false, 'message' => 'Message appears to be spam'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate code generation request
     */
    protected function validateCodeGenerationRequest(Request $request, $user): array
    {
        if (!$request->has('request') || !$request->has('language')) {
            return ['valid' => false, 'message' => 'Request and language are required'];
        }

        $codeRequest = $request->input('request');
        $language = $request->input('language');

        if (strlen($codeRequest) < 5 || strlen($codeRequest) > 1000) {
            return ['valid' => false, 'message' => 'Request must be between 5 and 1000 characters'];
        }

        $allowedLanguages = ['bash', 'python', 'php', 'yaml', 'json'];
        if (!in_array($language, $allowedLanguages)) {
            return ['valid' => false, 'message' => 'Invalid language specified'];
        }

        // Check for dangerous patterns
        if ($this->containsDangerousPatterns($codeRequest)) {
            return ['valid' => false, 'message' => 'Request contains potentially unsafe patterns'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate queue action request
     */
    protected function validateQueueActionRequest(Request $request, $user): array
    {
        // Basic validation - queue actions are generally safe
        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate admin action request
     */
    protected function validateAdminActionRequest(Request $request, $user): array
    {
        if (!$user->root_admin) {
            return ['valid' => false, 'message' => 'Admin privileges required'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Check if message appears to be spam
     */
    protected function isSpamMessage(string $message): bool
    {
        $spamPatterns = [
            '/(.)\1{10,}/',           // Repeated characters
            '/[A-Z]{20,}/',           // Too many capitals
            '/https?:\/\/[^\s]+/i',   // URLs (basic check)
            '/\b(buy|sell|cheap|free|money|cash|prize|winner|congratulations)\b/i'
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for dangerous patterns in requests
     */
    protected function containsDangerousPatterns(string $request): bool
    {
        $dangerousPatterns = [
            '/rm\s+-rf/',
            '/sudo\s+/',
            '/chmod\s+777/',
            '/passwd/',
            '/shadow/',
            '/etc\/passwd/',
            '/curl.*\|.*sh/',
            '/wget.*\|.*sh/',
            '/eval\s*\(/',
            '/exec\s*\(/',
            '/system\s*\(/',
            '/shell_exec/',
            '/passthru/'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, strtolower($request))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log request for monitoring
     */
    protected function logRequest(Request $request, $user, string $type): void
    {
        Log::info('QueueAI request', [
            'user_id' => $user->id,
            'type' => $type,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);
    }

    /**
     * Response helpers
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json(['error' => $message], 401);
    }

    protected function forbiddenResponse(string $message): Response
    {
        return response()->json(['error' => $message], 403);
    }

    protected function badRequestResponse(string $message): Response
    {
        return response()->json(['error' => $message], 400);
    }

    protected function rateLimitResponse(int $seconds): Response
    {
        return response()->json([
            'error' => 'Rate limit exceeded',
            'retry_after' => $seconds
        ], 429);
    }

    protected function serverErrorResponse(string $message): Response
    {
        return response()->json(['error' => $message], 500);
    }
}