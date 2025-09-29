<?php

namespace Pterodactyl\Http\Controllers\Admin\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Exception;

class PermissionService
{
    /**
     * Permission levels for AI features
     */
    const PERMISSION_LEVELS = [
        'guest' => 0,
        'user' => 1,
        'moderator' => 2,
        'admin' => 3,
        'super_admin' => 4
    ];

    /**
     * AI feature permissions
     */
    const AI_PERMISSIONS = [
        'ai_chat' => 'Basic AI chat functionality',
        'code_generation' => 'Generate code snippets',
        'code_execution' => 'Execute generated code',
        'server_management' => 'AI-assisted server management',
        'system_commands' => 'Generate system-level commands',
        'database_operations' => 'Database queries and operations',
        'file_operations' => 'File system operations',
        'network_operations' => 'Network-related operations',
        'security_operations' => 'Security and permission changes',
        'dangerous_operations' => 'Potentially dangerous operations',
        'template_creation' => 'Create and share code templates',
        'template_management' => 'Manage public templates',
        'ai_configuration' => 'Configure AI providers and settings',
        'audit_access' => 'Access audit logs and analytics',
        'cost_monitoring' => 'View AI usage costs and limits'
    ];

    /**
     * Default permission mappings by user level
     */
    const DEFAULT_PERMISSIONS = [
        'guest' => [],
        'user' => [
            'ai_chat',
            'code_generation'
        ],
        'moderator' => [
            'ai_chat',
            'code_generation',
            'code_execution',
            'server_management',
            'file_operations',
            'template_creation'
        ],
        'admin' => [
            'ai_chat',
            'code_generation',
            'code_execution',
            'server_management',
            'system_commands',
            'database_operations',
            'file_operations',
            'network_operations',
            'security_operations',
            'template_creation',
            'template_management',
            'audit_access',
            'cost_monitoring'
        ],
        'super_admin' => [
            'ai_chat',
            'code_generation',
            'code_execution',
            'server_management',
            'system_commands',
            'database_operations',
            'file_operations',
            'network_operations',
            'security_operations',
            'dangerous_operations',
            'template_creation',
            'template_management',
            'ai_configuration',
            'audit_access',
            'cost_monitoring'
        ]
    ];

    /**
     * Get user's AI permissions
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = "ai_permissions_user_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $user = User::find($userId);
            if (!$user) {
                return $this->getGuestPermissions();
            }

            $userLevel = $this->getUserLevel($user);
            $basePermissions = $this->getBasePermissions($userLevel);
            $customPermissions = $this->getCustomPermissions($userId);
            
            return array_merge($basePermissions, $customPermissions);
        });
    }

    /**
     * Check if user has specific AI permission
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permission, $permissions);
    }

    /**
     * Check multiple permissions at once
     */
    public function hasPermissions(int $userId, array $permissions): array
    {
        $userPermissions = $this->getUserPermissions($userId);
        $results = [];
        
        foreach ($permissions as $permission) {
            $results[$permission] = in_array($permission, $userPermissions);
        }
        
        return $results;
    }

    /**
     * Get user's permission level
     */
    public function getUserLevel(User $user): string
    {
        if ($user->root_admin) {
            return 'super_admin';
        }

        // Check for custom admin roles (if implemented)
        if ($this->hasCustomRole($user, 'admin')) {
            return 'admin';
        }

        if ($this->hasCustomRole($user, 'moderator')) {
            return 'moderator';
        }

        return 'user';
    }

    /**
     * Get base permissions for user level
     */
    protected function getBasePermissions(string $level): array
    {
        return self::DEFAULT_PERMISSIONS[$level] ?? [];
    }

    /**
     * Get custom permissions for specific user
     */
    protected function getCustomPermissions(int $userId): array
    {
        try {
            $customPerms = DB::table('ai_user_permissions')
                ->where('user_id', $userId)
                ->where('granted', true)
                ->pluck('permission')
                ->toArray();

            return $customPerms;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get guest permissions (unauthenticated users)
     */
    protected function getGuestPermissions(): array
    {
        return [];
    }

    /**
     * Check if user has custom role
     */
    protected function hasCustomRole(User $user, string $role): bool
    {
        // This would integrate with your role system
        // For now, we'll use a simple check
        try {
            return DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role', $role)
                ->exists();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(int $userId, string $permission, int $grantedBy): bool
    {
        try {
            if (!array_key_exists($permission, self::AI_PERMISSIONS)) {
                throw new Exception("Invalid permission: {$permission}");
            }

            DB::table('ai_user_permissions')->updateOrInsert(
                ['user_id' => $userId, 'permission' => $permission],
                [
                    'granted' => true,
                    'granted_by' => $grantedBy,
                    'granted_at' => now(),
                    'updated_at' => now()
                ]
            );

            $this->clearUserPermissionCache($userId);
            $this->logPermissionChange($userId, $permission, 'granted', $grantedBy);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(int $userId, string $permission, int $revokedBy): bool
    {
        try {
            DB::table('ai_user_permissions')->updateOrInsert(
                ['user_id' => $userId, 'permission' => $permission],
                [
                    'granted' => false,
                    'revoked_by' => $revokedBy,
                    'revoked_at' => now(),
                    'updated_at' => now()
                ]
            );

            $this->clearUserPermissionCache($userId);
            $this->logPermissionChange($userId, $permission, 'revoked', $revokedBy);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get permission requirements for code generation
     */
    public function getCodeGenerationPermissions(string $codeType, string $language): array
    {
        $requirements = ['code_generation']; // Base requirement

        $typeRequirements = [
            'backup' => ['file_operations'],
            'optimization' => ['server_management'],
            'monitoring' => ['server_management'],
            'deployment' => ['server_management', 'system_commands'],
            'automation' => ['system_commands'],
            'security' => ['security_operations'],
            'database' => ['database_operations'],
            'api' => ['network_operations'],
            'system' => ['system_commands'],
            'dangerous' => ['dangerous_operations']
        ];

        $languageRequirements = [
            'bash' => ['system_commands'],
            'sql' => ['database_operations'],
            'php' => ['server_management'],
            'python' => ['system_commands'],
            'dockerfile' => ['system_commands'],
            'nginx' => ['network_operations'],
            'apache' => ['network_operations']
        ];

        if (isset($typeRequirements[$codeType])) {
            $requirements = array_merge($requirements, $typeRequirements[$codeType]);
        }

        if (isset($languageRequirements[$language])) {
            $requirements = array_merge($requirements, $languageRequirements[$language]);
        }

        return array_unique($requirements);
    }

    /**
     * Check if user can generate specific type of code
     */
    public function canGenerateCode(int $userId, string $codeType, string $language): array
    {
        $requiredPermissions = $this->getCodeGenerationPermissions($codeType, $language);
        $userPermissions = $this->getUserPermissions($userId);
        
        $hasPermissions = [];
        $missingPermissions = [];
        
        foreach ($requiredPermissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                $hasPermissions[] = $permission;
            } else {
                $missingPermissions[] = $permission;
            }
        }

        return [
            'allowed' => empty($missingPermissions),
            'has_permissions' => $hasPermissions,
            'missing_permissions' => $missingPermissions,
            'required_permissions' => $requiredPermissions
        ];
    }

    /**
     * Get server-specific permissions
     */
    public function getServerPermissions(int $userId, int $serverId): array
    {
        try {
            $server = Server::find($serverId);
            if (!$server) {
                return ['error' => 'Server not found'];
            }

            $user = User::find($userId);
            if (!$user) {
                return ['error' => 'User not found'];
            }

            // Check if user owns the server
            $isOwner = $server->owner_id === $userId;
            
            // Check if user has subuser permissions
            $subuser = DB::table('subusers')
                ->where('server_id', $serverId)
                ->where('user_id', $userId)
                ->first();

            $permissions = [];

            if ($isOwner || $user->root_admin) {
                $permissions = [
                    'server_console',
                    'server_files',
                    'server_config',
                    'server_restart',
                    'server_backup',
                    'server_database'
                ];
            } elseif ($subuser) {
                $subuserPerms = json_decode($subuser->permissions ?? '[]', true);
                $permissions = $this->mapSubuserPermissions($subuserPerms);
            }

            return [
                'server_id' => $serverId,
                'is_owner' => $isOwner,
                'is_subuser' => !is_null($subuser),
                'permissions' => $permissions
            ];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Map subuser permissions to AI permissions
     */
    protected function mapSubuserPermissions(array $subuserPerms): array
    {
        $mapping = [
            'control.console' => 'server_console',
            'file.read' => 'server_files',
            'file.write' => 'server_files',
            'startup.read' => 'server_config',
            'startup.write' => 'server_config',
            'control.restart' => 'server_restart',
            'backup.create' => 'server_backup',
            'database.read' => 'server_database',
            'database.create' => 'server_database'
        ];

        $aiPermissions = [];
        foreach ($subuserPerms as $perm) {
            if (isset($mapping[$perm])) {
                $aiPermissions[] = $mapping[$perm];
            }
        }

        return array_unique($aiPermissions);
    }

    /**
     * Get rate limits for user
     */
    public function getRateLimits(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->getGuestRateLimits();
        }

        $level = $this->getUserLevel($user);
        
        $limits = [
            'guest' => [
                'requests_per_hour' => 0,
                'tokens_per_request' => 0,
                'daily_cost_limit' => 0
            ],
            'user' => [
                'requests_per_hour' => 20,
                'tokens_per_request' => 2000,
                'daily_cost_limit' => 1.00
            ],
            'moderator' => [
                'requests_per_hour' => 50,
                'tokens_per_request' => 4000,
                'daily_cost_limit' => 5.00
            ],
            'admin' => [
                'requests_per_hour' => 200,
                'tokens_per_request' => 6000,
                'daily_cost_limit' => 20.00
            ],
            'super_admin' => [
                'requests_per_hour' => 1000,
                'tokens_per_request' => 8000,
                'daily_cost_limit' => 100.00
            ]
        ];

        return $limits[$level] ?? $limits['user'];
    }

    /**
     * Get guest rate limits
     */
    protected function getGuestRateLimits(): array
    {
        return [
            'requests_per_hour' => 0,
            'tokens_per_request' => 0,
            'daily_cost_limit' => 0
        ];
    }

    /**
     * Check if user has exceeded rate limits
     */
    public function checkRateLimit(int $userId, string $type = 'request'): array
    {
        $limits = $this->getRateLimits($userId);
        $usage = $this->getCurrentUsage($userId);

        switch ($type) {
            case 'request':
                $limit = $limits['requests_per_hour'];
                $current = $usage['requests_this_hour'];
                break;
            case 'tokens':
                $limit = $limits['tokens_per_request'];
                $current = 0; // This would be checked per request
                break;
            case 'cost':
                $limit = $limits['daily_cost_limit'];
                $current = $usage['cost_today'];
                break;
            default:
                return ['allowed' => false, 'error' => 'Invalid rate limit type'];
        }

        $allowed = $current < $limit;
        $remaining = max(0, $limit - $current);

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'current' => $current,
            'remaining' => $remaining,
            'reset_time' => $this->getResetTime($type)
        ];
    }

    /**
     * Get current usage for user
     */
    protected function getCurrentUsage(int $userId): array
    {
        $now = now();
        
        $requestsThisHour = DB::table('ai_conversations')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();

        $costToday = DB::table('ai_conversations')
            ->where('user_id', $userId)
            ->whereDate('created_at', $now->toDateString())
            ->sum('cost');

        return [
            'requests_this_hour' => $requestsThisHour,
            'cost_today' => $costToday
        ];
    }

    /**
     * Get reset time for rate limit type
     */
    protected function getResetTime(string $type): int
    {
        switch ($type) {
            case 'request':
                return now()->addHour()->timestamp;
            case 'cost':
                return now()->addDay()->startOfDay()->timestamp;
            default:
                return now()->addHour()->timestamp;
        }
    }

    /**
     * Clear user permission cache
     */
    protected function clearUserPermissionCache(int $userId): void
    {
        Cache::forget("ai_permissions_user_{$userId}");
    }

    /**
     * Log permission changes
     */
    protected function logPermissionChange(int $userId, string $permission, string $action, int $changedBy): void
    {
        try {
            DB::table('ai_permission_logs')->insert([
                'user_id' => $userId,
                'permission' => $permission,
                'action' => $action,
                'changed_by' => $changedBy,
                'created_at' => now()
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the operation
        }
    }

    /**
     * Get all available permissions with descriptions
     */
    public function getAllPermissions(): array
    {
        return self::AI_PERMISSIONS;
    }

    /**
     * Get permission levels
     */
    public function getPermissionLevels(): array
    {
        return self::PERMISSION_LEVELS;
    }

    /**
     * Get default permissions for level
     */
    public function getDefaultPermissions(string $level): array
    {
        return self::DEFAULT_PERMISSIONS[$level] ?? [];
    }

    /**
     * Validate permission name
     */
    public function isValidPermission(string $permission): bool
    {
        return array_key_exists($permission, self::AI_PERMISSIONS);
    }

    /**
     * Get permission description
     */
    public function getPermissionDescription(string $permission): string
    {
        return self::AI_PERMISSIONS[$permission] ?? 'Unknown permission';
    }

    /**
     * Bulk update user permissions
     */
    public function bulkUpdatePermissions(int $userId, array $permissions, int $updatedBy): bool
    {
        try {
            DB::beginTransaction();

            // Remove all current permissions
            DB::table('ai_user_permissions')
                ->where('user_id', $userId)
                ->delete();

            // Add new permissions
            foreach ($permissions as $permission) {
                if ($this->isValidPermission($permission)) {
                    $this->grantPermission($userId, $permission, $updatedBy);
                }
            }

            DB::commit();
            $this->clearUserPermissionCache($userId);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}