<?php

namespace Blueprint\Extensions\AIAssistant\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;
use Blueprint\Extensions\AIAssistant\Tests\Mocks\MockLogAnalysisService;
use Blueprint\Extensions\AIAssistant\Tests\Mocks\MockServerMetricsService;
use Blueprint\Extensions\AIAssistant\Tests\Mocks\MockSecurityService;
use Blueprint\Extensions\AIAssistant\Tests\Mocks\MockAIService;

class TestCase extends BaseTestCase
{
    protected $metricsService;
    protected $logAnalysis;
    protected $security;
    protected $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize mock services
        $this->metricsService = new MockServerMetricsService();
        $this->logAnalysis = new MockLogAnalysisService();
        $this->security = new MockSecurityService();
        $this->aiService = new MockAIService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mock($class)
    {
        return Mockery::mock($class);
    }

    protected function createTestAIService()
    {
        $metricsService = $this->mock('App\BlueprintFramework\Extensions\AIAssistant\Services\ServerMetricsService');
        $logAnalysis = $this->mock('App\BlueprintFramework\Extensions\AIAssistant\Services\LogAnalysisService');
        $security = $this->mock('App\BlueprintFramework\Extensions\AIAssistant\Services\SecurityService');
        
        $metricsService->shouldReceive('getServerMetrics')->andReturn([
            'cpu_usage' => 45,
            'memory_usage' => 2048,
            'status' => 'running'
        ]);

        $logAnalysis->shouldReceive('getRelevantLogs')->andReturn([
            'errors' => [],
            'warnings' => []
        ]);

        $security->shouldReceive('getServerSecurityStatus')->andReturn([
            'firewall' => 'active',
            'ssl' => 'valid',
            'updates' => 'current'
        ]);

        return new AIService(
            config('ai-assistant.providers'),
            $metricsService,
            $logAnalysis,
            $security
        );
    }
}
