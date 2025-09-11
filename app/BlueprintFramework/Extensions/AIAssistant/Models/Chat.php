<?php

namespace App\BlueprintFramework\Extensions\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use SoftDeletes;

    protected $table = 'ai_chats';

    protected $fillable = [
        'user_id',
        'server_id',
        'title',
        'status',
        'context',
        'metadata'
    ];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function suggestions()
    {
        return $this->hasMany(ChatSuggestion::class);
    }

    public function actions()
    {
        return $this->hasMany(ChatAction::class);
    }

    public function getContextAttribute($value)
    {
        $context = json_decode($value, true) ?? [];
        return array_merge([
            'server_status' => null,
            'performance_metrics' => null,
            'security_status' => null,
            'user_permissions' => null,
        ], $context);
    }

    public function updateContext(array $data)
    {
        $this->context = array_merge($this->context, $data);
        $this->save();
    }
}
