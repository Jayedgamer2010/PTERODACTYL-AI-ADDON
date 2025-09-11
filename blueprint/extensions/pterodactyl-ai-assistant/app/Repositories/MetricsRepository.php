<?php

namespace Blueprint\Extensions\AIAssistant\Repositories;

use Blueprint\Extensions\AIAssistant\Models\AIMetric;
use Blueprint\Extensions\AIAssistant\Contracts\Repositories\MetricsRepositoryInterface;
use Pterodactyl\Repositories\Repository;
use Carbon\Carbon;

class MetricsRepository extends Repository implements MetricsRepositoryInterface
{
    protected $model = AIMetric::class;

    /**
     * Record a new metric.
     */
    public function record(string $key, string $value, ?int $serverId = null): AIMetric
    {
        return $this->model->newInstance()->create([
            'metric_key' => $key,
            'metric_value' => $value,
            'server_id' => $serverId,
            'recorded_at' => Carbon::now(),
        ]);
    }

    /**
     * Get metrics for a specific key.
     */
    public function getMetrics(string $key, ?int $serverId = null, ?Carbon $from = null, ?Carbon $to = null)
    {
        $query = $this->model->newQuery()
            ->where('metric_key', $key);

        if ($serverId) {
            $query->where('server_id', $serverId);
        }

        if ($from) {
            $query->where('recorded_at', '>=', $from);
        }

        if ($to) {
            $query->where('recorded_at', '<=', $to);
        }

        return $query->orderBy('recorded_at', 'desc')->get();
    }
}
