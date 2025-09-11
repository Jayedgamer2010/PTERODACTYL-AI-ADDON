<?php

namespace Blueprint\Extensions\AIAssistant\Tests\Mocks;

class MockLogAnalysisService
{
    public function getRelevantLogs($serverId, $context = [])
    {
        return [
            'errors' => [],
            'warnings' => [],
            'security' => [],
            'performance' => []
        ];
    }

    public function analyzeSecurityIncidents($serverId)
    {
        return [];
    }

    public function getPerformanceIssues($serverId)
    {
        return [];
    }
}

class MockServerMetricsService
{
    public function getServerMetrics($serverId)
    {
        return [
            'cpu_usage' => 45,
            'memory_usage' => 2048,
            'status' => 'running'
        ];
    }

    public function getDetailedMetrics($serverId)
    {
        return array_merge(
            $this->getServerMetrics($serverId),
            [
                'cpu_details' => [
                    'load' => [0.5, 0.7, 0.4],
                    'cores' => 4
                ],
                'memory_breakdown' => [
                    'total' => 8192,
                    'used' => 2048,
                    'cached' => 1024
                ]
            ]
        );
    }
}

class MockSecurityService
{
    public function getServerSecurityStatus($serverId)
    {
        return [
            'firewall' => 'active',
            'ssl' => 'valid',
            'updates' => 'current'
        ];
    }
}

class MockAIService
{
    public function processUserQuery($query, $context = [])
    {
        return "This is a mock AI response for query: " . $query;
    }
}
