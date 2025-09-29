@extends('layouts.admin')

@section('title')
    AI Assistant Configuration
@endsection

@section('content-header')
    <h1>AI Assistant Configuration <small>Manage AI providers, models, and settings</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.queuesystem.index') }}">Queue System</a></li>
        <li class="active">AI Configuration</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- AI Provider Status -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">AI Provider Status</h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-sm btn-success" id="test-all-providers">
                        <i class="fa fa-check-circle"></i> Test All Providers
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="row" id="provider-status">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active Providers</span>
                                <span class="info-box-number">{{ $activeProviders }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow"><i class="fa fa-code"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Code Generated</span>
                                <span class="info-box-number">{{ $codeGenerated }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-comments"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Conversations</span>
                                <span class="info-box-number">{{ $totalConversations }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-red"><i class="fa fa-dollar"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Monthly Cost</span>
                                <span class="info-box-number">${{ number_format($monthlyCost, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Provider Configuration -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">AI Provider Configuration</h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#add-provider-modal">
                        <i class="fa fa-plus"></i> Add Provider
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Model</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Rate Limit</th>
                                <th>Cost/1K Tokens</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aiConfigs as $config)
                            <tr>
                                <td>
                                    <strong>{{ ucfirst($config->provider) }}</strong>
                                    @if($config->is_default)
                                        <span class="label label-primary">Default</span>
                                    @endif
                                </td>
                                <td>{{ $config->model_name }}</td>
                                <td>
                                    <span class="label label-{{ $config->model_type === 'chat' ? 'info' : ($config->model_type === 'code' ? 'warning' : 'success') }}">
                                        {{ ucfirst($config->model_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($config->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $config->rate_limit_per_minute }}/min</td>
                                <td>${{ number_format($config->cost_per_1k_tokens, 4) }}</td>
                                <td>
                                    <button class="btn btn-xs btn-info" onclick="editProvider({{ $config->id }})">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-xs btn-warning" onclick="testProvider({{ $config->id }})">
                                        <i class="fa fa-check"></i>
                                    </button>
                                    <button class="btn btn-xs btn-danger" onclick="deleteProvider({{ $config->id }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- System Prompts Configuration -->
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">System Prompts</h3>
            </div>
            <div class="box-body">
                <form method="POST" action="{{ route('admin.ai.update-prompts') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="system_prompt">Main System Prompt</label>
                                <textarea class="form-control" id="system_prompt" name="system_prompt" rows="8" placeholder="You are a helpful AI assistant for Pterodactyl Panel...">{{ $systemPrompt }}</textarea>
                                <p class="help-block">This prompt defines the AI's behavior and personality for general chat.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code_prompt">Code Generation Prompt</label>
                                <textarea class="form-control" id="code_prompt" name="code_prompt" rows="8" placeholder="Generate secure, well-commented code...">{{ $codePrompt }}</textarea>
                                <p class="help-block">This prompt is used specifically for code generation requests.</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Prompts
                        </button>
                        <button type="button" class="btn btn-default" onclick="resetPrompts()">
                            <i class="fa fa-refresh"></i> Reset to Default
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security & Safety Settings -->
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Security & Safety Settings</h3>
            </div>
            <div class="box-body">
                <form method="POST" action="{{ route('admin.ai.update-security') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Code Generation Safety</h4>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="require_admin_approval" {{ $securitySettings['require_admin_approval'] ? 'checked' : '' }}>
                                    Require admin approval for dangerous code
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="enable_sandbox" {{ $securitySettings['enable_sandbox'] ? 'checked' : '' }}>
                                    Enable sandbox execution
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="audit_all_code" {{ $securitySettings['audit_all_code'] ? 'checked' : '' }}>
                                    Audit all generated code
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="max_code_length">Maximum code length (characters)</label>
                                <input type="number" class="form-control" id="max_code_length" name="max_code_length" value="{{ $securitySettings['max_code_length'] }}" min="100" max="50000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4>Rate Limiting</h4>
                            <div class="form-group">
                                <label for="user_requests_per_hour">User requests per hour</label>
                                <input type="number" class="form-control" id="user_requests_per_hour" name="user_requests_per_hour" value="{{ $securitySettings['user_requests_per_hour'] }}" min="1" max="1000">
                            </div>
                            <div class="form-group">
                                <label for="admin_requests_per_hour">Admin requests per hour</label>
                                <input type="number" class="form-control" id="admin_requests_per_hour" name="admin_requests_per_hour" value="{{ $securitySettings['admin_requests_per_hour'] }}" min="1" max="5000">
                            </div>
                            <div class="form-group">
                                <label for="max_tokens_per_request">Maximum tokens per request</label>
                                <input type="number" class="form-control" id="max_tokens_per_request" name="max_tokens_per_request" value="{{ $securitySettings['max_tokens_per_request'] }}" min="100" max="8000">
                            </div>
                            <div class="form-group">
                                <label for="daily_cost_limit">Daily cost limit ($)</label>
                                <input type="number" class="form-control" id="daily_cost_limit" name="daily_cost_limit" value="{{ $securitySettings['daily_cost_limit'] }}" min="0" max="1000" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-shield"></i> Update Security Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Analytics & Monitoring -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Analytics & Monitoring</h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-sm btn-default" onclick="refreshAnalytics()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="usage-chart" width="400" height="200"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="cost-chart" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h4>Recent Activity</h4>
                        <div class="table-responsive">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Provider</th>
                                        <th>Tokens</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivity as $activity)
                                    <tr>
                                        <td>{{ $activity->created_at->format('H:i:s') }}</td>
                                        <td>{{ $activity->user->name ?? 'Unknown' }}</td>
                                        <td>
                                            @if($activity->role === 'user')
                                                <span class="label label-info">Chat</span>
                                            @else
                                                <span class="label label-warning">Code Gen</span>
                                            @endif
                                        </td>
                                        <td>{{ $activity->ai_provider }}</td>
                                        <td>{{ $activity->tokens_used }}</td>
                                        <td>${{ number_format($activity->cost, 4) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="add-provider-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add AI Provider</h4>
            </div>
            <form method="POST" action="{{ route('admin.ai.add-provider') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="provider">Provider</label>
                                <select class="form-control" id="provider" name="provider" required>
                                    <option value="">Select Provider</option>
                                    <option value="openai">OpenAI</option>
                                    <option value="claude">Anthropic Claude</option>
                                    <option value="deepseek">DeepSeek</option>
                                    <option value="gemini">Google Gemini</option>
                                    <option value="groq">Groq</option>
                                    <option value="ollama">Ollama (Local)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model_name">Model Name</label>
                                <input type="text" class="form-control" id="model_name" name="model_name" placeholder="gpt-4o, claude-3-sonnet, etc." required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model_type">Model Type</label>
                                <select class="form-control" id="model_type" name="model_type" required>
                                    <option value="chat">Chat</option>
                                    <option value="code">Code Generation</option>
                                    <option value="fast">Fast Response</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="api_key">API Key</label>
                                <input type="password" class="form-control" id="api_key" name="api_key" placeholder="Enter API key" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="api_endpoint">API Endpoint (Optional)</label>
                                <input type="url" class="form-control" id="api_endpoint" name="api_endpoint" placeholder="https://api.openai.com/v1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_tokens">Max Tokens</label>
                                <input type="number" class="form-control" id="max_tokens" name="max_tokens" value="4000" min="100" max="8000">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rate_limit_per_minute">Rate Limit (per minute)</label>
                                <input type="number" class="form-control" id="rate_limit_per_minute" name="rate_limit_per_minute" value="60" min="1" max="1000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cost_per_1k_tokens">Cost per 1K Tokens ($)</label>
                                <input type="number" class="form-control" id="cost_per_1k_tokens" name="cost_per_1k_tokens" step="0.000001" min="0" max="1">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" checked>
                            Active
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_default">
                            Set as default for this model type
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Initialize charts
const usageCtx = document.getElementById('usage-chart').getContext('2d');
const usageChart = new Chart(usageCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($usageChartLabels) !!},
        datasets: [{
            label: 'Requests',
            data: {!! json_encode($usageChartData) !!},
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'AI Usage (Last 7 Days)'
            }
        }
    }
});

const costCtx = document.getElementById('cost-chart').getContext('2d');
const costChart = new Chart(costCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($costChartLabels) !!},
        datasets: [{
            label: 'Cost ($)',
            data: {!! json_encode($costChartData) !!},
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'AI Costs (Last 7 Days)'
            }
        }
    }
});

// Provider management functions
function editProvider(id) {
    // Implementation for editing provider
    console.log('Edit provider:', id);
}

function testProvider(id) {
    // Test provider connection
    fetch(`/admin/ai/test-provider/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Provider test successful!');
        } else {
            alert('Provider test failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error testing provider: ' + error.message);
    });
}

function deleteProvider(id) {
    if (confirm('Are you sure you want to delete this provider?')) {
        fetch(`/admin/ai/delete-provider/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting provider: ' + data.message);
            }
        });
    }
}

function resetPrompts() {
    if (confirm('Reset prompts to default values?')) {
        document.getElementById('system_prompt').value = 'You are a helpful AI assistant for Pterodactyl Panel. You help users manage their game servers, troubleshoot issues, and optimize configurations. Always provide safe, secure, and well-explained solutions.';
        document.getElementById('code_prompt').value = 'Generate secure, well-commented code based on the user\'s request. Include explanations and safety considerations. Follow best practices for the target language and environment.';
    }
}

function refreshAnalytics() {
    location.reload();
}

// Test all providers
document.getElementById('test-all-providers').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testing...';
    
    fetch('/admin/ai/test-all-providers', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(`Provider test results:\nActive: ${data.active}\nFailed: ${data.failed}`);
        location.reload();
    })
    .catch(error => {
        alert('Error testing providers: ' + error.message);
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fa fa-check-circle"></i> Test All Providers';
    });
});
</script>
@endsection