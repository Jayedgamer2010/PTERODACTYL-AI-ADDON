# QueueAI System - Advanced AI Integration for Pterodactyl Panel

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON)
[![Pterodactyl](https://img.shields.io/badge/pterodactyl-1.11.11-green.svg)](https://pterodactyl.io)
[![License](https://img.shields.io/badge/license-MIT-orange.svg)](LICENSE)

A comprehensive AI-powered assistant extension for Pterodactyl Panel that combines intelligent queue management with advanced AI capabilities, code generation, and real-time chat functionality.

## 🚀 Features

### 🤖 AI Integration
- **Multi-Provider Support**: OpenAI, Claude, DeepSeek, Gemini, and Groq
- **Intelligent Chat**: Context-aware conversations with pattern recognition
- **Code Generation**: Safe code generation with validation for Bash, Python, PHP, YAML, and JSON
- **Response Caching**: Optimized responses for common questions

### 📋 Queue Management
- **Position Tracking**: Real-time queue position updates
- **Auto-Refresh**: Automatic status updates every 30 seconds
- **Capacity Management**: Configurable queue limits and overflow handling
- **Wait Time Estimation**: Intelligent wait time calculations

### 🛡️ Security & Performance
- **Rate Limiting**: Comprehensive rate limiting per user and action type
- **Input Validation**: Advanced input sanitization and dangerous pattern detection
- **Code Safety**: Multi-layer code validation to prevent unsafe operations
- **Caching Strategy**: Multi-level caching for optimal performance
- **Database Optimization**: Efficient queries with proper indexing

### 📊 Analytics & Monitoring
- **Usage Statistics**: Detailed user and system analytics
- **Cost Tracking**: AI usage cost monitoring and reporting
- **Performance Metrics**: Response time and system health monitoring
- **Daily Reports**: Automated daily statistics generation

### 🎨 User Experience
- **Responsive Design**: Mobile-friendly interface
- **Real-time Updates**: WebSocket support for live updates
- **Enhanced UX**: Loading states, progress indicators, and user feedback
- **Character Counters**: Input validation with visual feedback
- **Copy to Clipboard**: Modern clipboard API with fallback support

## 📦 Installation

### Prerequisites
- Pterodactyl Panel 1.11.11 or higher
- PHP 8.1 or higher
- MySQL/MariaDB database
- Redis (recommended for caching)

### Blueprint Installation

1. **Download the extension**:
   ```bash
   cd /var/www/pterodactyl
   wget https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/releases/latest/download/QueueAISystem.zip
   ```

2. **Install via Blueprint**:
   ```bash
   blueprint -install QueueAISystem
   ```

3. **Run migrations**:
   ```bash
   php artisan migrate
   ```

4. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

### Manual Installation

1. **Extract files** to your Pterodactyl directory
2. **Run database migrations**:
   ```bash
   php artisan migrate --path=database/migrations/queueai
   ```
3. **Register service provider** in `config/app.php`:
   ```php
   'providers' => [
       // ...
       Pterodactyl\Providers\QueueAIServiceProvider::class,
   ],
   ```

## ⚙️ Configuration

### AI Provider Setup

1. **Access Admin Panel**: Navigate to Admin → QueueAI System
2. **Add AI Provider**: Click "Add AI Provider" and configure:
   - **Provider**: Select from OpenAI, Claude, DeepSeek, Gemini, or Groq
   - **Model Name**: Specify the model (e.g., gpt-4, claude-3-sonnet)
   - **API Key**: Enter your provider's API key
   - **Max Tokens**: Set token limit (100-8000)
   - **Cost per 1K Tokens**: Set pricing for cost tracking

### Environment Variables

Add these to your `.env` file:

```env
# QueueAI Configuration
QUEUEAI_ENABLED=true
QUEUEAI_MAX_QUEUE_SIZE=100
QUEUEAI_CACHE_TTL=300
QUEUEAI_RATE_LIMIT_ENABLED=true

# AI Configuration
QUEUEAI_DEFAULT_PROVIDER=openai
QUEUEAI_ENABLE_CODE_GENERATION=true
QUEUEAI_ENABLE_CACHING=true

# Security
QUEUEAI_ENABLE_SAFETY_CHECKS=true
QUEUEAI_LOG_ALL_REQUESTS=false
```

### Rate Limiting

Default rate limits (per hour):
- **Regular Users**: 30 AI chats, 10 code generations, 25 queue actions
- **Administrators**: 60 AI chats, 20 code generations, 50 queue actions

Customize in `config/queueai.php`:
```php
'rate_limits' => [
    'ai_chat' => ['user' => 30, 'admin' => 60],
    'code_generation' => ['user' => 10, 'admin' => 20],
    'queue_actions' => ['user' => 25, 'admin' => 50],
],
```

## 🔧 API Endpoints

### Queue Management
```http
POST /admin/queueaisystem/queue/join
POST /admin/queueaisystem/queue/leave
GET  /admin/queueaisystem/api/queue/status
```

### AI Features
```http
POST /admin/queueaisystem/ai/chat
POST /admin/queueaisystem/ai/generate-code
GET  /admin/queueaisystem/api/ai/providers
```

### Statistics
```http
GET /admin/queueaisystem/api/stats
```

## 🛠️ Development

### Database Schema

The extension creates the following tables:
- `queues` - Queue management
- `ai_conversations` - Chat history
- `generated_code` - Code generation history
- `ai_configs` - AI provider configurations
- `ai_user_permissions` - User permissions
- `code_executions` - Code execution logs

### Caching Strategy

- **L1 Cache**: In-memory application cache
- **L2 Cache**: Redis/Database cache
- **L3 Cache**: CDN for static assets

Cache keys:
- `queueai_dashboard_user_{id}` - User dashboard data
- `queue_status_user_{id}` - Queue status
- `ai_providers_list` - Available AI providers
- `ai_response_{hash}` - Cached AI responses

### Event System

The extension fires the following events:
- `QueueJoined` - User joins queue
- `QueueLeft` - User leaves queue
- `AIRequestMade` - AI request processed
- `CodeGenerated` - Code generation completed

## 🔒 Security Features

### Input Validation
- Regex pattern matching for dangerous commands
- Character limits and type validation
- SQL injection prevention
- XSS protection

### Code Safety
- Multi-layer validation for generated code
- Blacklist of dangerous functions and commands
- Sandbox execution environment (optional)
- Code review and approval workflow

### Rate Limiting
- Per-user rate limiting
- Action-specific limits
- IP-based limiting
- Exponential backoff

## 📈 Performance Optimization

### Database
- Proper indexing on frequently queried columns
- Query optimization with eager loading
- Connection pooling
- Read/write splitting support

### Caching
- Multi-level caching strategy
- Cache warming on application start
- Intelligent cache invalidation
- Compressed cache storage

### Frontend
- Lazy loading of components
- Debounced input validation
- Optimized asset loading
- Progressive enhancement

## 🐛 Troubleshooting

### Common Issues

**AI Provider Not Working**:
1. Verify API key is correct
2. Check provider status and quotas
3. Review error logs in `storage/logs/laravel.log`

**Queue Not Updating**:
1. Ensure WebSocket server is running
2. Check cache configuration
3. Verify database connectivity

**Performance Issues**:
1. Enable Redis caching
2. Optimize database queries
3. Check server resources

### Debug Mode

Enable debug logging:
```env
QUEUEAI_DEBUG=true
LOG_LEVEL=debug
```

### Log Files
- Application logs: `storage/logs/laravel.log`
- QueueAI logs: `storage/logs/queueai.log`
- Error logs: `storage/logs/queueai-errors.log`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Development Setup
```bash
git clone https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON.git
cd PTERODACTYL-AI-ADDON/QueueAISystem
composer install
npm install
npm run dev
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Pterodactyl Panel team for the excellent foundation
- Blueprint framework for extension support
- AI providers for their powerful APIs
- Community contributors and testers

## 📞 Support

- **GitHub Issues**: [Report bugs and request features](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/issues)
- **Documentation**: [Full documentation](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/wiki)
- **Discord**: [Join our community](https://discord.gg/pterodactyl)

---

**Made with ❤️ for the Pterodactyl community**