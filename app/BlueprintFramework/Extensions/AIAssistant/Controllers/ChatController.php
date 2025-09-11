<?php

namespace App\BlueprintFramework\Extensions\AIAssistant\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Blueprint\Extensions\AIAssistant\Services\AIService;

class ChatController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function show()
    {
        return view('ai-assistant::chat');
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $response = $this->aiService->processUserQuery(
                $request->message,
                [
                    'user_id' => auth()->id(),
                    'server_id' => $request->server_id,
                ]
            );

            return response()->json([
                'success' => true,
                'response' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to process message'
            ], 500);
        }
    }
}
