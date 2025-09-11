<?php

namespace Blueprint\Extensions\AIAssistant\Tests\Mocks;

class MockServerMetricsService
{
    public function getServerMetrics($serverId = null)
    {
        return [
            'cpu_usage' => 45,
            'memory_usage' => 2048,
            'disk_usage' => 5000,
            'status' => 'running'
        ];
    }

    public function getDetailedMetrics($serverId = null)
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
