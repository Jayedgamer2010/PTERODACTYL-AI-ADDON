# QueueAI System - Final Installation Guide

## 🚀 Complete AI-Powered Queue System for Pterodactyl Panel

### Overview
QueueAI System is a comprehensive Blueprint extension that combines queue management with AI-powered assistance. This production-ready extension provides intelligent code generation, real-time chat support, and advanced queue management in one unified system.

---

## 📦 Installation Steps

### 1. Download & Install
```bash
# Navigate to your Pterodactyl directory
cd /var/www/pterodactyl

# Install the extension using Blueprint
blueprint -install QueueAISystem.blueprint
```

### 2. Run Database Migrations
```bash
# Run migrations to create all required tables
php artisan migrate
```

### 3. Clear Cache
```bash
# Clear all caches
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```

### 4. Set Permissions
```bash
# Ensure proper file permissions
chown -R www-data:www-data /var/www/pterodactyl
chmod -R 755 /var/www/pterodactyl
```

---

## 🎯 Accessing the System

After installation, access the QueueAI System at:
- **Admin Panel**: `/admin/queueaisystem`
- **Navigation**: Admin Panel → QueueAI System

---

## ✨ Features Overview

### 🔄 Queue Management
- **Join/Leave Queue**: Simple queue system with position tracking
- **Real-time Updates**: Automatic position updates
- **Status Monitoring**: View total users in queue

### 🤖 AI Assistant
- **Multi-Provider Support**: OpenAI, Claude, DeepSeek, Gemini, Groq
- **Intelligent Chat**: Context-aware responses
- **Code Generation**: Smart script and config generation
- **Safety Validation**: Built-in security checks

### 🛡️ Security Features
- **Role-Based Access**: Different capabilities for users vs admins
- **Encrypted Storage**: Secure API key storage
- **Audit Logging**: Complete activity tracking
- **Permission System**: Granular access controls

---

## ⚙️ Configuration

### For Regular Users
1. **Access Dashboard**: Navigate to `/admin/queueaisystem`
2. **Join Queue**: Click "Join Queue" to enter the queue system
3. **Use AI Chat**: Type questions in the AI chat section
4. **Generate Code**: Request scripts and configurations

### For Administrators
1. **Add AI Providers**: Click "Add Provider" to configure AI services
2. **Monitor Usage**: View statistics and recent activity
3. **Manage Permissions**: Control user access to AI features
4. **Review Generated Code**: Monitor all AI-generated content

---

## 🔧 AI Provider Setup

### OpenAI Configuration
```
Provider: openai
Model: gpt-4o
API Key: sk-your-openai-key
Max Tokens: 4000
Cost/1K Tokens: 0.03
```

### Claude Configuration
```
Provider: claude
Model: claude-3-sonnet-20240229
API Key: sk-ant-your-claude-key
Max Tokens: 4000
Cost/1K Tokens: 0.015
```

### DeepSeek Configuration
```
Provider: deepseek
Model: deepseek-coder
API Key: your-deepseek-key
Max Tokens: 4000
Cost/1K Tokens: 0.002
```

---

## 📊 Database Tables Created

The extension creates 6 essential tables:

1. **queues** - Queue management and positions
2. **ai_conversations** - Chat history and context
3. **generated_code** - All AI-generated code
4. **code_executions** - Code execution logs
5. **ai_configs** - AI provider configurations
6. **ai_user_permissions** - User permission management

---

## 🎮 Usage Examples

### Queue System
- Join queue for priority support
- Monitor your position in real-time
- Leave queue when no longer needed

### AI Chat Examples
```
"Help me optimize my Minecraft server"
"Create a backup script for my server"
"Generate EssentialsX configuration"
"Troubleshoot high CPU usage"
```

### Code Generation Examples
```
Request: "Create a daily backup script"
Language: bash
Result: Complete backup script with error handling

Request: "Generate server.properties for Minecraft"
Language: yaml
Result: Optimized server configuration
```

---

## 🔒 Security & Permissions

### User Roles & Capabilities

**Regular Users:**
- ✅ Basic AI chat assistance
- ✅ Simple code generation
- ✅ Queue management
- ❌ System-level operations

**Administrators:**
- ✅ All user capabilities
- ✅ AI provider configuration
- ✅ Advanced code generation
- ✅ System monitoring
- ✅ Permission management

---

## 🚨 Troubleshooting

### Common Issues

**Extension Not Loading**
```bash
# Check Blueprint installation
blueprint -v

# Verify file permissions
ls -la /var/www/pterodactyl/public/extensions/
```

**Database Errors**
```bash
# Run migrations again
php artisan migrate

# Check database connection
php artisan tinker
DB::connection()->getPdo();
```

**AI Features Not Working**
1. Verify AI provider API keys are correct
2. Check provider status in admin panel
3. Ensure user has proper permissions
4. Review error logs: `/var/www/pterodactyl/storage/logs/`

---

## 📈 Performance Tips

### Optimization
- Configure appropriate rate limits for your user base
- Monitor AI provider costs regularly
- Use caching for frequently requested code templates
- Regular database maintenance for conversation logs

### Scaling
- Consider load balancing for high-traffic installations
- Implement Redis caching for better performance
- Monitor server resources during peak usage
- Set up log rotation for AI conversation logs

---

## 🔄 Updates & Maintenance

### Regular Maintenance
```bash
# Clear old conversation logs (monthly)
php artisan tinker
DB::table('ai_conversations')->where('created_at', '<', now()->subDays(30))->delete();

# Update AI provider configurations as needed
# Monitor costs and usage patterns
# Review generated code for quality and safety
```

### Backup Important Data
```bash
# Backup AI configurations
mysqldump -u username -p database_name ai_configs > ai_configs_backup.sql

# Backup user permissions
mysqldump -u username -p database_name ai_user_permissions > permissions_backup.sql
```

---

## 🆘 Support & Resources

### Getting Help
- **Documentation**: This installation guide
- **Logs**: Check `/var/www/pterodactyl/storage/logs/laravel.log`
- **Database**: Verify table creation and data integrity
- **Permissions**: Ensure proper file and user permissions

### Best Practices
- Start with one AI provider and expand gradually
- Monitor costs closely, especially with premium providers
- Regularly review generated code for security
- Keep API keys secure and rotate them periodically
- Set appropriate rate limits to prevent abuse

---

## 📋 Version Information

- **Extension Version**: 1.0.0
- **Pterodactyl Compatibility**: v1.11.11+
- **PHP Requirements**: 8.2+
- **Database**: MySQL 8.0+ / MariaDB 10.3+

---

## ✅ Installation Checklist

- [ ] Downloaded QueueAISystem.blueprint
- [ ] Installed via Blueprint CLI
- [ ] Ran database migrations successfully
- [ ] Cleared all caches
- [ ] Set proper file permissions
- [ ] Accessed admin dashboard
- [ ] Configured at least one AI provider
- [ ] Tested queue functionality
- [ ] Tested AI chat feature
- [ ] Tested code generation
- [ ] Reviewed security settings

---

**🎉 Installation Complete!**

Your QueueAI System is now ready for production use. Users can access the unified dashboard to manage queues and interact with AI assistance, while administrators have full control over providers, permissions, and monitoring.

**Author**: Jayed Sheikh  
**Repository**: https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON  
**Support**: Check logs and documentation for troubleshooting