<?php

namespace App\BlueprintFramework\Extensions\AIAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAction extends Model
{
    protected $table = 'ai_chat_actions';

    protected $fillable = [
        'chat_id',
        'message_id',
        'action_type',
        'action_data',
        'status',
        'result',
        'executed_at'
    ];

    protected $casts = [
        'action_data' => 'array',
        'result' => 'array',
        'executed_at' => 'datetime'
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function message()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function execute()
    {
        try {
            $handler = $this->getActionHandler();
            $result = $handler->execute($this->action_data);
            
            $this->update([
                'status' => 'completed',
                'result' => $result,
                'executed_at' => now()
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->update([
                'status' => 'failed',
                'result' => ['error' => $e->getMessage()],
                'executed_at' => now()
            ]);

            throw $e;
        }
    }

    protected function getActionHandler()
    {
        $handlerClass = config('ai-assistant.action_handlers.' . $this->action_type);
        
        if (!$handlerClass || !class_exists($handlerClass)) {
            throw new \Exception("No handler found for action type: {$this->action_type}");
        }

        return new $handlerClass();
    }
}
