# AI Assistant with Queue System - Complete Installation Guide

## Overview
AI Assistant is a comprehensive Blueprint extension for Pterodactyl Panel that provides advanced AI-powered assistance with intelligent code generation, queue management, and automation capabilities. This enterprise-grade solution integrates multiple AI providers and offers real-time chat support with sophisticated safety mechanisms.

## Prerequisites

1. **Pterodactyl Panel** v1.11.7 or higher
2. **Blueprint Framework** installed on your panel
3. **Admin access** to your Pterodactyl panel

## Installation Steps

### 1. Download the Extension
Download the `AIAssistant-v2.0.0.blueprint` file from this repository.

### 2. Install via Blueprint CLI
```bash
# Navigate to your Pterodactyl directory
cd /var/www/pterodactyl

# Install the extension using Blueprint
blueprint -install AIAssistant-v2.0.0.blueprint
```

### 3. Run Database Migrations
```bash
# Run the migrations to create all AI tables
php artisan migrate
```

### 4. Install WebSocket Dependencies
```bash
# Navigate to WebSocket directory
cd /var/www/pterodactyl/public/extensions/aiassistant/websocket

# Install Node.js dependencies
npm install

# Start WebSocket server with PM2
pm2 start server.js --name "ai-assistant-ws"
pm2 save
pm2 startup
```

### 5. Configure AI Providers
1. Access Admin Panel → AI Assistant → Configuration
2. Add your AI provider API keys (OpenAI, Claude, etc.)
3. Configure system prompts and security settings
4. Test provider connections

### 6. Clear Cache (Recommended)
```bash
# Clear application cache
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```

## Accessing the AI Assistant

After installation, the AI Assistant will be available:
- **Floating Chat Widget**: Available on all panel pages (bottom-right corner)
- **Admin Configuration**: `/admin/ai/config`
- **Queue Management**: `/admin/queuesystem`

## Core Features

### 🤖 AI Integration
✅ **Multi-Provider Support** - OpenAI, Claude, DeepSeek, Gemini, Groq, Ollama  
✅ **Intelligent Routing** - Automatic provider selection based on cost/availability  
✅ **Real-time Chat** - WebSocket-powered floating chat widget  
✅ **Context Awareness** - Understands your server setup and user permissions  

### 🔧 Code Generation
✅ **Smart Code Generation** - Context-aware script and config generation  
✅ **Multi-Language Support** - Bash, Python, PHP, JavaScript, YAML, JSON, SQL  
✅ **Safety Validation** - Comprehensive security scanning and approval workflows  
✅ **Sandbox Execution** - Safe code testing in isolated Docker containers  
✅ **Template System** - Reusable code templates and snippets  

### 🛡️ Security & Permissions
✅ **Role-Based Access** - Granular permissions for different user types  
✅ **Rate Limiting** - Configurable limits per user role  
✅ **Audit Logging** - Complete activity tracking and monitoring  
✅ **Cost Controls** - Daily/monthly spending limits and monitoring  

### 📊 Administration
✅ **Provider Management** - Easy AI provider configuration and testing  
✅ **Analytics Dashboard** - Usage statistics and cost monitoring  
✅ **Security Settings** - Comprehensive safety and approval controls  
✅ **User Management** - Permission assignment and monitoring  

### 🎯 Queue System (Legacy)
✅ **Queue Management** - User queue system with position tracking  
✅ **Real-time Updates** - Live position updates and notifications  

## File Structure

```
AIAssistant/
├── conf.yml                                    # Blueprint configuration
├── database/migrations/                        # Database schema files
│   ├── 2025_01_01_000000_create_queues_table.php
│   ├── 2025_01_02_000000_create_ai_conversations_table.php
│   ├── 2025_01_02_000001_create_generated_code_table.php
│   ├── 2025_01_02_000002_create_code_executions_table.php
│   ├── 2025_01_02_000003_create_ai_configs_table.php
│   ├── 2025_01_02_000004_create_code_templates_table.php
│   ├── 2025_01_02_000005_create_ai_user_permissions_table.php
│   └── 2025_01_02_000006_create_ai_permission_logs_table.php
├── app/
│   ├── Http/Controllers/
│   │   ├── QueueController.php                 # Queue management
│   │   └── AIConfigController.php              # AI configuration
│   ├── Models/
│   │   └── Queue.php                           # Queue model
│   └── Services/AI/                            # AI service layer
│       ├── AIProviderService.php               # Multi-provider integration
│       ├── CodeGenerationService.php           # Code generation engine
│       ├── PermissionService.php               # Permission management
│       └── SandboxService.php                  # Code execution sandbox
├── routes/
│   └── web.php                                 # Route definitions
├── resources/
│   ├── views/
│   │   ├── queue.blade.php                     # Queue interface
│   │   └── admin/ai/config.blade.php           # AI admin panel
│   └── assets/
│       ├── css/ai-chat-widget.css              # Chat widget styles
│       └── js/ai-chat-widget.js                # Chat widget functionality
└── websocket/
    ├── server.js                               # WebSocket server
    └── package.json                            # Node.js dependencies
```

## Database Schema

The extension creates 8 comprehensive tables:

### Core AI Tables
- **ai_conversations** - Chat history and context
- **generated_code** - All generated code with versions and safety scores
- **code_executions** - Execution logs and results
- **ai_configs** - AI provider settings and API keys
- **code_templates** - Reusable code templates

### Permission & Security Tables
- **ai_user_permissions** - Granular user permissions
- **ai_permission_logs** - Complete audit trail

### Legacy Queue Table
- **queues** - Original queue management system

## Usage Guide

### For Regular Users
1. **Access Chat Widget**: Click the floating AI button (bottom-right)
2. **Ask Questions**: Get help with server optimization, troubleshooting
3. **Generate Code**: Request scripts, configs, and automation tools
4. **Execute Safely**: Test generated code in sandbox environment

### For Administrators
1. **Configure Providers**: Add AI provider API keys in admin panel
2. **Set Permissions**: Assign AI capabilities to different user roles
3. **Monitor Usage**: Track costs, usage patterns, and performance
4. **Manage Security**: Configure approval workflows and safety settings

### AI Capabilities by User Role

#### Regular Users
- ✅ Basic AI chat assistance
- ✅ Simple code generation (configs, basic scripts)
- ❌ System-level operations
- ❌ Dangerous commands

#### Moderators
- ✅ Advanced code generation
- ✅ Server management scripts
- ✅ File operations
- ❌ System administration
- ❌ Database operations

#### Administrators
- ✅ Full code generation capabilities
- ✅ System-level commands
- ✅ Database operations
- ✅ Security configurations
- ✅ Cost and usage monitoring

#### Super Administrators
- ✅ All AI features
- ✅ Dangerous operations (with approval)
- ✅ AI provider configuration
- ✅ Permission management
- ✅ Complete system access

## Troubleshooting

### Extension Not Loading
- Ensure Blueprint is properly installed
- Check file permissions: `chown -R www-data:www-data /var/www/pterodactyl`
- Verify all files are in correct locations

### Database Errors
- Run migrations: `php artisan migrate`
- Check database connection in `.env`
- Ensure proper database permissions

### Route Not Found
- Clear route cache: `php artisan route:clear`
- Verify web server configuration
- Check Blueprint installation logs

### Permission Denied
- Ensure user has admin privileges
- Check middleware configuration
- Verify authentication is working

## Configuration

### AI Provider Setup
1. **OpenAI**: Get API key from https://platform.openai.com/api-keys
2. **Claude**: Get API key from https://console.anthropic.com/
3. **DeepSeek**: Get API key from https://platform.deepseek.com/
4. **Gemini**: Get API key from https://makersuite.google.com/app/apikey
5. **Groq**: Get API key from https://console.groq.com/keys
6. **Ollama**: Set up local Ollama server

### WebSocket Configuration
```bash
# Configure WebSocket port (default: 8080)
export WS_PORT=8080

# Configure database connection
export DB_HOST=localhost
export DB_DATABASE=pterodactyl
export DB_USERNAME=pterodactyl
export DB_PASSWORD=your_password
```

### Security Settings
- **Require Admin Approval**: For dangerous code generation
- **Enable Sandbox**: Safe code execution environment
- **Rate Limiting**: Requests per hour by user role
- **Cost Limits**: Daily/monthly spending controls

## Uninstallation

To remove the extension:

```bash
# Stop WebSocket server
pm2 stop ai-assistant-ws
pm2 delete ai-assistant-ws

# Remove via Blueprint
blueprint -remove aiassistant

# Optional: Remove all database tables
php artisan migrate:rollback --step=8
```

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify your Pterodactyl and Blueprint versions
3. Check server logs for detailed error messages
4. Ensure all prerequisites are met

## Advanced Features

### Code Generation Examples
```bash
# Server optimization
"Optimize my Minecraft server for 8GB RAM"

# Backup automation
"Create a backup script that runs daily at 2 AM"

# Plugin configuration
"Generate EssentialsX config for survival server"

# Monitoring setup
"Create a health check script for my server"
```

### Template System
- Create reusable code templates
- Share templates with community
- Version control for templates
- Usage analytics and ratings

### Sandbox Execution
- Docker-based isolation
- Resource limits (CPU, memory, time)
- Network isolation
- Read-only file system

## Version Compatibility

- **Pterodactyl Panel**: v1.11.11+
- **Blueprint Framework**: Latest version
- **PHP**: 8.2+
- **Laravel**: 10.x+
- **Node.js**: 16.x+ (for WebSocket server)
- **Docker**: 20.x+ (for sandbox execution)

## Performance & Scaling

### Recommended Server Specs
- **CPU**: 4+ cores
- **RAM**: 8GB+ (4GB for panel + 4GB for AI services)
- **Storage**: SSD recommended for database performance
- **Network**: Stable internet for AI provider APIs

### Optimization Tips
- Use Redis for caching AI responses
- Configure rate limiting appropriately
- Monitor AI provider costs regularly
- Use local Ollama for cost-effective solutions

## Security Considerations

- Store API keys securely (encrypted in database)
- Enable audit logging for all AI operations
- Configure appropriate user permissions
- Monitor for unusual usage patterns
- Regular security updates and patches

## Support & Community

- **GitHub Issues**: Report bugs and feature requests
- **Documentation**: Comprehensive guides and examples
- **Community**: Discord server for support and discussions
- **Updates**: Regular feature updates and security patches

## License

This extension is open-source software licensed under MIT License.

---

**Author**: Jayed Sheikh  
**Version**: 2.0.0  
**Last Updated**: 2025-01-02  
**Repository**: https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON