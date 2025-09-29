#!/bin/bash

# QueueAI System Installation Script
# Compatible with Pterodactyl Panel 1.11.11+

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PTERODACTYL_PATH="/var/www/pterodactyl"
EXTENSION_NAME="QueueAISystem"
BACKUP_DIR="/tmp/queueai_backup_$(date +%Y%m%d_%H%M%S)"

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    print_status "Checking system requirements..."
    
    # Check if running as root or with sudo
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
    
    # Check if Pterodactyl directory exists
    if [[ ! -d "$PTERODACTYL_PATH" ]]; then
        print_error "Pterodactyl Panel not found at $PTERODACTYL_PATH"
        print_status "Please ensure Pterodactyl Panel is installed and the path is correct"
        exit 1
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "0")
    if [[ $(echo "$PHP_VERSION" | cut -d. -f1) -lt 8 ]]; then
        print_error "PHP 8.1 or higher is required. Current version: $PHP_VERSION"
        exit 1
    fi
    
    # Check if Blueprint is installed
    if ! command -v blueprint &> /dev/null; then
        print_warning "Blueprint not found. Installing Blueprint..."
        install_blueprint
    fi
    
    # Check database connection
    cd "$PTERODACTYL_PATH"
    if ! php artisan migrate:status &> /dev/null; then
        print_error "Cannot connect to database. Please check your database configuration"
        exit 1
    fi
    
    print_success "All requirements met"
}

install_blueprint() {
    print_status "Installing Blueprint..."
    
    cd /tmp
    wget -q https://github.com/BlueprintFramework/framework/releases/latest/download/blueprint.zip
    unzip -q blueprint.zip
    chmod +x blueprint.sh
    ./blueprint.sh
    
    print_success "Blueprint installed successfully"
}

create_backup() {
    print_status "Creating backup..."
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if command -v mysqldump &> /dev/null; then
        DB_NAME=$(grep DB_DATABASE "$PTERODACTYL_PATH/.env" | cut -d '=' -f2)
        DB_USER=$(grep DB_USERNAME "$PTERODACTYL_PATH/.env" | cut -d '=' -f2)
        DB_PASS=$(grep DB_PASSWORD "$PTERODACTYL_PATH/.env" | cut -d '=' -f2)
        
        if [[ -n "$DB_NAME" && -n "$DB_USER" ]]; then
            mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database_backup.sql" 2>/dev/null || true
        fi
    fi
    
    # Backup important files
    cp -r "$PTERODACTYL_PATH/app" "$BACKUP_DIR/" 2>/dev/null || true
    cp -r "$PTERODACTYL_PATH/resources" "$BACKUP_DIR/" 2>/dev/null || true
    cp "$PTERODACTYL_PATH/.env" "$BACKUP_DIR/" 2>/dev/null || true
    
    print_success "Backup created at $BACKUP_DIR"
}

install_extension() {
    print_status "Installing QueueAI System extension..."
    
    cd "$PTERODACTYL_PATH"
    
    # Install via Blueprint if available
    if command -v blueprint &> /dev/null; then
        print_status "Installing via Blueprint..."
        blueprint -install "$EXTENSION_NAME"
    else
        print_status "Installing manually..."
        manual_install
    fi
    
    print_success "Extension installed successfully"
}

manual_install() {
    print_status "Performing manual installation..."
    
    # Copy files
    cp -r app/Http/Controllers/QueueAIController.php "$PTERODACTYL_PATH/app/Http/Controllers/Admin/"
    cp -r app/Http/Middleware/ValidateQueueAIRequest.php "$PTERODACTYL_PATH/app/Http/Middleware/"
    cp -r app/Providers/QueueAIServiceProvider.php "$PTERODACTYL_PATH/app/Providers/"
    cp -r app/Services/ "$PTERODACTYL_PATH/app/"
    cp -r resources/views/ "$PTERODACTYL_PATH/resources/"
    cp -r database/migrations/ "$PTERODACTYL_PATH/database/"
    
    # Register service provider
    if ! grep -q "QueueAIServiceProvider" "$PTERODACTYL_PATH/config/app.php"; then
        sed -i "/App\\\\Providers\\\\RouteServiceProvider::class,/a\\        Pterodactyl\\\\Providers\\\\QueueAIServiceProvider::class," "$PTERODACTYL_PATH/config/app.php"
    fi
}

run_migrations() {
    print_status "Running database migrations..."
    
    cd "$PTERODACTYL_PATH"
    php artisan migrate --force
    
    print_success "Database migrations completed"
}

setup_permissions() {
    print_status "Setting up file permissions..."
    
    # Set ownership
    chown -R www-data:www-data "$PTERODACTYL_PATH"
    
    # Set permissions
    find "$PTERODACTYL_PATH" -type f -exec chmod 644 {} \;
    find "$PTERODACTYL_PATH" -type d -exec chmod 755 {} \;
    
    # Special permissions for storage and cache
    chmod -R 775 "$PTERODACTYL_PATH/storage"
    chmod -R 775 "$PTERODACTYL_PATH/bootstrap/cache"
    
    print_success "Permissions set successfully"
}

clear_cache() {
    print_status "Clearing application cache..."
    
    cd "$PTERODACTYL_PATH"
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    php artisan route:clear
    
    # Optimize for production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    print_success "Cache cleared and optimized"
}

setup_scheduler() {
    print_status "Setting up task scheduler..."
    
    # Add cron job for Laravel scheduler
    CRON_JOB="* * * * * cd $PTERODACTYL_PATH && php artisan schedule:run >> /dev/null 2>&1"
    
    if ! crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
        (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
        print_success "Scheduler configured"
    else
        print_status "Scheduler already configured"
    fi
}

create_config() {
    print_status "Creating configuration file..."
    
    cat > "$PTERODACTYL_PATH/config/queueai.php" << 'EOF'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QueueAI System Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('QUEUEAI_ENABLED', true),
    
    'queue' => [
        'max_size' => env('QUEUEAI_MAX_QUEUE_SIZE', 100),
        'auto_cleanup' => env('QUEUEAI_AUTO_CLEANUP', true),
        'cleanup_interval' => env('QUEUEAI_CLEANUP_INTERVAL', 3600), // 1 hour
    ],
    
    'ai' => [
        'default_provider' => env('QUEUEAI_DEFAULT_PROVIDER', 'openai'),
        'enable_caching' => env('QUEUEAI_ENABLE_CACHING', true),
        'cache_ttl' => env('QUEUEAI_CACHE_TTL', 300), // 5 minutes
        'enable_code_generation' => env('QUEUEAI_ENABLE_CODE_GENERATION', true),
    ],
    
    'rate_limits' => [
        'ai_chat' => [
            'user' => env('QUEUEAI_RATE_LIMIT_AI_CHAT_USER', 30),
            'admin' => env('QUEUEAI_RATE_LIMIT_AI_CHAT_ADMIN', 60),
        ],
        'code_generation' => [
            'user' => env('QUEUEAI_RATE_LIMIT_CODE_USER', 10),
            'admin' => env('QUEUEAI_RATE_LIMIT_CODE_ADMIN', 20),
        ],
        'queue_actions' => [
            'user' => env('QUEUEAI_RATE_LIMIT_QUEUE_USER', 25),
            'admin' => env('QUEUEAI_RATE_LIMIT_QUEUE_ADMIN', 50),
        ],
    ],
    
    'security' => [
        'enable_safety_checks' => env('QUEUEAI_ENABLE_SAFETY_CHECKS', true),
        'log_all_requests' => env('QUEUEAI_LOG_ALL_REQUESTS', false),
        'enable_code_validation' => env('QUEUEAI_ENABLE_CODE_VALIDATION', true),
    ],
    
    'performance' => [
        'enable_query_optimization' => env('QUEUEAI_ENABLE_QUERY_OPTIMIZATION', true),
        'enable_response_compression' => env('QUEUEAI_ENABLE_RESPONSE_COMPRESSION', true),
        'max_response_time' => env('QUEUEAI_MAX_RESPONSE_TIME', 30), // seconds
    ],
];
EOF

    print_success "Configuration file created"
}

update_env() {
    print_status "Updating environment configuration..."
    
    ENV_FILE="$PTERODACTYL_PATH/.env"
    
    # Add QueueAI configuration if not exists
    if ! grep -q "QUEUEAI_ENABLED" "$ENV_FILE"; then
        cat >> "$ENV_FILE" << 'EOF'

# QueueAI System Configuration
QUEUEAI_ENABLED=true
QUEUEAI_MAX_QUEUE_SIZE=100
QUEUEAI_CACHE_TTL=300
QUEUEAI_RATE_LIMIT_ENABLED=true
QUEUEAI_DEFAULT_PROVIDER=openai
QUEUEAI_ENABLE_CODE_GENERATION=true
QUEUEAI_ENABLE_CACHING=true
QUEUEAI_ENABLE_SAFETY_CHECKS=true
QUEUEAI_LOG_ALL_REQUESTS=false
EOF
        print_success "Environment configuration updated"
    else
        print_status "Environment configuration already exists"
    fi
}

run_tests() {
    print_status "Running system tests..."
    
    cd "$PTERODACTYL_PATH"
    
    # Test database connection
    if php artisan migrate:status &> /dev/null; then
        print_success "Database connection: OK"
    else
        print_error "Database connection: FAILED"
        return 1
    fi
    
    # Test cache
    if php artisan cache:clear &> /dev/null; then
        print_success "Cache system: OK"
    else
        print_error "Cache system: FAILED"
        return 1
    fi
    
    # Test routes
    if php artisan route:list | grep -q "queueaisystem" &> /dev/null; then
        print_success "Routes registration: OK"
    else
        print_error "Routes registration: FAILED"
        return 1
    fi
    
    print_success "All tests passed"
}

show_completion_message() {
    echo
    echo "=========================================="
    echo -e "${GREEN}QueueAI System Installation Complete!${NC}"
    echo "=========================================="
    echo
    echo "Next steps:"
    echo "1. Access your Pterodactyl admin panel"
    echo "2. Navigate to Admin → QueueAI System"
    echo "3. Configure your AI providers"
    echo "4. Test the system functionality"
    echo
    echo "Important files:"
    echo "- Configuration: $PTERODACTYL_PATH/config/queueai.php"
    echo "- Environment: $PTERODACTYL_PATH/.env"
    echo "- Backup: $BACKUP_DIR"
    echo
    echo "For support and documentation:"
    echo "- GitHub: https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON"
    echo "- Issues: https://github.com/Jayedgamer2010/PTERODACTYL-AI-ADDON/issues"
    echo
    echo -e "${YELLOW}Remember to configure your AI provider API keys!${NC}"
    echo
}

# Main installation process
main() {
    echo "=========================================="
    echo "QueueAI System Installation Script"
    echo "=========================================="
    echo
    
    check_requirements
    create_backup
    install_extension
    run_migrations
    create_config
    update_env
    setup_permissions
    clear_cache
    setup_scheduler
    
    if run_tests; then
        show_completion_message
    else
        print_error "Installation completed with errors. Please check the logs."
        exit 1
    fi
}

# Run main function
main "$@"