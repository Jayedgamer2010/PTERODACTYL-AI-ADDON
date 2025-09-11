<?php

namespace App\BlueprintFramework\Extensions\AIAssistant\Tests\Mocks\Services;

class LogAnalysisService
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
