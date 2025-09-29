# QueueAI System - Installation Guide

## 📦 Package Information

**File:** `QueueAISystem.blueprint`  
**Size:** 68KB  
**Version:** 1.0.0  
**Compatibility:** Pterodactyl Panel 1.11.11+  
**Framework:** Blueprint  

## 🚀 Quick Installation

### Method 1: Blueprint Installation (Recommended)

1. **Download the package:**
   ```bash
   wget https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/releases/latest/download/QueueAISystem.blueprint
   ```

2. **Install via Blueprint:**
   ```bash
   cd /var/www/pterodactyl
   blueprint -install QueueAISystem.blueprint
   ```

3. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

### Method 2: Manual Installation

1. **Extract the package:**
   ```bash
   unzip QueueAISystem.blueprint -d /tmp/queueai
   cd /tmp/queueai/QueueAISystem
   ```

2. **Run the installation script:**
   ```bash
   chmod +x install.sh
   sudo ./install.sh
   ```

## ⚙️ Post-Installation Setup

### 1. Configure AI Providers

1. **Access Admin Panel:**
   - Navigate to your Pterodactyl admin panel
   - Go to **Admin → QueueAI System**

2. **Add AI Provider:**
   - Click "Add AI Provider"
   - Select provider (OpenAI, Claude, DeepSeek, Gemini, or Groq)
   - Enter your API key
   - Configure model settings
   - Set cost per 1K tokens

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# QueueAI System Configuration
QUEUEAI_ENABLED=true
QUEUEAI_MAX_QUEUE_SIZE=100
QUEUEAI_CACHE_TTL=300
QUEUEAI_RATE_LIMIT_ENABLED=true

# AI Configuration
QUEUEAI_DEFAULT_PROVIDER=openai
QUEUEAI_ENABLE_CODE_GENERATION=true
QUEUEAI_ENABLE_CACHING=true

# Security Settings
QUEUEAI_ENABLE_SAFETY_CHECKS=true
QUEUEAI_LOG_ALL_REQUESTS=false
```

### 3. Database Setup

The installation automatically runs migrations, but you can verify:

```bash
cd /var/www/pterodactyl
php artisan migrate:status
```

### 4. Permissions Setup

Ensure proper file permissions:

```bash
chown -R www-data:www-data /var/www/pterodactyl
chmod -R 755 /var/www/pterodactyl
chmod -R 775 /var/www/pterodactyl/storage
chmod -R 775 /var/www/pterodactyl/bootstrap/cache
```

## 🔧 Configuration Options

### Rate Limiting

Customize rate limits in `config/queueai.php`:

```php
'rate_limits' => [
    'ai_chat' => [
        'user' => 30,  // Regular users: 30 requests/hour
        'admin' => 60, // Admins: 60 requests/hour
    ],
    'code_generation' => [
        'user' => 10,  // Regular users: 10 requests/hour
        'admin' => 20, // Admins: 20 requests/hour
    ],
    'queue_actions' => [
        'user' => 25,  // Regular users: 25 requests/hour
        'admin' => 50, // Admins: 50 requests/hour
    ],
],
```

### Queue Settings

```php
'queue' => [
    'max_size' => 100,        // Maximum queue size
    'auto_cleanup' => true,   // Automatic cleanup
    'cleanup_interval' => 3600, // Cleanup every hour
],
```

### AI Settings

```php
'ai' => [
    'default_provider' => 'openai',
    'enable_caching' => true,
    'cache_ttl' => 300,       // 5 minutes
    'enable_code_generation' => true,
],
```

## 🛡️ Security Configuration

### API Key Management

1. **Secure Storage:** API keys are encrypted using Laravel's encryption
2. **Environment Variables:** Store sensitive data in `.env` file
3. **File Permissions:** Ensure `.env` is not web-accessible

### Rate Limiting

The system includes comprehensive rate limiting:
- Per-user limits based on role
- Per-action type limits
- IP-based limiting for additional security

### Input Validation

All inputs are validated for:
- Length limits
- Character patterns
- Dangerous command detection
- Spam prevention

## 📊 Monitoring & Analytics

### Usage Statistics

Access detailed analytics at **Admin → QueueAI System**:
- AI conversation counts
- Code generation statistics
- Queue usage metrics
- Cost tracking per user

### Log Files

Monitor system logs:
- Application logs: `storage/logs/laravel.log`
- QueueAI logs: `storage/logs/queueai.log`
- Error logs: `storage/logs/queueai-errors.log`

## 🔄 Maintenance

### Automated Tasks

The system includes automated maintenance:
- Daily cleanup of old conversations (30+ days)
- Daily cleanup of old generated code (7+ days)
- Queue position updates every 5 minutes
- Cache optimization every hour
- Daily statistics generation

### Manual Maintenance

```bash
# Clear all caches
php artisan cache:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run queue cleanup
php artisan queueai:cleanup

# Generate statistics
php artisan queueai:stats
```

## 🐛 Troubleshooting

### Common Issues

**1. AI Provider Not Working**
```bash
# Check API key configuration
php artisan config:show queueai

# Test API connection
php artisan queueai:test-provider openai
```

**2. Queue Not Updating**
```bash
# Check database connection
php artisan migrate:status

# Clear queue cache
php artisan cache:forget queueai_*
```

**3. Permission Errors**
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/pterodactyl
sudo chmod -R 755 /var/www/pterodactyl
```

**4. High Memory Usage**
```bash
# Enable Redis caching
# Add to .env:
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Debug Mode

Enable debug logging:
```env
QUEUEAI_DEBUG=true
LOG_LEVEL=debug
```

### Performance Issues

1. **Enable Redis:** Use Redis for caching and sessions
2. **Database Optimization:** Ensure proper indexing
3. **PHP Configuration:** Increase memory_limit and max_execution_time
4. **Web Server:** Configure proper caching headers

## 📞 Support

### Documentation
- **Full Documentation:** [GitHub Wiki](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/wiki)
- **API Reference:** [API Documentation](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/blob/main/API.md)

### Community Support
- **GitHub Issues:** [Report Issues](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/issues)
- **Discord:** [Join Community](https://discord.gg/pterodactyl)
- **Forum:** [Pterodactyl Community](https://community.pterodactyl.io)

### Professional Support
For enterprise support and custom development:
- **Email:** support@queueai.dev
- **Website:** https://queueai.dev

## 🔄 Updates

### Checking for Updates
```bash
blueprint -version QueueAISystem
```

### Updating
```bash
# Download new version
wget https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/releases/latest/download/QueueAISystem.blueprint

# Update via Blueprint
blueprint -update QueueAISystem.blueprint
```

### Backup Before Updates
```bash
# Create backup
php artisan backup:run

# Or manual backup
mysqldump -u username -p database_name > backup.sql
cp -r /var/www/pterodactyl /backup/pterodactyl-$(date +%Y%m%d)
```

## ✅ Verification

After installation, verify everything is working:

1. **Access Dashboard:** Admin → QueueAI System
2. **Test Queue:** Join and leave queue
3. **Test AI Chat:** Send a test message
4. **Test Code Generation:** Generate a simple script
5. **Check Logs:** Verify no errors in logs

## 🎉 You're Ready!

Your QueueAI System is now installed and ready to use. The system provides:

- ✅ Advanced AI integration with multiple providers
- ✅ Intelligent queue management system
- ✅ Secure code generation with safety validation
- ✅ Real-time updates and modern UI
- ✅ Comprehensive security and performance optimization

Enjoy your enhanced Pterodactyl Panel experience!

---

**Installation completed successfully!** 🚀

For questions or support, visit our [GitHub repository](https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON) or join our community Discord.