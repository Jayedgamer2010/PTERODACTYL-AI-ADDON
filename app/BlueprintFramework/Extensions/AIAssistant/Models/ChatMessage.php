<?php

namespace App\BlueprintFramework\Extensions\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'processed_at',
        'error'
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function suggestions()
    {
        return $this->hasMany(ChatSuggestion::class);
    }

    public function actions()
    {
        return $this->hasMany(ChatAction::class);
    }

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    public function markAsProcessed()
    {
        $this->processed_at = now();
        $this->save();
    }

    public function markAsError($error)
    {
        $this->error = $error;
        $this->save();
    }

    public function isFromAI()
    {
        return $this->user_id === null;
    }

    public function getContentAttribute($value)
    {
        return $this->isFromAI() ? $this->formatAIResponse($value) : $value;
    }

    protected function formatAIResponse($content)
    {
        // Apply formatting to AI responses
        return $content;
    }
}
