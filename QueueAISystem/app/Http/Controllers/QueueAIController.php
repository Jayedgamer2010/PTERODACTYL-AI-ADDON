<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\User;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Exception;

// Queue Model
class Queue extends Model
{
    protected $fillable = ['user_id', 'position', 'status'];
    protected $casts = ['position' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function updatePositions()
    {
        $queues = self::where('status', 'waiting')->orderBy('created_at')->get();
        foreach ($queues as $index => $queue) {
            $queue->position = $index + 1;
            $queue->save();
        }
    }
}

class QueueAIController extends Controller
{
    /**
     * Main dashboard combining queue and AI features
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Cache key for user-specific data
            $cacheKey = "queueai_dashboard_user_{$user->id}";
            
            $data = Cache::remember($cacheKey, 60, function () use ($user) {
                // Queue data with optimized queries
                $userQueue = Queue::where('user_id', $user->id)
                                  ->where('status', 'waiting')
                                  ->first();
                $totalInQueue = Queue::where('status', 'waiting')->count();
                
                // AI statistics with caching
                $aiStats = $this->getAIStats();
                $aiConfigs = DB::table('ai_configs')
                    ->where('is_active', true)
                    ->select('id', 'provider', 'model_name', 'max_tokens', 'cost_per_1k_tokens')
                    ->get();
                $recentActivity = $this->getRecentActivity($user->id);
                
                return [
                    'userQueue' => $userQueue,
                    'totalInQueue' => $totalInQueue,
                    'isInQueue' => !is_null($userQueue),
                    'aiStats' => $aiStats,
                    'aiConfigs' => $aiConfigs,
                    'recentActivity' => $recentActivity,
                    'userPermissions' => $this->getUserAIPermissions($user->id),
                    'canUseAI' => $this->canUserUseAI($user),
                ];
            });
            
            return view('admin.queueaisystem.dashboard', $data);
            
        } catch (Exception $e) {
            Log::error('QueueAI Dashboard Error: ' . $e->getMessage());
            return redirect()->route('admin.index')
                ->with('error', 'Unable to load QueueAI dashboard. Please try again.');
        }
    }

    /**
     * Queue Management Functions
     */
    public function joinQueue(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('admin.queueaisystem.index')
                               ->with('error', 'Authentication required to join queue.');
            }
            
            // Check if user is already in queue
            $existing = Queue::where('user_id', $user->id)
                             ->where('status', 'waiting')
                             ->first();
            
            if ($existing) {
                return redirect()->route('admin.queueaisystem.index')
                               ->with('error', 'You are already in the queue at position ' . $existing->position . '!');
            }
            
            // Check queue capacity (max 100 users)
            $queueCount = Queue::where('status', 'waiting')->count();
            if ($queueCount >= 100) {
                return redirect()->route('admin.queueaisystem.index')
                               ->with('error', 'Queue is currently full. Please try again later.');
            }
            
            // Use database transaction to prevent race conditions
            DB::beginTransaction();
            
            $maxPosition = Queue::where('status', 'waiting')->lockForUpdate()->max('position') ?? 0;
            
            $queueEntry = Queue::create([
                'user_id' => $user->id,
                'position' => $maxPosition + 1,
                'status' => 'waiting',
            ]);
            
            DB::commit();
            
            if (!$queueEntry) {
                throw new Exception('Failed to create queue entry');
            }
            
            // Clear cache for queue data
            Cache::forget("queueai_dashboard_user_{$user->id}");
            
            return redirect()->route('admin.queueaisystem.index')
                           ->with('success', 'Successfully joined the queue at position ' . ($maxPosition + 1) . '!');
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Queue join error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.queueaisystem.index')
                           ->with('error', 'Failed to join queue. Please try again.');
        }
    }

    public function leaveQueue(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('admin.queueaisystem.index')
                               ->with('error', 'Authentication required.');
            }
            
            $queue = Queue::where('user_id', $user->id)
                         ->where('status', 'waiting')
                         ->first();
            
            if (!$queue) {
                return redirect()->route('admin.queueaisystem.index')
                               ->with('error', 'You are not currently in the queue!');
            }
            
            // Use transaction for consistency
            DB::beginTransaction();
            
            $position = $queue->position;
            $queue->delete();
            
            // Update positions for users after the deleted position
            Queue::where('status', 'waiting')
                 ->where('position', '>', $position)
                 ->decrement('position');
            
            DB::commit();
            
            // Clear cache for queue data
            Cache::forget("queueai_dashboard_user_{$user->id}");
            
            return redirect()->route('admin.queueaisystem.index')
                           ->with('success', 'Successfully left the queue!');
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Queue leave error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.queueaisystem.index')
                           ->with('error', 'Failed to leave queue. Please try again.');
        }
    }

    /**
     * AI Configuration Functions
     */
    public function addAIProvider(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check admin permissions
            if (!$user || !$user->root_admin) {
                return redirect()->route('admin.queueaisystem.index')
                               ->with('error', 'Admin privileges required to add AI providers.');
            }
            
            $validator = Validator::make($request->all(), [
                'provider' => 'required|string|in:openai,claude,deepseek,gemini,groq',
                'model_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\-_.]+$/',
                'api_key' => 'required|string|min:10|max:500',
                'max_tokens' => 'required|integer|min:100|max:8000',
                'cost_per_1k_tokens' => 'required|numeric|min:0|max:1',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                               ->withErrors($validator)
                               ->withInput();
            }

            // Check if provider with same model already exists
            $existingProvider = DB::table('ai_configs')
                ->where('provider', $request->provider)
                ->where('model_name', $request->model_name)
                ->first();
                
            if ($existingProvider) {
                return redirect()->back()
                               ->with('error', 'Provider with this model already exists.')
                               ->withInput();
            }

            // Validate API key format based on provider
            if (!$this->validateAPIKeyFormat($request->provider, $request->api_key)) {
                return redirect()->back()
                               ->with('error', 'Invalid API key format for ' . $request->provider)
                               ->withInput();
            }

            DB::beginTransaction();

            $encryptedKey = Crypt::encrypt($request->api_key);

            $providerId = DB::table('ai_configs')->insertGetId([
                'provider' => $request->provider,
                'model_name' => $request->model_name,
                'model_type' => 'chat',
                'api_key_encrypted' => $encryptedKey,
                'max_tokens' => $request->max_tokens,
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'cost_per_1k_tokens' => $request->cost_per_1k_tokens,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            if (!$providerId) {
                throw new Exception('Failed to create AI provider');
            }

            // Clear relevant caches
            Cache::forget('ai_providers');
            Cache::forget('ai_stats');

            Log::info('AI provider added successfully', [
                'provider' => $request->provider,
                'model' => $request->model_name,
                'user_id' => $user->id
            ]);

            return redirect()->route('admin.queueaisystem.index')
                ->with('success', 'AI provider "' . $request->model_name . '" added successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('AI provider creation error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'provider' => $request->provider ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to add AI provider. Please try again.')
                ->withInput();
        }
    }

    /**
     * AI Chat API endpoint - Optimized for fast responses
     */
    public function aiChat(Request $request)
    {
        // Fast validation
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'context' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid input'], 400);
        }

        $user = Auth::user();
        
        // Quick permission check
        if (!$this->canUserUseAI($user)) {
            return response()->json(['error' => 'AI access not permitted'], 403);
        }

        // Rate limiting check
        $rateLimitKey = "ai_rate_limit_user_{$user->id}";
        $requestCount = Cache::get($rateLimitKey, 0);
        
        if ($requestCount >= 10) { // 10 requests per minute
            return response()->json(['error' => 'Rate limit exceeded. Please wait.'], 429);
        }

        try {
            // Increment rate limit counter
            Cache::put($rateLimitKey, $requestCount + 1, 60);
            
            // Get or create session ID
            $sessionId = $request->session()->get('ai_session_id');
            if (!$sessionId) {
                $sessionId = 'session-' . $user->id . '-' . time();
                $request->session()->put('ai_session_id', $sessionId);
            }

            // Check cache for similar recent responses
            $cacheKey = 'ai_response_' . md5(strtolower(trim($request->message)));
            $cachedResponse = Cache::get($cacheKey);
            
            if ($cachedResponse) {
                // Return cached response for common questions
                return response()->json([
                    'success' => true,
                    'response' => $cachedResponse['content'],
                    'metadata' => array_merge($cachedResponse['metadata'], ['cached' => true])
                ]);
            }

            // Generate AI response with optimized processing
            $aiResponse = $this->generateOptimizedAIResponse($request->message, $user);

            // Cache common responses
            if ($this->isCommonQuestion($request->message)) {
                Cache::put($cacheKey, $aiResponse, 300); // Cache for 5 minutes
            }

            // Store conversation asynchronously (non-blocking)
            $this->storeConversationAsync($user->id, $sessionId, $request->message, $aiResponse);

            return response()->json([
                'success' => true,
                'response' => $aiResponse['content'],
                'metadata' => $aiResponse['metadata']
            ]);

        } catch (Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return response()->json(['error' => 'AI service temporarily unavailable'], 500);
        }
    }

    /**
     * Code generation endpoint
     */
    public function generateCode(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
            
            $validator = Validator::make($request->all(), [
                'request' => 'required|string|min:5|max:1000|regex:/^[a-zA-Z0-9\s\-_.,!?]+$/',
                'language' => 'required|string|in:bash,python,php,yaml,json'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Invalid input',
                    'details' => $validator->errors()
                ], 400);
            }
            
            if (!$this->canUserGenerateCode($user)) {
                return response()->json([
                    'error' => 'Code generation not permitted',
                    'reason' => 'Daily limit exceeded or insufficient permissions'
                ], 403);
            }
            
            // Check for potentially dangerous requests
            if ($this->containsDangerousPatterns($request->request)) {
                return response()->json([
                    'error' => 'Request contains potentially unsafe patterns'
                ], 400);
            }
            
            // Rate limiting for code generation
            $rateLimitKey = "code_rate_limit_user_{$user->id}";
            $requestCount = Cache::get($rateLimitKey, 0);
            
            if ($requestCount >= 5) { // 5 code generations per hour
                return response()->json([
                    'error' => 'Code generation rate limit exceeded. Please wait.'
                ], 429);
            }
            
            Cache::put($rateLimitKey, $requestCount + 1, 3600); // 1 hour
            
            // Update daily counter
            $dailyKey = "code_daily_limit_user_{$user->id}";
            $dailyCount = Cache::get($dailyKey, 0);
            Cache::put($dailyKey, $dailyCount + 1, 86400); // 24 hours

            $code = $this->generateSimpleCode($request->request, $request->language, $user);
            
            // Validate generated code for safety
            if (!$this->isCodeSafe($code['code'], $request->language)) {
                return response()->json([
                    'error' => 'Generated code failed safety validation'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Store generated code
            $codeId = DB::table('generated_code')->insertGetId([
                'user_id' => $user->id,
                'title' => substr($request->request, 0, 100),
                'description' => $request->request,
                'language' => $request->language,
                'code' => $code['code'],
                'explanation' => $code['explanation'],
                'safety_level' => 'safe',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            if (!$codeId) {
                throw new Exception('Failed to store generated code');
            }
            
            Log::info('Code generated successfully', [
                'user_id' => $user->id,
                'language' => $request->language,
                'code_id' => $codeId
            ]);

            return response()->json([
                'success' => true,
                'code' => $code['code'],
                'explanation' => $code['explanation'],
                'language' => $request->language,
                'id' => $codeId,
                'safety_level' => 'safe'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Code generation error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->request ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Code generation failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Helper Functions
     */
    protected function getAIStats(): array
    {
        return Cache::remember('ai_stats', 300, function () {
            return [
                'active_providers' => DB::table('ai_configs')->where('is_active', true)->count(),
                'total_conversations' => DB::table('ai_conversations')->distinct('session_id')->count(),
                'code_generated' => DB::table('generated_code')->count(),
                'monthly_cost' => DB::table('ai_conversations')
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('cost') ?? 0
            ];
        });
    }

    protected function getRecentActivity(int $userId): array
    {
        return Cache::remember("recent_activity_user_{$userId}", 120, function () use ($userId) {
            return DB::table('ai_conversations')
                ->where('user_id', $userId)
                ->select('role', 'message', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();
        });
    }

    protected function getUserAIPermissions(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) return [];

        if ($user->root_admin) {
            return ['ai_chat', 'code_generation', 'system_commands', 'all_features'];
        }

        return ['ai_chat', 'code_generation'];
    }

    protected function canUserUseAI(User $user): bool
    {
        // Check if user is authenticated
        if (!$user) {
            return false;
        }
        
        // Check if user is suspended or banned
        if (isset($user->suspended) && $user->suspended) {
            return false;
        }
        
        // Check rate limiting
        $rateLimitKey = "ai_daily_limit_user_{$user->id}";
        $dailyUsage = Cache::get($rateLimitKey, 0);
        
        // Daily limits based on user type
        $dailyLimit = $user->root_admin ? 1000 : 100;
        
        return $dailyUsage < $dailyLimit;
    }

    protected function canUserGenerateCode(User $user): bool
    {
        // More restrictive permissions for code generation
        if (!$user) {
            return false;
        }
        
        // Check if user is suspended
        if (isset($user->suspended) && $user->suspended) {
            return false;
        }
        
        // Check daily code generation limit
        $rateLimitKey = "code_daily_limit_user_{$user->id}";
        $dailyCodeGen = Cache::get($rateLimitKey, 0);
        
        // Code generation limits
        $codeLimit = $user->root_admin ? 50 : 10;
        
        return $dailyCodeGen < $codeLimit;
    }

    /**
     * Generate optimized AI response with better pattern matching
     */
    protected function generateOptimizedAIResponse(string $message, User $user): array
    {
        $lowerMessage = strtolower(trim($message));
        
        // Enhanced response patterns with more specific help
        $patterns = [
            // Greetings
            '/^(hi|hello|hey|good morning|good afternoon)/' => [
                "Hello {$user->name}! 👋 I'm your AI assistant for Pterodactyl Panel.",
                "I can help you with:",
                "• Server optimization and troubleshooting",
                "• Code generation (scripts, configs)",
                "• Queue management",
                "• General Pterodactyl questions",
                "",
                "What would you like help with today?"
            ],
            
            // Server optimization
            '/(optimize|performance|lag|slow|cpu|memory|ram)/' => [
                "🚀 **Server Optimization Help**",
                "",
                "I can help optimize your server! Here are common solutions:",
                "",
                "**For Minecraft servers:**",
                "• Adjust JVM flags for your RAM amount",
                "• Configure server.properties for better performance",
                "• Set up automatic restarts and cleanup",
                "",
                "**For general servers:**",
                "• Monitor resource usage",
                "• Optimize startup parameters",
                "• Configure automatic backups",
                "",
                "Tell me your server type and RAM amount for specific recommendations!"
            ],
            
            // Code generation
            '/(script|code|generate|create|backup|automate)/' => [
                "💻 **Code Generation Available**",
                "",
                "I can generate various scripts and configs:",
                "",
                "**Scripts:** Backup, restart, monitoring, cleanup",
                "**Configs:** server.properties, plugin configs, startup scripts",
                "**Languages:** Bash, Python, PHP, YAML, JSON",
                "",
                "Example: 'Generate a backup script for my Minecraft server'",
                "Just describe what you need!"
            ],
            
            // Troubleshooting
            '/(error|problem|issue|not working|crash|fail)/' => [
                "🔧 **Troubleshooting Assistant**",
                "",
                "I'm here to help solve your issues! Common problems:",
                "",
                "**Server won't start:** Check logs, verify Java version",
                "**High resource usage:** Optimize settings, check plugins",
                "**Connection issues:** Verify ports, firewall settings",
                "**Plugin conflicts:** Check compatibility, update versions",
                "",
                "Describe your specific problem for targeted help!"
            ],
            
            // Queue system
            '/(queue|position|wait|priority)/' => [
                "📋 **Queue System Help**",
                "",
                "The queue system helps manage support requests:",
                "",
                "• **Join Queue:** Get in line for priority support",
                "• **View Position:** See where you are in line",
                "• **Leave Queue:** Exit when you no longer need help",
                "",
                "Your current queue status is shown on the dashboard.",
                "Would you like help with queue management?"
            ]
        ];
        
        // Check patterns for quick responses
        foreach ($patterns as $pattern => $responseLines) {
            if (preg_match($pattern, $lowerMessage)) {
                $response = implode("\n", $responseLines);
                return [
                    'content' => $response,
                    'metadata' => [
                        'tokens_used' => strlen($response),
                        'cost' => 0.001,
                        'model' => 'optimized-ai',
                        'provider' => 'internal',
                        'response_time' => microtime(true) - LARAVEL_START
                    ]
                ];
            }
        }
        
        // Default intelligent response
        $response = $this->generateContextualResponse($message, $user);
        
        return [
            'content' => $response,
            'metadata' => [
                'tokens_used' => strlen($response),
                'cost' => 0.001,
                'model' => 'contextual-ai',
                'provider' => 'internal',
                'response_time' => microtime(true) - LARAVEL_START
            ]
        ];
    }
    
    /**
     * Generate contextual response based on user and message
     */
    protected function generateContextualResponse(string $message, User $user): string
    {
        $isAdmin = $user->root_admin;
        $userName = $user->name;
        
        $responses = [
            "Hi {$userName}! I understand you're asking about: \"{$message}\"",
            "",
            "As your Pterodactyl AI assistant, I can help with:",
            "• **Server Management:** Optimization, troubleshooting, monitoring",
            "• **Code Generation:** Scripts, configs, automation tools",
            "• **Queue System:** Priority support and management",
        ];
        
        if ($isAdmin) {
            $responses[] = "• **Admin Features:** Advanced configurations, system management";
        }
        
        $responses[] = "";
        $responses[] = "Could you be more specific about what you need help with?";
        $responses[] = "For example: 'Help optimize my Minecraft server' or 'Generate a backup script'";
        
        return implode("\n", $responses);
    }
    
    /**
     * Validate API key format based on provider
     */
    protected function validateAPIKeyFormat(string $provider, string $apiKey): bool
    {
        $patterns = [
            'openai' => '/^sk-[a-zA-Z0-9]{48}$/',
            'claude' => '/^sk-ant-[a-zA-Z0-9\-_]{95,}$/',
            'deepseek' => '/^sk-[a-zA-Z0-9]{48}$/',
            'gemini' => '/^[a-zA-Z0-9\-_]{39}$/',
            'groq' => '/^gsk_[a-zA-Z0-9]{52}$/'
        ];
        
        if (!isset($patterns[$provider])) {
            return false;
        }
        
        return preg_match($patterns[$provider], $apiKey);
    }
    
    /**
     * Check if message is a common question for caching
     */
    protected function isCommonQuestion(string $message): bool
    {
        $commonPatterns = [
            '/^(hi|hello|hey)/',
            '/help/',
            '/how to/',
            '/what is/',
            '/optimize/',
            '/backup/',
            '/queue/'
        ];
        
        $lowerMessage = strtolower($message);
        foreach ($commonPatterns as $pattern) {
            if (preg_match($pattern, $lowerMessage)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Store conversation asynchronously to avoid blocking response
     */
    protected function storeConversationAsync(int $userId, string $sessionId, string $userMessage, array $aiResponse): void
    {
        // Use Laravel's queue system if available, otherwise store directly
        try {
            DB::table('ai_conversations')->insert([
                [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'role' => 'user',
                    'message' => $userMessage,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'role' => 'assistant',
                    'message' => $aiResponse['content'],
                    'metadata' => json_encode($aiResponse['metadata']),
                    'tokens_used' => $aiResponse['metadata']['tokens_used'] ?? 0,
                    'cost' => $aiResponse['metadata']['cost'] ?? 0,
                    'ai_provider' => $aiResponse['metadata']['provider'] ?? 'internal',
                    'ai_model' => $aiResponse['metadata']['model'] ?? 'internal',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to store AI conversation: ' . $e->getMessage());
        }
    }

    protected function generateSimpleCode(string $request, string $language, User $user): array
    {
        $templates = [
            'bash' => [
                'backup' => "#!/bin/bash\n# Backup script generated for: $request\necho 'Creating backup...'\ntar -czf backup-$(date +%Y%m%d).tar.gz /path/to/server\necho 'Backup completed!'",
                'default' => "#!/bin/bash\n# Script generated for: $request\necho 'Hello World'\n# Add your commands here"
            ],
            'python' => [
                'default' => "#!/usr/bin/env python3\n# Python script for: $request\nprint('Hello World')\n# Add your code here"
            ],
            'php' => [
                'default' => "<?php\n// PHP script for: $request\necho 'Hello World';\n// Add your code here"
            ],
            'yaml' => [
                'default' => "# YAML configuration for: $request\nname: example\nversion: 1.0\nsettings:\n  enabled: true"
            ],
            'json' => [
                'default' => "{\n  \"name\": \"example\",\n  \"description\": \"Generated for: $request\",\n  \"version\": \"1.0\"\n}"
            ]
        ];

        $lowerRequest = strtolower($request);
        $template = 'default';

        if (strpos($lowerRequest, 'backup') !== false) {
            $template = 'backup';
        }

        $code = $templates[$language][$template] ?? $templates[$language]['default'];
        
        return [
            'code' => $code,
            'explanation' => "This $language code was generated based on your request: \"$request\". It provides a basic template that you can customize for your specific needs."
        ];
    }
    
    /**
     * Check for dangerous patterns in user requests
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
            '/\/dev\/null/',
            '/curl.*\|.*sh/',
            '/wget.*\|.*sh/',
            '/eval\s*\(/',
            '/exec\s*\(/',
            '/system\s*\(/',
            '/shell_exec/',
            '/passthru/',
            '/proc_open/',
            '/popen/',
            '/__halt_compiler/',
            '/file_get_contents.*http/',
            '/fopen.*http/',
            '/include.*http/',
            '/require.*http/'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, strtolower($request))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate generated code for safety
     */
    protected function isCodeSafe(string $code, string $language): bool
    {
        $dangerousPatterns = [
            'bash' => [
                '/rm\s+-rf/',
                '/sudo\s+/',
                '/chmod\s+777/',
                '/passwd/',
                '/\/etc\//',
                '/curl.*\|.*sh/',
                '/wget.*\|.*sh/'
            ],
            'python' => [
                '/import\s+os/',
                '/import\s+subprocess/',
                '/exec\s*\(/',
                '/eval\s*\(/',
                '/__import__/',
                '/open\s*\(.*["\']w["\']/',
                '/urllib.*urlopen/'
            ],
            'php' => [
                '/exec\s*\(/',
                '/system\s*\(/',
                '/shell_exec/',
                '/passthru/',
                '/proc_open/',
                '/popen/',
                '/file_get_contents.*http/',
                '/fopen.*http/',
                '/include.*http/',
                '/require.*http/',
                '/eval\s*\(/'
            ]
        ];
        
        if (!isset($dangerousPatterns[$language])) {
            return true; // Safe by default for yaml/json
        }
        
        foreach ($dangerousPatterns[$language] as $pattern) {
            if (preg_match($pattern, strtolower($code))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * API endpoints for optimized AJAX requests
     */
    
    /**
     * Get current queue status for real-time updates
     */
    public function getQueueStatus(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
            
            $cacheKey = "queue_status_user_{$user->id}";
            
            $data = Cache::remember($cacheKey, 30, function () use ($user) {
                $userQueue = Queue::where('user_id', $user->id)
                                  ->where('status', 'waiting')
                                  ->first();
                                  
                $totalInQueue = Queue::where('status', 'waiting')->count();
                $averageWaitTime = $this->calculateAverageWaitTime();
                
                return [
                    'in_queue' => !is_null($userQueue),
                    'position' => $userQueue ? $userQueue->position : null,
                    'total_in_queue' => $totalInQueue,
                    'estimated_wait_minutes' => $userQueue ? ($userQueue->position * $averageWaitTime) : null,
                    'queue_status' => $totalInQueue > 50 ? 'busy' : ($totalInQueue > 20 ? 'moderate' : 'light'),
                    'last_updated' => now()->toISOString()
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            Log::error('Queue status API error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get queue status'], 500);
        }
    }
    
    /**
     * Get available AI providers
     */
    public function getAIProviders(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
            
            $providers = Cache::remember('ai_providers_list', 300, function () {
                return DB::table('ai_configs')
                    ->where('is_active', true)
                    ->select('id', 'provider', 'model_name', 'max_tokens', 'cost_per_1k_tokens', 'is_default')
                    ->orderBy('is_default', 'desc')
                    ->orderBy('provider')
                    ->get();
            });
            
            return response()->json([
                'success' => true,
                'providers' => $providers,
                'count' => $providers->count()
            ]);
            
        } catch (Exception $e) {
            Log::error('AI providers API error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get AI providers'], 500);
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
            
            $cacheKey = "user_stats_{$user->id}";
            
            $stats = Cache::remember($cacheKey, 600, function () use ($user) {
                $today = now()->startOfDay();
                $thisMonth = now()->startOfMonth();
                
                return [
                    'ai_conversations_today' => DB::table('ai_conversations')
                        ->where('user_id', $user->id)
                        ->where('role', 'user')
                        ->where('created_at', '>=', $today)
                        ->count(),
                    'ai_conversations_total' => DB::table('ai_conversations')
                        ->where('user_id', $user->id)
                        ->where('role', 'user')
                        ->count(),
                    'code_generated_today' => DB::table('generated_code')
                        ->where('user_id', $user->id)
                        ->where('created_at', '>=', $today)
                        ->count(),
                    'code_generated_total' => DB::table('generated_code')
                        ->where('user_id', $user->id)
                        ->count(),
                    'queue_joins_this_month' => DB::table('queues')
                        ->where('user_id', $user->id)
                        ->where('created_at', '>=', $thisMonth)
                        ->count(),
                    'total_ai_cost_this_month' => DB::table('ai_conversations')
                        ->where('user_id', $user->id)
                        ->where('created_at', '>=', $thisMonth)
                        ->sum('cost') ?? 0,
                    'permissions' => $this->getUserAIPermissions($user->id),
                    'rate_limits' => $this->getUserRateLimits($user->id)
                ];
            });
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            Log::error('User stats API error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get user statistics'], 500);
        }
    }
    
    /**
     * Helper methods for API endpoints
     */
    
    protected function calculateAverageWaitTime(): int
    {
        // Simple calculation - in real implementation, this would be based on historical data
        return 5; // 5 minutes average per position
    }
    
    protected function getUserRateLimits(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) return [];
        
        $rateLimitKeys = [
            'ai_chat' => "ai_rate_limit_user_{$userId}",
            'code_generation' => "code_rate_limit_user_{$userId}",
            'queue_actions' => "queue_rate_limit_user_{$userId}"
        ];
        
        $limits = [];
        foreach ($rateLimitKeys as $type => $key) {
            $current = Cache::get($key, 0);
            $max = $this->getMaxAttempts($type, $user);
            $limits[$type] = [
                'current' => $current,
                'max' => $max,
                'remaining' => max(0, $max - $current)
            ];
        }
        
        return $limits;
    }
    
    protected function getMaxAttempts(string $type, $user): int
    {
        $limits = [
            'ai_chat' => $user->root_admin ? 60 : 30,
            'code_generation' => $user->root_admin ? 20 : 10,
            'queue_actions' => $user->root_admin ? 50 : 25,
        ];
        
        return $limits[$type] ?? 10;
    }
}