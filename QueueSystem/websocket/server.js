/**
 * WebSocket Server for AI Chat Widget
 * Handles real-time communication between frontend and AI services
 */

const WebSocket = require('ws');
const http = require('http');
const url = require('url');
const jwt = require('jsonwebtoken');
const mysql = require('mysql2/promise');
const axios = require('axios');

class AIChatWebSocketServer {
    constructor(port = 8080) {
        this.port = port;
        this.clients = new Map();
        this.aiProviders = new Map();
        this.rateLimiter = new Map();
        
        this.initializeServer();
        this.loadAIProviders();
    }

    initializeServer() {
        // Create HTTP server
        this.server = http.createServer();
        
        // Create WebSocket server
        this.wss = new WebSocket.Server({
            server: this.server,
            path: '/ws/ai-chat'
        });

        this.wss.on('connection', (ws, request) => {
            this.handleConnection(ws, request);
        });

        this.server.listen(this.port, () => {
            console.log(`AI Chat WebSocket server running on port ${this.port}`);
        });
    }

    async handleConnection(ws, request) {
        const clientId = this.generateClientId();
        const clientInfo = {
            id: clientId,
            ws: ws,
            authenticated: false,
            userId: null,
            sessionId: null,
            lastActivity: Date.now()
        };

        this.clients.set(clientId, clientInfo);
        console.log(`Client ${clientId} connected`);

        ws.on('message', async (data) => {
            try {
                const message = JSON.parse(data.toString());
                await this.handleMessage(clientId, message);
            } catch (error) {
                console.error('Error handling message:', error);
                this.sendError(clientId, 'Invalid message format');
            }
        });

        ws.on('close', () => {
            console.log(`Client ${clientId} disconnected`);
            this.clients.delete(clientId);
        });

        ws.on('error', (error) => {
            console.error(`WebSocket error for client ${clientId}:`, error);
            this.clients.delete(clientId);
        });

        // Send welcome message
        this.sendMessage(clientId, {
            type: 'status',
            message: 'Connected to AI Chat server',
            model: 'GPT-4',
            provider: 'OpenAI'
        });
    }

    async handleMessage(clientId, message) {
        const client = this.clients.get(clientId);
        if (!client) return;

        client.lastActivity = Date.now();

        switch (message.type) {
            case 'auth':
                await this.handleAuth(clientId, message);
                break;
            case 'message':
                await this.handleChatMessage(clientId, message);
                break;
            case 'code_request':
                await this.handleCodeRequest(clientId, message);
                break;
            case 'ping':
                this.sendMessage(clientId, { type: 'pong' });
                break;
            default:
                this.sendError(clientId, 'Unknown message type');
        }
    }

    async handleAuth(clientId, message) {
        const client = this.clients.get(clientId);
        if (!client) return;

        try {
            // Verify token (implement your authentication logic)
            const user = await this.verifyToken(message.token);
            
            if (user) {
                client.authenticated = true;
                client.userId = user.id;
                client.sessionId = message.sessionId;
                
                this.sendMessage(clientId, {
                    type: 'auth_success',
                    user: {
                        id: user.id,
                        name: user.name,
                        admin: user.root_admin
                    }
                });

                console.log(`Client ${clientId} authenticated as user ${user.id}`);
            } else {
                this.sendError(clientId, 'Authentication failed');
            }
        } catch (error) {
            console.error('Auth error:', error);
            this.sendError(clientId, 'Authentication error');
        }
    }

    async handleChatMessage(clientId, message) {
        const client = this.clients.get(clientId);
        if (!client || !client.authenticated) {
            this.sendError(clientId, 'Not authenticated');
            return;
        }

        // Check rate limiting
        if (!this.checkRateLimit(client.userId)) {
            this.sendError(clientId, 'Rate limit exceeded');
            return;
        }

        try {
            // Store message in database
            await this.storeMessage(client.userId, client.sessionId, 'user', message.content);

            // Send typing indicator
            this.sendMessage(clientId, { type: 'typing' });

            // Get AI response
            const aiResponse = await this.getAIResponse(
                message.content,
                client.userId,
                message.context || {}
            );

            // Store AI response
            await this.storeMessage(
                client.userId,
                client.sessionId,
                'assistant',
                aiResponse.content,
                {
                    tokens: aiResponse.tokens_used,
                    cost: aiResponse.cost,
                    model: aiResponse.model,
                    provider: aiResponse.provider
                }
            );

            // Check if response contains code
            if (this.containsCode(aiResponse.content)) {
                const codeBlocks = this.extractCodeBlocks(aiResponse.content);
                
                for (const block of codeBlocks) {
                    this.sendMessage(clientId, {
                        type: 'code',
                        code: block.code,
                        language: block.language,
                        explanation: block.explanation || aiResponse.content
                    });
                }
            } else {
                this.sendMessage(clientId, {
                    type: 'message',
                    content: aiResponse.content,
                    metadata: {
                        tokens: aiResponse.tokens_used,
                        cost: aiResponse.cost,
                        model: aiResponse.model,
                        provider: aiResponse.provider
                    }
                });
            }

        } catch (error) {
            console.error('Error processing chat message:', error);
            this.sendError(clientId, 'Failed to process message');
        }
    }

    async handleCodeRequest(clientId, message) {
        const client = this.clients.get(clientId);
        if (!client || !client.authenticated) {
            this.sendError(clientId, 'Not authenticated');
            return;
        }

        try {
            // Send typing indicator
            this.sendMessage(clientId, { type: 'typing' });

            // Generate code using specialized AI provider
            const codeResponse = await this.generateCode(
                message.request,
                client.userId,
                message.context || {}
            );

            // Store generated code
            await this.storeGeneratedCode(
                client.userId,
                codeResponse.code,
                codeResponse.language,
                codeResponse.explanation,
                message.request
            );

            this.sendMessage(clientId, {
                type: 'code',
                code: codeResponse.code,
                language: codeResponse.language,
                explanation: codeResponse.explanation,
                safety_score: codeResponse.safety_score,
                metadata: {
                    tokens: codeResponse.tokens_used,
                    cost: codeResponse.cost,
                    model: codeResponse.model,
                    provider: codeResponse.provider
                }
            });

        } catch (error) {
            console.error('Error generating code:', error);
            this.sendError(clientId, 'Failed to generate code');
        }
    }

    async getAIResponse(prompt, userId, context) {
        // Get user permissions and context
        const userContext = await this.getUserContext(userId);
        const fullContext = { ...context, ...userContext };

        // Select appropriate AI provider
        const provider = await this.selectAIProvider('chat', fullContext);

        // Make API request to AI provider
        return await provider.generateResponse(prompt, fullContext);
    }

    async generateCode(request, userId, context) {
        // Get user permissions and context
        const userContext = await this.getUserContext(userId);
        const fullContext = { ...context, ...userContext };

        // Select code generation provider
        const provider = await this.selectAIProvider('code', fullContext);

        // Generate code
        return await provider.generateCode(request, fullContext);
    }

    async selectAIProvider(type, context) {
        // Implementation for selecting best available AI provider
        // This would check database for active providers, costs, etc.
        
        // For now, return a mock provider
        return {
            generateResponse: async (prompt, context) => {
                return {
                    content: `Mock AI response to: ${prompt}`,
                    tokens_used: 50,
                    cost: 0.001,
                    model: 'gpt-4',
                    provider: 'openai'
                };
            },
            generateCode: async (request, context) => {
                return {
                    code: `#!/bin/bash\n# Generated script for: ${request}\necho "Hello World"`,
                    language: 'bash',
                    explanation: `This script was generated for your request: ${request}`,
                    safety_score: { score: 95, level: 'safe', warnings: [] },
                    tokens_used: 75,
                    cost: 0.0015,
                    model: 'deepseek-coder',
                    provider: 'deepseek'
                };
            }
        };
    }

    containsCode(content) {
        return /```[\s\S]*?```/.test(content);
    }

    extractCodeBlocks(content) {
        const codeBlockRegex = /```(\w+)?\n([\s\S]*?)\n```/g;
        const blocks = [];
        let match;

        while ((match = codeBlockRegex.exec(content)) !== null) {
            blocks.push({
                language: match[1] || 'text',
                code: match[2],
                explanation: content.replace(match[0], '').trim()
            });
        }

        return blocks;
    }

    checkRateLimit(userId) {
        const now = Date.now();
        const userLimits = this.rateLimiter.get(userId) || { requests: [], lastReset: now };

        // Clean old requests (older than 1 minute)
        userLimits.requests = userLimits.requests.filter(time => now - time < 60000);

        // Check if under limit (60 requests per minute)
        if (userLimits.requests.length >= 60) {
            return false;
        }

        // Add current request
        userLimits.requests.push(now);
        this.rateLimiter.set(userId, userLimits);

        return true;
    }

    async verifyToken(token) {
        // Implement token verification logic
        // This should verify the CSRF token or API token from Pterodactyl
        
        // Mock implementation
        return {
            id: 1,
            name: 'Test User',
            root_admin: false
        };
    }

    async getUserContext(userId) {
        // Get user information and permissions from database
        // Mock implementation
        return {
            user_id: userId,
            is_admin: false,
            permissions: [],
            servers: []
        };
    }

    async storeMessage(userId, sessionId, role, content, metadata = {}) {
        // Store message in database
        console.log(`Storing message: ${role} - ${content.substring(0, 50)}...`);
    }

    async storeGeneratedCode(userId, code, language, explanation, request) {
        // Store generated code in database
        console.log(`Storing generated code: ${language} - ${code.substring(0, 50)}...`);
    }

    sendMessage(clientId, message) {
        const client = this.clients.get(clientId);
        if (client && client.ws.readyState === WebSocket.OPEN) {
            client.ws.send(JSON.stringify(message));
        }
    }

    sendError(clientId, error) {
        this.sendMessage(clientId, {
            type: 'error',
            message: error
        });
    }

    generateClientId() {
        return 'client-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    async loadAIProviders() {
        // Load AI provider configurations from database
        console.log('Loading AI providers...');
    }

    // Cleanup inactive connections
    startCleanupTimer() {
        setInterval(() => {
            const now = Date.now();
            const timeout = 5 * 60 * 1000; // 5 minutes

            for (const [clientId, client] of this.clients.entries()) {
                if (now - client.lastActivity > timeout) {
                    console.log(`Cleaning up inactive client ${clientId}`);
                    client.ws.close();
                    this.clients.delete(clientId);
                }
            }
        }, 60000); // Check every minute
    }
}

// Start the server
const server = new AIChatWebSocketServer(process.env.WS_PORT || 8080);
server.startCleanupTimer();

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('Shutting down WebSocket server...');
    server.wss.close(() => {
        server.server.close(() => {
            process.exit(0);
        });
    });
});

module.exports = AIChatWebSocketServer;