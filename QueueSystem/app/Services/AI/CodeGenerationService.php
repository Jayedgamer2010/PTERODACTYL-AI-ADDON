<?php

namespace Pterodactyl\Http\Controllers\Admin\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class CodeGenerationService
{
    protected $aiProvider;
    protected $safetyValidator;
    protected $templateEngine;
    protected $executionEngine;

    public function __construct()
    {
        $this->aiProvider = new AIProviderService();
        $this->safetyValidator = new CodeSafetyValidator();
        $this->templateEngine = new CodeTemplateEngine();
        $this->executionEngine = new CodeExecutionEngine();
    }

    /**
     * Generate code based on user request with comprehensive safety checks
     */
    public function generateCode(string $request, array $userContext = []): array
    {
        try {
            // Validate user permissions
            $this->validateUserPermissions($userContext);

            // Detect code type and language
            $codeType = $this->detectCodeType($request);
            $language = $this->detectLanguage($request, $codeType);

            // Build comprehensive context
            $context = $this->buildGenerationContext($userContext, $codeType, $language);

            // Check for existing templates
            $template = $this->templateEngine->findMatchingTemplate($request, $language);
            
            if ($template && $template['confidence'] > 0.8) {
                // Use template-based generation
                $result = $this->generateFromTemplate($template, $request, $context);
            } else {
                // Use AI-based generation
                $result = $this->generateWithAI($request, $context, $language);
            }

            // Comprehensive safety validation
            $safetyResult = $this->safetyValidator->validateCode(
                $result['code'],
                $language,
                $userContext
            );

            $result['safety'] = $safetyResult;

            // Store generated code
            $codeId = $this->storeGeneratedCode($result, $userContext, $request);
            $result['id'] = $codeId;

            // Log generation activity
            $this->logCodeGeneration($userContext['user_id'], $request, $result);

            return $result;

        } catch (Exception $e) {
            Log::error('Code generation failed', [
                'request' => $request,
                'user_id' => $userContext['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Code generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate code using AI provider
     */
    protected function generateWithAI(string $request, array $context, string $language): array
    {
        $prompt = $this->buildCodeGenerationPrompt($request, $context, $language);
        
        $aiResponse = $this->aiProvider->generateCode($prompt, $context);
        
        return [
            'code' => $aiResponse['code'],
            'language' => $language,
            'explanation' => $aiResponse['explanation'],
            'title' => $this->generateTitle($request),
            'description' => $this->generateDescription($request, $aiResponse['explanation']),
            'metadata' => [
                'tokens_used' => $aiResponse['tokens_used'],
                'cost' => $aiResponse['cost'],
                'model' => $aiResponse['model'],
                'provider' => $aiResponse['provider'],
                'generation_method' => 'ai'
            ]
        ];
    }

    /**
     * Generate code from template
     */
    protected function generateFromTemplate(array $template, string $request, array $context): array
    {
        $variables = $this->extractVariables($request, $template['variables']);
        $code = $this->templateEngine->renderTemplate($template['template_code'], $variables);
        
        return [
            'code' => $code,
            'language' => $template['language'],
            'explanation' => $this->templateEngine->generateExplanation($template, $variables),
            'title' => $template['name'],
            'description' => $template['description'],
            'metadata' => [
                'template_id' => $template['id'],
                'generation_method' => 'template',
                'variables' => $variables
            ]
        ];
    }

    /**
     * Build comprehensive generation context
     */
    protected function buildGenerationContext(array $userContext, string $codeType, string $language): array
    {
        return [
            'user' => [
                'id' => $userContext['user_id'],
                'is_admin' => $userContext['is_admin'] ?? false,
                'permissions' => $userContext['permissions'] ?? []
            ],
            'server' => $this->getServerContext($userContext['server_id'] ?? null),
            'environment' => $this->getEnvironmentContext(),
            'code_type' => $codeType,
            'language' => $language,
            'safety_requirements' => $this->getSafetyRequirements($userContext),
            'templates' => $this->getRelevantTemplates($codeType, $language),
            'best_practices' => $this->getBestPractices($language, $codeType)
        ];
    }

    /**
     * Build AI generation prompt
     */
    protected function buildCodeGenerationPrompt(string $request, array $context, string $language): string
    {
        $prompt = "You are an expert code generator for Pterodactyl Panel management.\n\n";
        
        $prompt .= "USER REQUEST: {$request}\n\n";
        
        $prompt .= "CONTEXT:\n";
        $prompt .= "- Language: {$language}\n";
        $prompt .= "- User Role: " . ($context['user']['is_admin'] ? 'Administrator' : 'Regular User') . "\n";
        
        if (!empty($context['server'])) {
            $prompt .= "- Server: {$context['server']['name']} ({$context['server']['memory']}MB RAM, {$context['server']['disk']}MB disk)\n";
            $prompt .= "- Server Type: {$context['server']['egg']}\n";
        }
        
        $prompt .= "\nSAFETY REQUIREMENTS:\n";
        foreach ($context['safety_requirements'] as $requirement => $allowed) {
            $status = $allowed ? 'ALLOWED' : 'FORBIDDEN';
            $prompt .= "- " . ucfirst(str_replace('_', ' ', $requirement)) . ": {$status}\n";
        }
        
        $prompt .= "\nBEST PRACTICES:\n";
        foreach ($context['best_practices'] as $practice) {
            $prompt .= "- {$practice}\n";
        }
        
        $prompt .= "\nINSTRUCTIONS:\n";
        $prompt .= "1. Generate secure, well-commented code\n";
        $prompt .= "2. Include error handling and validation\n";
        $prompt .= "3. Follow the safety requirements strictly\n";
        $prompt .= "4. Provide clear explanations\n";
        $prompt .= "5. Use best practices for the target language\n";
        $prompt .= "6. Make the code production-ready\n\n";
        
        $prompt .= "Generate the code and provide a detailed explanation of what it does and how to use it safely.";
        
        return $prompt;
    }

    /**
     * Detect code type from request
     */
    protected function detectCodeType(string $request): string
    {
        $patterns = [
            'backup' => ['/backup/', '/dump/', '/archive/'],
            'optimization' => ['/optimize/', '/performance/', '/tune/', '/jvm/', '/flags/'],
            'monitoring' => ['/monitor/', '/health/', '/status/', '/check/'],
            'deployment' => ['/deploy/', '/install/', '/setup/', '/configure/'],
            'automation' => ['/automate/', '/schedule/', '/cron/', '/script/'],
            'security' => ['/secure/', '/permission/', '/firewall/', '/ssl/'],
            'database' => ['/database/', '/mysql/', '/query/', '/migration/'],
            'api' => ['/api/', '/endpoint/', '/webhook/', '/integration/'],
            'plugin' => ['/plugin/', '/mod/', '/addon/', '/extension/'],
            'server' => ['/server/', '/minecraft/', '/game/', '/service/']
        ];

        foreach ($patterns as $type => $typePatterns) {
            foreach ($typePatterns as $pattern) {
                if (preg_match($pattern, strtolower($request))) {
                    return $type;
                }
            }
        }

        return 'general';
    }

    /**
     * Detect programming language from request
     */
    protected function detectLanguage(string $request, string $codeType): string
    {
        $languagePatterns = [
            'bash' => ['/bash/', '/shell/', '/script/', '/sh/', '/linux/', '/ubuntu/'],
            'python' => ['/python/', '/py/', '/django/', '/flask/', '/pip/'],
            'php' => ['/php/', '/laravel/', '/composer/', '/artisan/'],
            'javascript' => ['/javascript/', '/js/', '/node/', '/npm/', '/react/'],
            'yaml' => ['/yaml/', '/yml/', '/config/', '/docker-compose/'],
            'json' => ['/json/', '/api/', '/config/'],
            'sql' => ['/sql/', '/database/', '/query/', '/mysql/', '/postgres/'],
            'dockerfile' => ['/docker/', '/container/', '/dockerfile/'],
            'nginx' => ['/nginx/', '/proxy/', '/reverse proxy/'],
            'apache' => ['/apache/', '/httpd/', '/htaccess/']
        ];

        foreach ($languagePatterns as $language => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, strtolower($request))) {
                    return $language;
                }
            }
        }

        // Default based on code type
        $typeDefaults = [
            'backup' => 'bash',
            'optimization' => 'bash',
            'monitoring' => 'bash',
            'deployment' => 'bash',
            'automation' => 'bash',
            'database' => 'sql',
            'api' => 'php',
            'plugin' => 'yaml',
            'server' => 'bash'
        ];

        return $typeDefaults[$codeType] ?? 'bash';
    }

    /**
     * Get server context information
     */
    protected function getServerContext($serverId): array
    {
        if (!$serverId) return [];

        try {
            $server = DB::table('servers')->where('id', $serverId)->first();
            if (!$server) return [];

            $egg = DB::table('eggs')->where('id', $server->egg_id)->first();

            return [
                'id' => $server->id,
                'name' => $server->name,
                'memory' => $server->memory,
                'disk' => $server->disk,
                'cpu' => $server->cpu,
                'egg' => $egg->name ?? 'unknown',
                'docker_image' => $server->image,
                'startup' => $server->startup,
                'environment' => json_decode($server->environment ?? '{}', true)
            ];
        } catch (Exception $e) {
            Log::warning('Failed to get server context', ['server_id' => $serverId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get environment context
     */
    protected function getEnvironmentContext(): array
    {
        return [
            'panel_version' => config('app.version', '1.11.x'),
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'timezone' => config('app.timezone', 'UTC'),
            'environment' => config('app.env', 'production')
        ];
    }

    /**
     * Get safety requirements based on user context
     */
    protected function getSafetyRequirements(array $userContext): array
    {
        $isAdmin = $userContext['is_admin'] ?? false;

        return [
            'allow_system_commands' => $isAdmin,
            'allow_file_operations' => $isAdmin,
            'allow_network_operations' => $isAdmin,
            'allow_database_operations' => $isAdmin,
            'allow_service_management' => $isAdmin,
            'require_approval' => !$isAdmin,
            'sandbox_execution' => true,
            'audit_logging' => true
        ];
    }

    /**
     * Get relevant templates for code type and language
     */
    protected function getRelevantTemplates(string $codeType, string $language): array
    {
        try {
            return DB::table('code_templates')
                ->where('category', $codeType)
                ->where('language', $language)
                ->where('is_public', true)
                ->orderBy('usage_count', 'desc')
                ->limit(5)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get best practices for language and code type
     */
    protected function getBestPractices(string $language, string $codeType): array
    {
        $practices = [
            'bash' => [
                'Use set -euo pipefail for error handling',
                'Quote variables to prevent word splitting',
                'Use functions for reusable code blocks',
                'Add proper logging and error messages',
                'Validate input parameters'
            ],
            'python' => [
                'Use virtual environments',
                'Follow PEP 8 style guidelines',
                'Add proper exception handling',
                'Use type hints for better code clarity',
                'Include docstrings for functions'
            ],
            'php' => [
                'Use strict types declaration',
                'Follow PSR standards',
                'Implement proper error handling',
                'Use prepared statements for database queries',
                'Validate and sanitize input data'
            ],
            'sql' => [
                'Use parameterized queries',
                'Add proper indexing',
                'Include transaction handling',
                'Use meaningful table and column names',
                'Add comments for complex queries'
            ]
        ];

        return $practices[$language] ?? [
            'Follow language-specific best practices',
            'Include proper error handling',
            'Add comprehensive comments',
            'Validate input data',
            'Use secure coding practices'
        ];
    }

    /**
     * Store generated code in database
     */
    protected function storeGeneratedCode(array $result, array $userContext, string $request): int
    {
        return DB::table('generated_code')->insertGetId([
            'user_id' => $userContext['user_id'],
            'title' => $result['title'],
            'description' => $result['description'],
            'language' => $result['language'],
            'code' => $result['code'],
            'explanation' => $result['explanation'],
            'context' => json_encode($userContext),
            'safety_level' => $result['safety']['level'],
            'safety_warnings' => json_encode($result['safety']['warnings']),
            'status' => $result['safety']['level'] === 'dangerous' ? 'rejected' : 'draft',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Generate title from request
     */
    protected function generateTitle(string $request): string
    {
        $title = ucfirst(trim($request));
        if (strlen($title) > 100) {
            $title = substr($title, 0, 97) . '...';
        }
        return $title;
    }

    /**
     * Generate description from request and explanation
     */
    protected function generateDescription(string $request, string $explanation): string
    {
        $description = "Generated code for: " . $request;
        if (strlen($explanation) > 0) {
            $description .= "\n\n" . substr($explanation, 0, 500);
            if (strlen($explanation) > 500) {
                $description .= '...';
            }
        }
        return $description;
    }

    /**
     * Extract variables from request for template
     */
    protected function extractVariables(string $request, array $templateVariables): array
    {
        $variables = [];
        
        foreach ($templateVariables as $variable) {
            $name = $variable['name'];
            $pattern = $variable['pattern'] ?? null;
            $default = $variable['default'] ?? '';
            
            if ($pattern && preg_match($pattern, $request, $matches)) {
                $variables[$name] = $matches[1] ?? $default;
            } else {
                $variables[$name] = $default;
            }
        }
        
        return $variables;
    }

    /**
     * Validate user permissions for code generation
     */
    protected function validateUserPermissions(array $userContext): void
    {
        if (empty($userContext['user_id'])) {
            throw new Exception('User authentication required');
        }

        // Additional permission checks can be added here
    }

    /**
     * Log code generation activity
     */
    protected function logCodeGeneration(int $userId, string $request, array $result): void
    {
        Log::info('Code generated', [
            'user_id' => $userId,
            'request' => $request,
            'language' => $result['language'],
            'safety_level' => $result['safety']['level'],
            'code_length' => strlen($result['code']),
            'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
            'cost' => $result['metadata']['cost'] ?? 0
        ]);
    }
}

/**
 * Code Safety Validator
 * Comprehensive security analysis for generated code
 */
class CodeSafetyValidator
{
    protected $dangerousPatterns = [
        'bash' => [
            // File system operations
            '/rm\s+-rf\s+\//' => 'Dangerous recursive delete of root directory',
            '/rm\s+-rf\s+\*/' => 'Dangerous recursive delete with wildcard',
            '/dd\s+if=.*of=\/dev\//' => 'Dangerous disk write operation',
            '/mkfs\./' => 'File system formatting command',
            '/fdisk/' => 'Disk partitioning command',
            '/format\s+/' => 'Disk formatting command',
            
            // Network operations
            '/wget\s+.*\|\s*sh/' => 'Downloading and executing remote script',
            '/curl\s+.*\|\s*sh/' => 'Downloading and executing remote script',
            '/nc\s+.*-e/' => 'Netcat with command execution',
            
            // System operations
            '/sudo\s+/' => 'Privilege escalation command',
            '/su\s+/' => 'User switching command',
            '/chmod\s+777/' => 'Overly permissive file permissions',
            '/chown\s+.*root/' => 'Changing ownership to root',
            
            // Process operations
            '/kill\s+-9\s+1/' => 'Attempting to kill init process',
            '/killall\s+/' => 'Mass process termination',
            
            // System modification
            '/echo\s+.*>>\s*\/etc\//' => 'Modifying system configuration files',
            '/crontab\s+-r/' => 'Removing all cron jobs',
        ],
        
        'python' => [
            '/import\s+os/' => 'OS module import (system access)',
            '/import\s+subprocess/' => 'Subprocess module import (command execution)',
            '/exec\s*\(/' => 'Dynamic code execution',
            '/eval\s*\(/' => 'Dynamic code evaluation',
            '/__import__\s*\(/' => 'Dynamic module import',
            '/open\s*\(.*["\']w["\']/' => 'File write operation',
            '/open\s*\(.*["\']a["\']/' => 'File append operation',
            '/shutil\.rmtree/' => 'Recursive directory deletion',
            '/os\.system/' => 'System command execution',
            '/os\.remove/' => 'File deletion',
            '/os\.rmdir/' => 'Directory deletion',
        ],
        
        'php' => [
            '/exec\s*\(/' => 'Command execution function',
            '/system\s*\(/' => 'System command execution',
            '/shell_exec\s*\(/' => 'Shell command execution',
            '/passthru\s*\(/' => 'Command execution with output',
            '/eval\s*\(/' => 'Dynamic code evaluation',
            '/file_get_contents\s*\(.*http/' => 'Remote file access',
            '/file_put_contents/' => 'File write operation',
            '/unlink\s*\(/' => 'File deletion',
            '/rmdir\s*\(/' => 'Directory deletion',
            '/chmod\s*\(/' => 'File permission modification',
        ],
        
        'sql' => [
            '/DROP\s+DATABASE/' => 'Database deletion',
            '/DROP\s+TABLE/' => 'Table deletion',
            '/TRUNCATE/' => 'Table data deletion',
            '/DELETE\s+FROM\s+\w+\s*;/' => 'Unfiltered delete operation',
            '/UPDATE\s+\w+\s+SET.*WHERE\s*1\s*=\s*1/' => 'Mass update operation',
            '/GRANT\s+ALL/' => 'Granting all privileges',
            '/CREATE\s+USER/' => 'User creation',
            '/ALTER\s+USER/' => 'User modification',
        ]
    ];

    public function validateCode(string $code, string $language, array $userContext): array
    {
        $warnings = [];
        $score = 100;
        $level = 'safe';

        // Check for dangerous patterns
        if (isset($this->dangerousPatterns[$language])) {
            foreach ($this->dangerousPatterns[$language] as $pattern => $description) {
                if (preg_match($pattern, $code)) {
                    $warnings[] = $description;
                    $score -= 25;
                }
            }
        }

        // Additional security checks
        $additionalChecks = $this->performAdditionalSecurityChecks($code, $language, $userContext);
        $warnings = array_merge($warnings, $additionalChecks['warnings']);
        $score -= $additionalChecks['penalty'];

        // Determine safety level
        if ($score >= 80) {
            $level = 'safe';
        } elseif ($score >= 50) {
            $level = 'caution';
        } else {
            $level = 'dangerous';
        }

        return [
            'score' => max(0, $score),
            'level' => $level,
            'warnings' => $warnings,
            'recommendations' => $this->generateRecommendations($warnings, $language)
        ];
    }

    protected function performAdditionalSecurityChecks(string $code, string $language, array $userContext): array
    {
        $warnings = [];
        $penalty = 0;

        // Check for hardcoded credentials
        if (preg_match('/password\s*=\s*["\'][^"\']+["\']/', $code)) {
            $warnings[] = 'Hardcoded password detected';
            $penalty += 15;
        }

        // Check for API keys
        if (preg_match('/api[_-]?key\s*=\s*["\'][^"\']+["\']/', $code)) {
            $warnings[] = 'Hardcoded API key detected';
            $penalty += 15;
        }

        // Check for IP addresses (potential security risk)
        if (preg_match('/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/', $code)) {
            $warnings[] = 'Hardcoded IP address found';
            $penalty += 5;
        }

        // Check for user permission violations
        $isAdmin = $userContext['is_admin'] ?? false;
        if (!$isAdmin) {
            // Non-admin users shouldn't have system-level operations
            if (preg_match('/(sudo|su|root|admin)/', $code)) {
                $warnings[] = 'Administrative operations not allowed for regular users';
                $penalty += 30;
            }
        }

        return [
            'warnings' => $warnings,
            'penalty' => $penalty
        ];
    }

    protected function generateRecommendations(array $warnings, string $language): array
    {
        $recommendations = [];

        foreach ($warnings as $warning) {
            if (strpos($warning, 'password') !== false) {
                $recommendations[] = 'Use environment variables or secure vaults for passwords';
            }
            if (strpos($warning, 'API key') !== false) {
                $recommendations[] = 'Store API keys in environment variables or configuration files';
            }
            if (strpos($warning, 'recursive delete') !== false) {
                $recommendations[] = 'Add confirmation prompts and safety checks before deletion';
            }
            if (strpos($warning, 'command execution') !== false) {
                $recommendations[] = 'Validate and sanitize all input before command execution';
            }
        }

        // Add general recommendations based on language
        $generalRecommendations = [
            'bash' => [
                'Use set -euo pipefail for better error handling',
                'Quote all variables to prevent word splitting',
                'Validate input parameters before use'
            ],
            'python' => [
                'Use virtual environments for package isolation',
                'Implement proper exception handling',
                'Validate input data types and ranges'
            ],
            'php' => [
                'Use prepared statements for database queries',
                'Sanitize all user input',
                'Enable strict error reporting'
            ],
            'sql' => [
                'Use parameterized queries to prevent injection',
                'Add WHERE clauses to prevent mass operations',
                'Test queries on development data first'
            ]
        ];

        if (isset($generalRecommendations[$language])) {
            $recommendations = array_merge($recommendations, $generalRecommendations[$language]);
        }

        return array_unique($recommendations);
    }
}

/**
 * Code Template Engine
 * Manages code templates and variable substitution
 */
class CodeTemplateEngine
{
    public function findMatchingTemplate(string $request, string $language): ?array
    {
        // Implementation for finding matching templates
        // This would search the database for relevant templates
        return null; // Placeholder
    }

    public function renderTemplate(string $template, array $variables): string
    {
        $rendered = $template;
        
        foreach ($variables as $name => $value) {
            $rendered = str_replace("{{$name}}", $value, $rendered);
        }
        
        return $rendered;
    }

    public function generateExplanation(array $template, array $variables): string
    {
        $explanation = $template['description'];
        
        if (!empty($variables)) {
            $explanation .= "\n\nTemplate variables used:\n";
            foreach ($variables as $name => $value) {
                $explanation .= "- {$name}: {$value}\n";
            }
        }
        
        return $explanation;
    }
}

/**
 * Code Execution Engine
 * Handles safe code execution in sandboxed environments
 */
class CodeExecutionEngine
{
    public function executeInSandbox(string $code, string $language, array $context = []): array
    {
        // Implementation for sandboxed code execution
        // This would use Docker containers or other isolation mechanisms
        
        return [
            'output' => 'Sandbox execution not implemented yet',
            'error' => '',
            'exit_code' => 0,
            'execution_time' => 0
        ];
    }
}