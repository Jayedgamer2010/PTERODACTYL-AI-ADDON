<?php

namespace Blueprint\Extensions\AIAssistant\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogAnalysisService
{
    protected $logPatterns = [
        'error' => '/\b(error|exception|failed|fatal)\b/i',
        'warning' => '/\b(warning|warn|deprecated)\b/i',
        'security' => '/\b(access denied|unauthorized|forbidden|invalid token)\b/i'
    ];

    public function getRelevantLogs($serverId, $context = [])
    {
        $logs = $this->fetchRecentLogs($serverId);
        $analyzed = $this->analyzeLogs($logs);
        
        return $this->prioritizeLogs($analyzed, $context);
    }

    public function analyzeSecurityIncidents($serverId)
    {
        $logs = $this->fetchRecentLogs($serverId);
        return $this->detectSecurityIssues($logs);
    }

    public function getPerformanceIssues($serverId)
    {
        $logs = $this->fetchRecentLogs($serverId);
        return $this->detectPerformanceIssues($logs);
    }

    protected function fetchRecentLogs($serverId)
    {
        $logPath = "servers/{$serverId}/logs";
        $files = Storage::files($logPath);
        $logs = [];

        foreach ($files as $file) {
            if (Storage::size($file) > 10 * 1024 * 1024) { // Skip files larger than 10MB
                continue;
            }
            
            $content = Storage::get($file);
            $logs[$file] = $this->parseLogFile($content);
        }

        return $logs;
    }

    protected function analyzeLogs($logs)
    {
        $analysis = [
            'errors' => [],
            'warnings' => [],
            'security' => [],
            'performance' => []
        ];

        foreach ($logs as $file => $entries) {
            foreach ($entries as $entry) {
                $this->categorizeLogEntry($entry, $analysis);
            }
        }

        return $analysis;
    }

    protected function detectSecurityIssues($logs)
    {
        $securityIssues = [];
        $patterns = [
            'bruteforce' => '/Failed login attempt|Invalid password/i',
            'injection' => '/SQL injection|XSS attempt/i',
            'unauthorized' => '/Unauthorized access|Permission denied/i'
        ];

        foreach ($logs as $file => $entries) {
            foreach ($entries as $entry) {
                foreach ($patterns as $type => $pattern) {
                    if (preg_match($pattern, $entry['message'])) {
                        $securityIssues[] = [
                            'type' => $type,
                            'severity' => $this->calculateSeverity($entry),
                            'timestamp' => $entry['timestamp'],
                            'details' => $entry['message']
                        ];
                    }
                }
            }
        }

        return $securityIssues;
    }

    protected function detectPerformanceIssues($logs)
    {
        $performanceIssues = [];
        $patterns = [
            'high_load' => '/high (CPU|memory) usage/i',
            'slow_query' => '/slow query|query took \d+ms/i',
            'timeout' => '/timeout|connection refused/i'
        ];

        foreach ($logs as $file => $entries) {
            foreach ($entries as $entry) {
                foreach ($patterns as $type => $pattern) {
                    if (preg_match($pattern, $entry['message'])) {
                        $performanceIssues[] = [
                            'type' => $type,
                            'impact' => $this->assessPerformanceImpact($entry),
                            'timestamp' => $entry['timestamp'],
                            'details' => $entry['message']
                        ];
                    }
                }
            }
        }

        return $performanceIssues;
    }

    protected function prioritizeLogs($analyzed, $context)
    {
        $prioritized = [];
        
        foreach ($analyzed as $category => $entries) {
            $entries = collect($entries)->sortByDesc(function($entry) use ($context) {
                return $this->calculatePriority($entry, $context);
            });
            
            $prioritized[$category] = $entries->take(10)->values()->all();
        }
        
        return $prioritized;
    }

    protected function calculatePriority($entry, $context)
    {
        $priority = 0;
        
        // Base priority by severity
        if (Str::contains(strtolower($entry['message']), ['error', 'exception', 'fatal'])) {
            $priority += 5;
        }
        
        // Context-based priority
        if (isset($context['query']) && Str::contains(strtolower($entry['message']), strtolower($context['query']))) {
            $priority += 3;
        }
        
        // Recency priority
        $age = time() - strtotime($entry['timestamp']);
        $priority += max(0, 5 - floor($age / 3600)); // Higher priority for newer entries
        
        return $priority;
    }

    // Additional helper methods...
}
