<?php

namespace Blueprint\Extensions\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIChatHistory extends Model
{
    protected $table = 'ai_chat_history';

    protected $fillable = [
        'user_id',
        'server_id',
        'message',
        'response',
        'context',
        'provider'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\Server::class);
    }
}
