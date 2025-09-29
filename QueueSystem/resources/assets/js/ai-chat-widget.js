/**
 * AI Chat Widget for Pterodactyl Panel
 * Provides floating chat interface with real-time AI assistance
 */

class AIChatWidget {
    constructor() {
        this.isOpen = false;
        this.socket = null;
        this.sessionId = this.generateSessionId();
        this.messageHistory = [];
        this.isConnected = false;
        this.isTyping = false;
        
        this.init();
    }

    init() {
        this.createWidget();
        this.setupEventListeners();
        this.connectWebSocket();
        this.loadChatHistory();
    }

    createWidget() {
        // Create floating chat button
        const chatButton = document.createElement('div');
        chatButton.id = 'ai-chat-button';
        chatButton.className = 'ai-chat-button';
        chatButton.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12C2 13.54 2.36 14.99 3.01 16.28L2 22L7.72 20.99C9.01 21.64 10.46 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C10.74 20 9.54 19.75 8.46 19.3L8 19.11L4.91 19.91L5.71 16.82L5.52 16.36C5.07 15.28 4.82 14.08 4.82 12.82C4.82 7.58 8.58 3.82 13.82 3.82C19.06 3.82 22.82 7.58 22.82 12.82C22.82 18.06 19.06 21.82 13.82 21.82H12V20Z" fill="currentColor"/>
                <circle cx="9" cy="12" r="1" fill="currentColor"/>
                <circle cx="12" cy="12" r="1" fill="currentColor"/>
                <circle cx="15" cy="12" r="1" fill="currentColor"/>
            </svg>
            <span class="ai-chat-badge" id="ai-chat-badge" style="display: none;">1</span>
        `;

        // Create chat window
        const chatWindow = document.createElement('div');
        chatWindow.id = 'ai-chat-window';
        chatWindow.className = 'ai-chat-window';
        chatWindow.innerHTML = `
            <div class="ai-chat-header">
                <div class="ai-chat-title">
                    <div class="ai-status-indicator" id="ai-status-indicator"></div>
                    <span>AI Assistant</span>
                    <span class="ai-model-info" id="ai-model-info">GPT-4</span>
                </div>
                <div class="ai-chat-controls">
                    <button class="ai-chat-minimize" id="ai-chat-minimize">−</button>
                    <button class="ai-chat-close" id="ai-chat-close">×</button>
                </div>
            </div>
            <div class="ai-chat-messages" id="ai-chat-messages">
                <div class="ai-welcome-message">
                    <div class="ai-message ai-message-assistant">
                        <div class="ai-message-content">
                            <p>👋 Hello! I'm your AI assistant for Pterodactyl Panel.</p>
                            <p>I can help you with:</p>
                            <ul>
                                <li>🔧 Server optimization and configuration</li>
                                <li>📝 Generate scripts and configs</li>
                                <li>🐛 Troubleshoot issues</li>
                                <li>📚 Answer questions about your panel</li>
                            </ul>
                            <p>What can I help you with today?</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ai-chat-typing" id="ai-chat-typing" style="display: none;">
                <div class="ai-typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span>AI is typing...</span>
            </div>
            <div class="ai-chat-input-container">
                <div class="ai-chat-input-wrapper">
                    <textarea 
                        id="ai-chat-input" 
                        class="ai-chat-input" 
                        placeholder="Ask me anything about your Pterodactyl setup..."
                        rows="1"
                    ></textarea>
                    <div class="ai-chat-input-actions">
                        <button class="ai-chat-attach" id="ai-chat-attach" title="Attach server context">
                            📎
                        </button>
                        <button class="ai-chat-send" id="ai-chat-send" title="Send message">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="ai-chat-suggestions" id="ai-chat-suggestions">
                    <button class="ai-suggestion" data-prompt="Help me optimize my Minecraft server">🎮 Optimize Minecraft server</button>
                    <button class="ai-suggestion" data-prompt="Generate a backup script">💾 Create backup script</button>
                    <button class="ai-suggestion" data-prompt="Fix server startup issues">🔧 Fix startup issues</button>
                </div>
            </div>
        `;

        document.body.appendChild(chatButton);
        document.body.appendChild(chatWindow);
    }

    setupEventListeners() {
        // Chat button toggle
        document.getElementById('ai-chat-button').addEventListener('click', () => {
            this.toggleChat();
        });

        // Close and minimize buttons
        document.getElementById('ai-chat-close').addEventListener('click', () => {
            this.closeChat();
        });

        document.getElementById('ai-chat-minimize').addEventListener('click', () => {
            this.minimizeChat();
        });

        // Send message
        document.getElementById('ai-chat-send').addEventListener('click', () => {
            this.sendMessage();
        });

        // Input handling
        const input = document.getElementById('ai-chat-input');
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        input.addEventListener('input', () => {
            this.autoResizeInput();
        });

        // Suggestion buttons
        document.querySelectorAll('.ai-suggestion').forEach(button => {
            button.addEventListener('click', () => {
                const prompt = button.getAttribute('data-prompt');
                document.getElementById('ai-chat-input').value = prompt;
                this.sendMessage();
            });
        });

        // Attach context button
        document.getElementById('ai-chat-attach').addEventListener('click', () => {
            this.attachServerContext();
        });
    }

    connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}/ws/ai-chat`;
        
        try {
            this.socket = new WebSocket(wsUrl);
            
            this.socket.onopen = () => {
                console.log('AI Chat WebSocket connected');
                this.isConnected = true;
                this.updateConnectionStatus(true);
                
                // Send authentication
                this.socket.send(JSON.stringify({
                    type: 'auth',
                    token: this.getAuthToken(),
                    sessionId: this.sessionId
                }));
            };

            this.socket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };

            this.socket.onclose = () => {
                console.log('AI Chat WebSocket disconnected');
                this.isConnected = false;
                this.updateConnectionStatus(false);
                
                // Attempt to reconnect after 3 seconds
                setTimeout(() => {
                    if (!this.isConnected) {
                        this.connectWebSocket();
                    }
                }, 3000);
            };

            this.socket.onerror = (error) => {
                console.error('AI Chat WebSocket error:', error);
                this.updateConnectionStatus(false);
            };
        } catch (error) {
            console.error('Failed to connect WebSocket:', error);
            this.updateConnectionStatus(false);
        }
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'message':
                this.displayMessage(data.content, 'assistant', data.metadata);
                this.hideTypingIndicator();
                break;
            case 'typing':
                this.showTypingIndicator();
                break;
            case 'code':
                this.displayCodeBlock(data.code, data.language, data.explanation);
                this.hideTypingIndicator();
                break;
            case 'error':
                this.displayError(data.message);
                this.hideTypingIndicator();
                break;
            case 'status':
                this.updateModelInfo(data.model, data.provider);
                break;
        }
    }

    sendMessage() {
        const input = document.getElementById('ai-chat-input');
        const message = input.value.trim();
        
        if (!message || !this.isConnected) return;

        // Display user message
        this.displayMessage(message, 'user');
        
        // Clear input
        input.value = '';
        this.autoResizeInput();

        // Show typing indicator
        this.showTypingIndicator();

        // Send to WebSocket
        this.socket.send(JSON.stringify({
            type: 'message',
            content: message,
            sessionId: this.sessionId,
            context: this.getCurrentContext()
        }));

        // Hide suggestions after first message
        document.getElementById('ai-chat-suggestions').style.display = 'none';
    }

    displayMessage(content, role, metadata = {}) {
        const messagesContainer = document.getElementById('ai-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ai-message-${role}`;
        
        const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageDiv.innerHTML = `
            <div class="ai-message-content">
                ${this.formatMessageContent(content)}
            </div>
            <div class="ai-message-meta">
                <span class="ai-message-time">${timestamp}</span>
                ${metadata.tokens ? `<span class="ai-message-tokens">${metadata.tokens} tokens</span>` : ''}
                ${metadata.cost ? `<span class="ai-message-cost">$${metadata.cost.toFixed(4)}</span>` : ''}
            </div>
        `;

        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
        
        // Store in history
        this.messageHistory.push({
            role,
            content,
            timestamp: Date.now(),
            metadata
        });
    }

    displayCodeBlock(code, language, explanation) {
        const messagesContainer = document.getElementById('ai-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'ai-message ai-message-assistant ai-message-code';
        
        const codeId = 'code-' + Date.now();
        
        messageDiv.innerHTML = `
            <div class="ai-message-content">
                <div class="ai-code-explanation">
                    ${this.formatMessageContent(explanation)}
                </div>
                <div class="ai-code-block">
                    <div class="ai-code-header">
                        <span class="ai-code-language">${language}</span>
                        <div class="ai-code-actions">
                            <button class="ai-code-copy" onclick="aiChat.copyCode('${codeId}')">📋 Copy</button>
                            <button class="ai-code-download" onclick="aiChat.downloadCode('${codeId}', '${language}')">💾 Download</button>
                            <button class="ai-code-execute" onclick="aiChat.executeCode('${codeId}')">▶️ Execute</button>
                        </div>
                    </div>
                    <pre id="${codeId}"><code class="language-${language}">${this.escapeHtml(code)}</code></pre>
                </div>
            </div>
        `;

        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
        
        // Apply syntax highlighting if available
        if (window.Prism) {
            Prism.highlightElement(messageDiv.querySelector('code'));
        }
    }

    formatMessageContent(content) {
        // Convert markdown-like formatting to HTML
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showTypingIndicator() {
        document.getElementById('ai-chat-typing').style.display = 'flex';
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        document.getElementById('ai-chat-typing').style.display = 'none';
    }

    updateConnectionStatus(connected) {
        const indicator = document.getElementById('ai-status-indicator');
        indicator.className = `ai-status-indicator ${connected ? 'connected' : 'disconnected'}`;
        indicator.title = connected ? 'Connected' : 'Disconnected';
    }

    updateModelInfo(model, provider) {
        document.getElementById('ai-model-info').textContent = `${provider}/${model}`;
    }

    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        document.getElementById('ai-chat-window').classList.add('open');
        document.getElementById('ai-chat-button').classList.add('active');
        this.isOpen = true;
        
        // Focus input
        setTimeout(() => {
            document.getElementById('ai-chat-input').focus();
        }, 300);
    }

    closeChat() {
        document.getElementById('ai-chat-window').classList.remove('open');
        document.getElementById('ai-chat-button').classList.remove('active');
        this.isOpen = false;
    }

    minimizeChat() {
        document.getElementById('ai-chat-window').classList.add('minimized');
    }

    scrollToBottom() {
        const messagesContainer = document.getElementById('ai-chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    autoResizeInput() {
        const input = document.getElementById('ai-chat-input');
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    getCurrentContext() {
        return {
            url: window.location.href,
            page: this.getCurrentPage(),
            server: this.getCurrentServer(),
            user: this.getCurrentUser()
        };
    }

    getCurrentPage() {
        const path = window.location.pathname;
        if (path.includes('/admin')) return 'admin';
        if (path.includes('/server/')) return 'server';
        return 'dashboard';
    }

    getCurrentServer() {
        const match = window.location.pathname.match(/\/server\/([a-f0-9-]+)/);
        return match ? match[1] : null;
    }

    getCurrentUser() {
        // Extract user info from page if available
        return {
            id: window.PterodactylUser?.id || null,
            admin: window.PterodactylUser?.admin || false
        };
    }

    attachServerContext() {
        // Implementation for attaching current server context
        console.log('Attaching server context...');
    }

    copyCode(codeId) {
        const codeElement = document.getElementById(codeId);
        const code = codeElement.textContent;
        
        navigator.clipboard.writeText(code).then(() => {
            this.showNotification('Code copied to clipboard!');
        });
    }

    downloadCode(codeId, language) {
        const codeElement = document.getElementById(codeId);
        const code = codeElement.textContent;
        
        const extensions = {
            'bash': 'sh',
            'python': 'py',
            'php': 'php',
            'javascript': 'js',
            'yaml': 'yml',
            'json': 'json',
            'sql': 'sql'
        };
        
        const extension = extensions[language] || 'txt';
        const filename = `generated-code-${Date.now()}.${extension}`;
        
        const blob = new Blob([code], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        
        URL.revokeObjectURL(url);
        this.showNotification(`Code downloaded as ${filename}`);
    }

    executeCode(codeId) {
        // Implementation for code execution
        console.log('Executing code:', codeId);
        this.showNotification('Code execution feature coming soon!');
    }

    showNotification(message) {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = 'ai-notification';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    generateSessionId() {
        return 'session-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    getAuthToken() {
        // Get CSRF token or API token from page
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    loadChatHistory() {
        // Load recent chat history from localStorage or API
        const saved = localStorage.getItem('ai-chat-history');
        if (saved) {
            try {
                this.messageHistory = JSON.parse(saved);
                // Display recent messages
                this.messageHistory.slice(-5).forEach(msg => {
                    this.displayMessage(msg.content, msg.role, msg.metadata);
                });
            } catch (e) {
                console.error('Failed to load chat history:', e);
            }
        }
    }

    saveChatHistory() {
        // Save chat history to localStorage
        try {
            localStorage.setItem('ai-chat-history', JSON.stringify(this.messageHistory.slice(-50)));
        } catch (e) {
            console.error('Failed to save chat history:', e);
        }
    }
}

// Initialize chat widget when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.aiChat = new AIChatWidget();
});

// Save chat history before page unload
window.addEventListener('beforeunload', () => {
    if (window.aiChat) {
        window.aiChat.saveChatHistory();
    }
});