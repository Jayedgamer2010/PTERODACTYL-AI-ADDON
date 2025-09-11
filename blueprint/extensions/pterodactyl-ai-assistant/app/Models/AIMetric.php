<?php

namespace Blueprint\Extensions\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIMetric extends Model
{
    protected $table = 'ai_metrics';
    
    public $timestamps = false;

    protected $fillable = [
        'metric_key',
        'metric_value',
        'server_id',
        'recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'datetime'
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\Server::class);
    }
}
