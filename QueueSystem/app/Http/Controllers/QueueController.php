<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\User;
use Illuminate\Database\Eloquent\Model;

// Queue Model inline for Blueprint compatibility
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

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $userQueue = Queue::where('user_id', $user->id)
                          ->where('status', 'waiting')
                          ->first();
        
        $totalInQueue = Queue::where('status', 'waiting')->count();
        
        return view('admin.queuesystem.queue', [
            'userQueue' => $userQueue,
            'totalInQueue' => $totalInQueue,
            'isInQueue' => !is_null($userQueue),
        ]);
    }

    public function join(Request $request)
    {
        $user = Auth::user();
        
        // Check if user already in queue
        $existing = Queue::where('user_id', $user->id)
                         ->where('status', 'waiting')
                         ->first();
        
        if ($existing) {
            return redirect()->route('admin.queuesystem.index')
                           ->with('error', 'You are already in the queue!');
        }
        
        // Add to queue
        $maxPosition = Queue::where('status', 'waiting')->max('position') ?? 0;
        
        Queue::create([
            'user_id' => $user->id,
            'position' => $maxPosition + 1,
            'status' => 'waiting',
        ]);
        
        return redirect()->route('admin.queuesystem.index')
                       ->with('success', 'Successfully joined the queue!');
    }

    public function leave(Request $request)
    {
        $user = Auth::user();
        
        $queue = Queue::where('user_id', $user->id)
                     ->where('status', 'waiting')
                     ->first();
        
        if (!$queue) {
            return redirect()->route('admin.queuesystem.index')
                           ->with('error', 'You are not in the queue!');
        }
        
        $queue->delete();
        
        // Update positions for remaining users
        Queue::updatePositions();
        
        return redirect()->route('admin.queuesystem.index')
                       ->with('success', 'Successfully left the queue!');
    }
}