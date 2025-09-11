<?php

namespace App\BlueprintFramework\Extensions\AIAssistant\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function index()
    {
        return view('ai-assistant::admin.index');
    }

    public function settings()
    {
        $config = config('ai-assistant');
        return view('ai-assistant::admin.settings', compact('config'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'providers.*.enabled' => 'boolean',
            'providers.*.api_key' => 'nullable|string',
            'settings.*' => 'required',
        ]);

        try {
            // Update configuration
            $this->updateConfig($validated);

            return redirect()->back()->with('success', 'Settings updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update AI Assistant settings: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update settings');
        }
    }

    public function logs()
    {
        $logs = Log::get('ai-assistant')->paginate(50);
        return view('ai-assistant::admin.logs', compact('logs'));
    }

    protected function updateConfig($data)
    {
        // Implementation for updating config
    }
}
