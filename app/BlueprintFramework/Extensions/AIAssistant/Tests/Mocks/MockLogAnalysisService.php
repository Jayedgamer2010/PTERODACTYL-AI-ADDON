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
