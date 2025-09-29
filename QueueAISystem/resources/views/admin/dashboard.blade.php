@extends('layouts.admin')

@section('title')
    QueueAI System
@endsection

@section('content-header')
    <h1>QueueAI System <small>Queue Management & AI Assistant</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">QueueAI System</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Queue System Section -->
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Queue Management</h3>
            </div>
            <div class="box-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="text-center" style="padding: 20px 0;">
                    @if($isInQueue)
                        <div class="queue-position">
                            <h2 style="font-size: 36px; margin-bottom: 10px; color: #3c8dbc;">
                                #{{ $userQueue->position }}
                            </h2>
                            <p style="font-size: 16px; color: #666;">
                                You are currently in the queue
                            </p>
                            <p style="margin-top: 15px;">
                                <strong>Total in queue:</strong> {{ $totalInQueue }} users
                            </p>
                        </div>
                        
                        <form method="POST" action="{{ route('admin.queueaisystem.queue.leave') }}" style="margin-top: 20px;">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-sign-out"></i> Leave Queue
                            </button>
                        </form>
                    @else
                        <div class="no-queue">
                            <i class="fa fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                            <h4>You are not in the queue</h4>
                            <p style="color: #666; margin-bottom: 20px;">
                                Currently <strong>{{ $totalInQueue }}</strong> users waiting
                            </p>
                            
                            <form method="POST" action="{{ route('admin.queueaisystem.queue.join') }}">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-plus-circle"></i> Join Queue
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- AI System Section -->
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">AI Assistant</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-robot"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">AI Providers</span>
                                <span class="info-box-number">{{ $aiStats['active_providers'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-comments"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Conversations</span>
                                <span class="info-box-number">{{ $aiStats['total_conversations'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($canUseAI)
                    <div class="ai-chat-section" style="margin-top: 20px;">
                        <h4>Quick AI Chat</h4>
                        <div class="form-group">
                            <textarea id="ai-message" class="form-control" rows="3" placeholder="Ask me anything about your Pterodactyl setup..."></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="sendAIMessage()">
                            <i class="fa fa-paper-plane"></i> Send Message
                        </button>
                        <div id="ai-response" style="margin-top: 15px; display: none;">
                            <div class="alert alert-info">
                                <strong>AI Response:</strong>
                                <div id="ai-response-content"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        AI features are not available for your account level.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Code Generation Section -->
@if($canUseAI)
<div class="row">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">AI Code Generation</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="code-request">Describe what code you need:</label>
                            <input type="text" id="code-request" class="form-control" placeholder="e.g., Create a backup script for my Minecraft server">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="code-language">Language:</label>
                            <select id="code-language" class="form-control">
                                <option value="bash">Bash Script</option>
                                <option value="python">Python</option>
                                <option value="php">PHP</option>
                                <option value="yaml">YAML Config</option>
                                <option value="json">JSON Config</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-warning" onclick="generateCode()">
                    <i class="fa fa-code"></i> Generate Code
                </button>
                
                <div id="generated-code" style="margin-top: 20px; display: none;">
                    <h4>Generated Code:</h4>
                    <div class="code-block">
                        <div class="code-header" style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd;">
                            <span id="code-language-display"></span>
                            <button type="button" class="btn btn-xs btn-default pull-right" onclick="copyCode()">
                                <i class="fa fa-copy"></i> Copy
                            </button>
                        </div>
                        <pre id="code-content" style="background: #2d3748; color: #e2e8f0; padding: 15px; margin: 0; border: 1px solid #ddd; border-top: none;"></pre>
                    </div>
                    <div id="code-explanation" class="alert alert-info" style="margin-top: 10px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- AI Provider Configuration -->
@if(auth()->user()->root_admin)
<div class="row">
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">AI Provider Configuration</h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#add-provider-modal">
                        <i class="fa fa-plus"></i> Add Provider
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if($aiConfigs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Model</th>
                                    <th>Status</th>
                                    <th>Max Tokens</th>
                                    <th>Cost/1K Tokens</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aiConfigs as $config)
                                <tr>
                                    <td><strong>{{ ucfirst($config->provider) }}</strong></td>
                                    <td>{{ $config->model_name }}</td>
                                    <td>
                                        <span class="label label-success">Active</span>
                                    </td>
                                    <td>{{ $config->max_tokens }}</td>
                                    <td>${{ number_format($config->cost_per_1k_tokens, 4) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        No AI providers configured. Add a provider to enable AI features.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="add-provider-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add AI Provider</h4>
            </div>
            <form method="POST" action="{{ route('admin.queueaisystem.ai.add-provider') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="provider">Provider</label>
                        <select class="form-control" id="provider" name="provider" required>
                            <option value="">Select Provider</option>
                            <option value="openai">OpenAI</option>
                            <option value="claude">Anthropic Claude</option>
                            <option value="deepseek">DeepSeek</option>
                            <option value="gemini">Google Gemini</option>
                            <option value="groq">Groq</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="model_name">Model Name</label>
                        <input type="text" class="form-control" id="model_name" name="model_name" placeholder="gpt-4, claude-3-sonnet, etc." required>
                    </div>
                    <div class="form-group">
                        <label for="api_key">API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key" placeholder="Enter API key" required>
                    </div>
                    <div class="form-group">
                        <label for="max_tokens">Max Tokens</label>
                        <input type="number" class="form-control" id="max_tokens" name="max_tokens" value="4000" min="100" max="8000">
                    </div>
                    <div class="form-group">
                        <label for="cost_per_1k_tokens">Cost per 1K Tokens ($)</label>
                        <input type="number" class="form-control" id="cost_per_1k_tokens" name="cost_per_1k_tokens" step="0.001" min="0" max="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Recent Activity -->
@if(count($recentActivity) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Recent AI Activity</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $activity)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($activity->created_at)->format('M j, H:i') }}</td>
                                <td>
                                    <span class="label label-{{ $activity->role === 'user' ? 'primary' : 'success' }}">
                                        {{ ucfirst($activity->role) }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($activity->message, 80) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('footer-scripts')
<script>
// Optimized AI message sending with better error handling
function sendAIMessage() {
    const messageInput = document.getElementById('ai-message');
    const message = messageInput.value.trim();
    
    if (!message) {
        alert('Please enter a message');
        return;
    }

    const responseDiv = document.getElementById('ai-response');
    const responseContent = document.getElementById('ai-response-content');
    const sendButton = document.querySelector('button[onclick="sendAIMessage()"]');
    
    // Show loading state
    responseDiv.style.display = 'block';
    responseContent.innerHTML = '<i class="fa fa-spinner fa-spin"></i> AI is thinking...';
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';

    // Send request with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

    fetch('{{ route("admin.queueaisystem.ai.chat") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            message: message,
            context: {
                page: 'dashboard',
                timestamp: Date.now()
            }
        }),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Format response with markdown-like styling
            let formattedResponse = data.response
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
            
            responseContent.innerHTML = formattedResponse;
            
            // Show metadata if available
            if (data.metadata && data.metadata.cached) {
                responseContent.innerHTML += '<br><small class="text-muted"><i class="fa fa-clock-o"></i> Cached response</small>';
            }
            
            // Clear input
            messageInput.value = '';
        } else {
            responseContent.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Error: ' + (data.error || 'Unknown error') + '</span>';
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        let errorMessage = 'Failed to get AI response';
        
        if (error.name === 'AbortError') {
            errorMessage = 'Request timed out. Please try again.';
        } else if (error.message.includes('429')) {
            errorMessage = 'Rate limit exceeded. Please wait a moment.';
        } else if (error.message.includes('403')) {
            errorMessage = 'AI access not permitted for your account.';
        }
        
        responseContent.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> ' + errorMessage + '</span>';
    })
    .finally(() => {
        // Reset button state
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="fa fa-paper-plane"></i> Send Message';
    });
}

// Enhanced code generation with comprehensive validation
function generateCode() {
    const requestInput = document.getElementById('code-request');
    const languageSelect = document.getElementById('code-language');
    const request = requestInput.value.trim();
    const language = languageSelect.value;
    
    // Comprehensive input validation
    const validation = validateCodeRequest(request, language);
    if (!validation.valid) {
        showValidationError(validation.message, requestInput);
        return;
    }

    const codeDiv = document.getElementById('generated-code');
    const codeContent = document.getElementById('code-content');
    const codeExplanation = document.getElementById('code-explanation');
    const languageDisplay = document.getElementById('code-language-display');
    const generateButton = document.querySelector('button[onclick="generateCode()"]');
    
    // Show loading state
    codeDiv.style.display = 'block';
    codeContent.textContent = 'Generating code...';
    codeExplanation.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating explanation...';
    languageDisplay.textContent = language.toUpperCase();
    generateButton.disabled = true;
    generateButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';

    // Send request with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout for code generation

    fetch('{{ route("admin.queueaisystem.ai.generate-code") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            request: request,
            language: language,
            context: {
                user_agent: navigator.userAgent,
                timestamp: Date.now()
            }
        }),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            codeContent.textContent = data.code;
            codeExplanation.innerHTML = '<strong><i class="fa fa-info-circle"></i> Explanation:</strong> ' + data.explanation;
            
            // Add copy success feedback
            const copyButton = document.querySelector('button[onclick="copyCode()"]');
            if (copyButton) {
                copyButton.style.display = 'inline-block';
            }
            
            // Scroll to generated code
            codeDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            codeContent.textContent = '// Error generating code';
            codeExplanation.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Error: ' + (data.error || 'Unknown error') + '</span>';
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        let errorMessage = 'Failed to generate code';
        
        if (error.name === 'AbortError') {
            errorMessage = 'Code generation timed out. Please try a simpler request.';
        } else if (error.message.includes('429')) {
            errorMessage = 'Rate limit exceeded. Please wait before generating more code.';
        }
        
        codeContent.textContent = '// ' + errorMessage;
        codeExplanation.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> ' + errorMessage + '</span>';
    })
    .finally(() => {
        // Reset button state
        generateButton.disabled = false;
        generateButton.innerHTML = '<i class="fa fa-code"></i> Generate Code';
    });
}

// Enhanced copy function with better feedback
function copyCode() {
    const codeContent = document.getElementById('code-content');
    const copyButton = document.querySelector('button[onclick="copyCode()"]');
    
    if (!codeContent.textContent || codeContent.textContent.includes('Error')) {
        alert('No valid code to copy');
        return;
    }
    
    // Use modern clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(codeContent.textContent).then(() => {
            showCopySuccess(copyButton);
        }).catch(() => {
            fallbackCopy(codeContent, copyButton);
        });
    } else {
        fallbackCopy(codeContent, copyButton);
    }
}

function fallbackCopy(codeContent, copyButton) {
    const textArea = document.createElement('textarea');
    textArea.value = codeContent.textContent;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(copyButton);
    } catch (err) {
        alert('Failed to copy code. Please select and copy manually.');
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fa fa-check"></i> Copied!';
    button.classList.add('btn-success');
    button.classList.remove('btn-default');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-default');
    }, 2000);
}

// Enhanced auto-resize textarea with better UX
document.addEventListener('DOMContentLoaded', function() {
    const aiMessage = document.getElementById('ai-message');
    if (aiMessage) {
        aiMessage.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px'; // Max height of 120px
        });
        
        // Allow Enter to send message (Shift+Enter for new line)
        aiMessage.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendAIMessage();
            }
        });
    }
    
    // Auto-refresh queue position every 30 seconds
    if (document.querySelector('.queue-position')) {
        setInterval(function() {
            // Only refresh if user is still on the page
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    }
    
    // Add loading states to form submissions
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                
                // Re-enable after 5 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 5000);
            }
        });
    });
    
    // Initialize comprehensive form validation
    initializeFormValidation();
});

// Comprehensive form validation functions
function validateCodeRequest(request, language) {
    if (!request || request.length === 0) {
        return { valid: false, message: 'Please describe what code you need' };
    }
    
    if (request.length < 5) {
        return { valid: false, message: 'Request must be at least 5 characters long' };
    }
    
    if (request.length > 1000) {
        return { valid: false, message: 'Request must be less than 1000 characters' };
    }
    
    const validPattern = /^[a-zA-Z0-9\s\-_.,!?()[\]{}'"]+$/;
    if (!validPattern.test(request)) {
        return { valid: false, message: 'Request contains invalid characters' };
    }
    
    const dangerousPatterns = [
        /rm\s+-rf/i, /sudo\s+/i, /chmod\s+777/i, /passwd/i, /shadow/i,
        /etc\/passwd/i, /curl.*\|.*sh/i, /wget.*\|.*sh/i, /eval\s*\(/i,
        /exec\s*\(/i, /system\s*\(/i
    ];
    
    for (const pattern of dangerousPatterns) {
        if (pattern.test(request)) {
            return { valid: false, message: 'Request contains potentially unsafe patterns' };
        }
    }
    
    const allowedLanguages = ['bash', 'python', 'php', 'yaml', 'json'];
    if (!allowedLanguages.includes(language)) {
        return { valid: false, message: 'Invalid programming language selected' };
    }
    
    return { valid: true, message: '' };
}

function validateAIMessage(message) {
    if (!message || message.trim().length === 0) {
        return { valid: false, message: 'Please enter a message' };
    }
    
    if (message.trim().length < 2) {
        return { valid: false, message: 'Message must be at least 2 characters long' };
    }
    
    if (message.length > 2000) {
        return { valid: false, message: 'Message must be less than 2000 characters' };
    }
    
    const spamPatterns = [
        /(.)\1{10,}/, // Repeated characters
        /[A-Z]{20,}/, // Too many capitals
        /https?:\/\/[^\s]+/i // URLs
    ];
    
    for (const pattern of spamPatterns) {
        if (pattern.test(message)) {
            return { valid: false, message: 'Message appears to be spam or contains invalid content' };
        }
    }
    
    return { valid: true, message: '' };
}

function showValidationError(message, inputElement = null) {
    let errorDiv = document.getElementById('validation-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'validation-error';
        errorDiv.className = 'alert alert-danger mt-2';
        errorDiv.style.display = 'none';
    }
    
    errorDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + message;
    errorDiv.style.display = 'block';
    
    if (inputElement && inputElement.parentNode) {
        inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
        inputElement.classList.add('is-invalid');
        inputElement.focus();
    } else {
        const container = document.querySelector('.card-body') || document.body;
        container.insertBefore(errorDiv, container.firstChild);
    }
    
    setTimeout(() => {
        errorDiv.style.display = 'none';
        if (inputElement) {
            inputElement.classList.remove('is-invalid');
        }
    }, 5000);
}

function initializeFormValidation() {
    const messageInput = document.getElementById('ai-message');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            const validation = validateAIMessage(this.value);
            if (!validation.valid && this.value.length > 0) {
                this.classList.add('is-invalid');
                this.title = validation.message;
            } else {
                this.classList.remove('is-invalid');
                this.title = '';
            }
        });
    }
    
    const codeRequestInput = document.getElementById('code-request');
    if (codeRequestInput) {
        codeRequestInput.addEventListener('input', function() {
            const language = document.getElementById('code-language').value;
            const validation = validateCodeRequest(this.value, language);
            if (!validation.valid && this.value.length > 0) {
                this.classList.add('is-invalid');
                this.title = validation.message;
            } else {
                this.classList.remove('is-invalid');
                this.title = '';
            }
        });
    }
    
    addCharacterCounters();
}

function addCharacterCounters() {
    const inputs = [
        { id: 'ai-message', max: 2000 },
        { id: 'code-request', max: 1000 }
    ];
    
    inputs.forEach(input => {
        const element = document.getElementById(input.id);
        if (element) {
            const counter = document.createElement('small');
            counter.className = 'text-muted character-counter';
            counter.style.float = 'right';
            element.parentNode.appendChild(counter);
            
            const updateCounter = () => {
                const remaining = input.max - element.value.length;
                counter.textContent = `${element.value.length}/${input.max}`;
                counter.className = remaining < 50 ? 'text-danger character-counter' : 'text-muted character-counter';
            };
            
            element.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
}
</script>
@endsection