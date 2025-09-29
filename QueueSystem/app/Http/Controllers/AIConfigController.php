<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Controllers\Admin\Services\AI\AIProviderService;
use Exception;

class AIConfigController extends Controller
{
    protected $aiProvider;

    public function __construct()
    {
        $this->aiProvider = new AIProviderService();
    }

    /**
     * Display AI configuration dashboard
     */
    public function index()
    {
        $aiConfigs = DB::table('ai_configs')->orderBy('provider')->get();
        
        $stats = $this->getAIStats();
        
        $recentActivity = DB::table('ai_conversations')
            ->join('users', 'ai_conversations.user_id', '=', 'users.id')
            ->select('ai_conversations.*', 'users.name as user_name')
            ->orderBy('ai_conversations.created_at', 'desc')
            ->limit(10)
            ->get();

        $chartData = $this->getChartData();

        return view('admin.ai.config', [
            'aiConfigs' => $aiConfigs,
            'activeProviders' => $stats['active_providers'],
            'codeGenerated' => $stats['code_generated'],
            'totalConversations' => $stats['total_conversations'],
            'monthlyCost' => $stats['monthly_cost'],
            'systemPrompt' => $this->getSystemPrompt(),
            'codePrompt' => $this->getCodePrompt(),
            'securitySettings' => $this->getSecuritySettings(),
            'recentActivity' => $recentActivity,
            'usageChartLabels' => $chartData['usage']['labels'],
            'usageChartData' => $chartData['usage']['data'],
            'costChartLabels' => $chartData['cost']['labels'],
            'costChartData' => $chartData['cost']['data'],
        ]);
    }

    /**
     * Add new AI provider
     */
    public function addProvider(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,claude,deepseek,gemini,groq,ollama',
            'model_name' => 'required|string|max:255',
            'model_type' => 'required|string|in:chat,code,fast',
            'api_key' => 'required|string',
            'api_endpoint' => 'nullable|url',
            'max_tokens' => 'required|integer|min:100|max:8000',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'cost_per_1k_tokens' => 'required|numeric|min:0|max:1',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        try {
            // If setting as default, unset other defaults for this model type
            if ($request->boolean('is_default')) {
                DB::table('ai_configs')
                    ->where('model_type', $request->model_type)
                    ->update(['is_default' => false]);
            }

            // Encrypt API key
            $encryptedKey = Crypt::encrypt($request->api_key);

            $configId = DB::table('ai_configs')->insertGetId([
                'provider' => $request->provider,
                'model_name' => $request->model_name,
                'model_type' => $request->model_type,
                'api_key_encrypted' => $encryptedKey,
                'api_endpoint' => $request->api_endpoint,
                'max_tokens' => $request->max_tokens,
                'rate_limit_per_minute' => $request->rate_limit_per_minute,
                'rate_limit_per_hour' => $request->rate_limit_per_minute * 60,
                'cost_per_1k_tokens' => $request->cost_per_1k_tokens,
                'is_active' => $request->boolean('is_active', true),
                'is_default' => $request->boolean('is_default', false),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Clear provider cache
            Cache::forget('ai_providers');

            return redirect()->route('admin.ai.config')
                ->with('success', 'AI provider added successfully');

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to add provider: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test AI provider connection
     */
    public function testProvider($id)
    {
        try {
            $config = DB::table('ai_configs')->where('id', $id)->first();
            
            if (!$config) {
                return response()->json(['success' => false, 'message' => 'Provider not found']);
            }

            $provider = $this->aiProvider->getProvider($config->provider);
            $provider->setConfig($config);

            $isAvailable = $provider->isAvailable();

            if ($isAvailable) {
                // Update last tested timestamp
                DB::table('ai_configs')
                    ->where('id', $id)
                    ->update(['updated_at' => now()]);

                return response()->json(['success' => true, 'message' => 'Provider is working correctly']);
            } else {
                return response()->json(['success' => false, 'message' => 'Provider is not responding']);
            }

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Test all providers
     */
    public function testAllProviders()
    {
        $configs = DB::table('ai_configs')->where('is_active', true)->get();
        $results = ['active' => 0, 'failed' => 0];

        foreach ($configs as $config) {
            try {
                $provider = $this->aiProvider->getProvider($config->provider);
                $provider->setConfig($config);

                if ($provider->isAvailable()) {
                    $results['active']++;
                } else {
                    $results['failed']++;
                    // Mark as inactive if test fails
                    DB::table('ai_configs')
                        ->where('id', $config->id)
                        ->update(['is_active' => false]);
                }
            } catch (Exception $e) {
                $results['failed']++;
                DB::table('ai_configs')
                    ->where('id', $config->id)
                    ->update(['is_active' => false]);
            }
        }

        Cache::forget('ai_providers');

        return response()->json($results);
    }

    /**
     * Delete AI provider
     */
    public function deleteProvider($id)
    {
        try {
            $deleted = DB::table('ai_configs')->where('id', $id)->delete();

            if ($deleted) {
                Cache::forget('ai_providers');
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => 'Provider not found']);
            }

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update system prompts
     */
    public function updatePrompts(Request $request)
    {
        $request->validate([
            'system_prompt' => 'required|string|max:5000',
            'code_prompt' => 'required|string|max:5000'
        ]);

        try {
            // Store prompts in cache/config
            Cache::put('ai_system_prompt', $request->system_prompt, now()->addDays(30));
            Cache::put('ai_code_prompt', $request->code_prompt, now()->addDays(30));

            // Also store in database for persistence
            DB::table('ai_configs')
                ->where('provider', 'system')
                ->updateOrInsert(
                    ['provider' => 'system'],
                    [
                        'system_prompt' => $request->system_prompt,
                        'code_generation_prompt' => $request->code_prompt,
                        'updated_at' => now()
                    ]
                );

            return redirect()->route('admin.ai.config')
                ->with('success', 'System prompts updated successfully');

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update prompts: ' . $e->getMessage());
        }
    }

    /**
     * Update security settings
     */
    public function updateSecurity(Request $request)
    {
        $request->validate([
            'require_admin_approval' => 'boolean',
            'enable_sandbox' => 'boolean',
            'audit_all_code' => 'boolean',
            'max_code_length' => 'required|integer|min:100|max:50000',
            'user_requests_per_hour' => 'required|integer|min:1|max:1000',
            'admin_requests_per_hour' => 'required|integer|min:1|max:5000',
            'max_tokens_per_request' => 'required|integer|min:100|max:8000',
            'daily_cost_limit' => 'required|numeric|min:0|max:1000'
        ]);

        try {
            $settings = [
                'require_admin_approval' => $request->boolean('require_admin_approval'),
                'enable_sandbox' => $request->boolean('enable_sandbox'),
                'audit_all_code' => $request->boolean('audit_all_code'),
                'max_code_length' => $request->max_code_length,
                'user_requests_per_hour' => $request->user_requests_per_hour,
                'admin_requests_per_hour' => $request->admin_requests_per_hour,
                'max_tokens_per_request' => $request->max_tokens_per_request,
                'daily_cost_limit' => $request->daily_cost_limit
            ];

            Cache::put('ai_security_settings', $settings, now()->addDays(30));

            return redirect()->route('admin.ai.config')
                ->with('success', 'Security settings updated successfully');

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update security settings: ' . $e->getMessage());
        }
    }

    /**
     * Get AI statistics
     */
    protected function getAIStats(): array
    {
        $activeProviders = DB::table('ai_configs')->where('is_active', true)->count();
        
        $codeGenerated = DB::table('generated_code')->count();
        
        $totalConversations = DB::table('ai_conversations')
            ->distinct('session_id')
            ->count();
        
        $monthlyCost = DB::table('ai_conversations')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost');

        return [
            'active_providers' => $activeProviders,
            'code_generated' => $codeGenerated,
            'total_conversations' => $totalConversations,
            'monthly_cost' => $monthlyCost
        ];
    }

    /**
     * Get system prompt
     */
    protected function getSystemPrompt(): string
    {
        return Cache::get('ai_system_prompt', 
            'You are a helpful AI assistant for Pterodactyl Panel. You help users manage their game servers, troubleshoot issues, and optimize configurations. Always provide safe, secure, and well-explained solutions.'
        );
    }

    /**
     * Get code generation prompt
     */
    protected function getCodePrompt(): string
    {
        return Cache::get('ai_code_prompt',
            'Generate secure, well-commented code based on the user\'s request. Include explanations and safety considerations. Follow best practices for the target language and environment.'
        );
    }

    /**
     * Get security settings
     */
    protected function getSecuritySettings(): array
    {
        return Cache::get('ai_security_settings', [
            'require_admin_approval' => true,
            'enable_sandbox' => true,
            'audit_all_code' => true,
            'max_code_length' => 10000,
            'user_requests_per_hour' => 50,
            'admin_requests_per_hour' => 500,
            'max_tokens_per_request' => 4000,
            'daily_cost_limit' => 10.00
        ]);
    }

    /**
     * Get chart data for analytics
     */
    protected function getChartData(): array
    {
        $days = collect(range(6, 0))->map(function ($daysAgo) {
            return now()->subDays($daysAgo)->format('M j');
        });

        $usageData = collect(range(6, 0))->map(function ($daysAgo) {
            return DB::table('ai_conversations')
                ->whereDate('created_at', now()->subDays($daysAgo))
                ->count();
        });

        $costData = collect(range(6, 0))->map(function ($daysAgo) {
            return DB::table('ai_conversations')
                ->whereDate('created_at', now()->subDays($daysAgo))
                ->sum('cost');
        });

        return [
            'usage' => [
                'labels' => $days->toArray(),
                'data' => $usageData->toArray()
            ],
            'cost' => [
                'labels' => $days->toArray(),
                'data' => $costData->toArray()
            ]
        ];
    }
}