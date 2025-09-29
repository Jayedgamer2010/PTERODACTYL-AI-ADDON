<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Console\Scheduling\Schedule;
use Pterodactyl\Http\Middleware\ValidateQueueAIRequest;

class QueueAIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register middleware
        $this->app['router']->aliasMiddleware('queueai.validate', ValidateQueueAIRequest::class);
        
        // Register services
        $this->registerQueueAIServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutes();
        
        // Load views
        $this->loadViews();
        
        // Load migrations
        $this->loadMigrations();
        
        // Register scheduled tasks
        $this->registerScheduledTasks();
        
        // Share global view data
        $this->shareViewData();
        
        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register QueueAI specific services
     */
    protected function registerQueueAIServices(): void
    {
        // Register AI service
        $this->app->singleton('queueai.ai', function ($app) {
            return new \Pterodactyl\Services\QueueAI\AIService();
        });
        
        // Register queue service
        $this->app->singleton('queueai.queue', function ($app) {
            return new \Pterodactyl\Services\QueueAI\QueueService();
        });
        
        // Register cache service
        $this->app->singleton('queueai.cache', function ($app) {
            return new \Pterodactyl\Services\QueueAI\CacheService();
        });
    }

    /**
     * Load routes for QueueAI
     */
    protected function loadRoutes(): void
    {
        if (file_exists($routes = __DIR__ . '/../../routes/web.php')) {
            Route::middleware(['web'])
                ->group($routes);
        }
    }

    /**
     * Load views for QueueAI
     */
    protected function loadViews(): void
    {
        $viewPath = __DIR__ . '/../../resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'queueai');
        }
    }

    /**
     * Load migrations for QueueAI
     */
    protected function loadMigrations(): void
    {
        $migrationPath = __DIR__ . '/../../database/migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Register scheduled tasks
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Clean up old conversations (keep last 30 days)
            $schedule->call(function () {
                \DB::table('ai_conversations')
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();
            })->daily()->at('02:00');
            
            // Clean up old generated code (keep last 7 days)
            $schedule->call(function () {
                \DB::table('generated_code')
                    ->where('created_at', '<', now()->subDays(7))
                    ->delete();
            })->daily()->at('02:30');
            
            // Update queue positions every 5 minutes
            $schedule->call(function () {
                \Pterodactyl\Http\Controllers\Admin\Queue::updatePositions();
            })->everyFiveMinutes();
            
            // Clear expired cache entries
            $schedule->call(function () {
                Cache::tags(['queueai'])->flush();
            })->hourly();
            
            // Generate daily statistics
            $schedule->call(function () {
                $this->generateDailyStats();
            })->daily()->at('23:55');
        });
    }

    /**
     * Share global view data
     */
    protected function shareViewData(): void
    {
        View::composer('admin.queueaisystem.*', function ($view) {
            $view->with([
                'queueai_version' => '1.0.0',
                'queueai_stats' => Cache::remember('queueai_global_stats', 300, function () {
                    return [
                        'total_users' => \DB::table('queues')->distinct('user_id')->count(),
                        'active_providers' => \DB::table('ai_configs')->where('is_active', true)->count(),
                        'total_conversations' => \DB::table('ai_conversations')->count(),
                        'total_code_generated' => \DB::table('generated_code')->count(),
                    ];
                })
            ]);
        });
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Listen for user login to clear cache
        \Event::listen('Illuminate\Auth\Events\Login', function ($event) {
            Cache::forget("queueai_dashboard_user_{$event->user->id}");
            Cache::forget("user_stats_{$event->user->id}");
        });
        
        // Listen for queue changes to update cache
        \Event::listen('eloquent.created: Pterodactyl\Http\Controllers\Admin\Queue', function ($queue) {
            Cache::forget("queue_status_user_{$queue->user_id}");
            Cache::tags(['queueai', 'queue'])->flush();
        });
        
        \Event::listen('eloquent.deleted: Pterodactyl\Http\Controllers\Admin\Queue', function ($queue) {
            Cache::forget("queue_status_user_{$queue->user_id}");
            Cache::tags(['queueai', 'queue'])->flush();
        });
    }

    /**
     * Generate daily statistics
     */
    protected function generateDailyStats(): void
    {
        try {
            $today = now()->startOfDay();
            $stats = [
                'date' => $today->toDateString(),
                'total_ai_requests' => \DB::table('ai_conversations')
                    ->where('created_at', '>=', $today)
                    ->where('role', 'user')
                    ->count(),
                'total_code_generated' => \DB::table('generated_code')
                    ->where('created_at', '>=', $today)
                    ->count(),
                'unique_users' => \DB::table('ai_conversations')
                    ->where('created_at', '>=', $today)
                    ->distinct('user_id')
                    ->count(),
                'queue_joins' => \DB::table('queues')
                    ->where('created_at', '>=', $today)
                    ->count(),
                'total_cost' => \DB::table('ai_conversations')
                    ->where('created_at', '>=', $today)
                    ->sum('cost') ?? 0,
                'avg_response_time' => \DB::table('ai_conversations')
                    ->where('created_at', '>=', $today)
                    ->where('role', 'assistant')
                    ->avg('response_time') ?? 0,
            ];
            
            \DB::table('queueai_daily_stats')->updateOrInsert(
                ['date' => $stats['date']],
                $stats
            );
            
        } catch (\Exception $e) {
            \Log::error('Failed to generate daily QueueAI stats: ' . $e->getMessage());
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'queueai.ai',
            'queueai.queue',
            'queueai.cache',
        ];
    }
}