<?php

namespace Blueprint\Extensions\AIAssistant\Contracts\Repositories;

use Blueprint\Extensions\AIAssistant\Models\AIMetric;
use Carbon\Carbon;

interface MetricsRepositoryInterface
{
    public function record(string $key, string $value, ?int $serverId = null): AIMetric;
    public function getMetrics(string $key, ?int $serverId = null, ?Carbon $from = null, ?Carbon $to = null);
}
