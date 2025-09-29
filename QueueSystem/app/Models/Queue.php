<?php

namespace Pterodactyl\BlueprintExtensions\QueueSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\User;

class Queue extends Model
{
    protected $fillable = [
        'user_id',
        'position',
        'status',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function updatePositions()
    {
        $queues = self::where('status', 'waiting')
                      ->orderBy('created_at')
                      ->get();

        foreach ($queues as $index => $queue) {
            $queue->position = $index + 1;
            $queue->save();
        }
    }
}