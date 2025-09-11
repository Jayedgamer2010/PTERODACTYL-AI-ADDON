<?php

namespace Blueprint\Extensions\AIAssistant\Tests;

use Blueprint\Extensions\AIAssistant\Tests\TestCase;
use Blueprint\Extensions\AIAssistant\Services\AIService;
use Blueprint\Extensions\AIAssistant\Services\WebSocketService;
use Blueprint\Extensions\AIAssistant\Services\ServerMetricsService;

class AIAssistantTest extends TestCase
{
    protected $aiService;
    protected $webSocketService;
    protected $metricsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->webSocketService = new WebSocketService($this->aiService, $this->metricsService, $this->security);
    }

    public function testChatWidget()
    {
        $query = "How do I start my Minecraft server?";
        $response = $this->aiService->processUserQuery($query, [
            'user_id' => 1,
            'server_id' => 1,
            'test_mode' => true
        ]);

        $this->assertNotEmpty($response);
        $this->assertStringContainsString('server', $response);
        $this->assertStringContainsString('start', $response);
    }

    public function testAdminMetrics()
    {
        $metrics = $this->metricsService->getServerMetrics(1);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('cpu_usage', $metrics);
        $this->assertArrayHasKey('memory_usage', $metrics);
        $this->assertArrayHasKey('disk_usage', $metrics);
    }

    public function testProviderFailover()
    {
        // Simulate primary provider failure
        config(['ai-assistant.providers.openai.enabled' => false]);

        $query = "Generate server optimization report";
        $response = $this->aiService->processUserQuery($query, [
            'user_id' => 1,
            'server_id' => 1
        ]);

        $this->assertNotEmpty($response);
        $this->assertNotEquals('openai', $this->aiService->getActiveProvider());
    }

    public function testWebSocketConnection()
    {
        $connection = new \stdClass();
        $connection->connectionId = uniqid();
        
        $this->webSocketService->onOpen($connection);
        
        $this->assertTrue($this->webSocketService->hasConnection($connection->connectionId));
    }

    public function testRealTimeMetrics()
    {
        $metrics = [];
        $connection = new \stdClass();
        $connection->connectionId = uniqid();
        
        $this->webSocketService->onOpen($connection);
        
        // Subscribe to metrics updates
        $this->webSocketService->onMessage($connection, json_encode([
            'type' => 'metrics_request',
            'server_id' => 1
        ]));

        // Wait for metrics update
        sleep(1);
        
        $this->assertNotEmpty($metrics);
    }

    public function testSecurityChecks()
    {
        $security = app(SecurityService::class);
        $status = $security->getServerSecurityStatus(1);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('firewall_status', $status);
        $this->assertArrayHasKey('ssl_certificate', $status);
    }

    public function testCacheSystem()
    {
        $cache = app(CacheService::class);
        
        $testData = ['test' => 'data'];
        $cache->put('test_key', $testData, 'server_metrics');
        
        $retrieved = $cache->get('test_key');
        $this->assertEquals($testData, $retrieved);
    }

    public function testErrorHandling()
    {
        $this->expectException(\Exception::class);
        
        // Simulate error condition
        $this->aiService->processUserQuery('', [
            'user_id' => null,
            'server_id' => null
        ]);
    }

    public function testRateLimiting()
    {
        $this->expectException(\Exception::class);
        
        // Attempt to exceed rate limit
        for ($i = 0; $i <= config('ai-assistant.rate_limit.max_requests'); $i++) {
            $this->aiService->processUserQuery('test', [
                'user_id' => 1,
                'server_id' => 1
            ]);
        }
    }
}
