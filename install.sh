#!/bin/bash

echo "Installing Pterodactyl AI Assistant..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if [[ $(echo "$PHP_VERSION 8.2" | awk '{if ($1 < $2) print "1"}') == "1" ]]; then
    echo "Error: PHP version 8.2 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

# Ensure we're in the correct directory
if [ ! -d "app/BlueprintFramework/Extensions" ]; then
    echo "Error: Please run this script from the root of your Pterodactyl installation"
    exit 1
fi

# Create necessary directories
echo "Creating directories..."
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/app/Models
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/app/Repositories
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/app/Contracts/Repositories
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/app/Providers
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/database/migrations
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/database/seeders
mkdir -p app/BlueprintFramework/Extensions/AIAssistant/config

# Copy files
echo "Copying files..."
cp -r app/BlueprintFramework/Extensions/AIAssistant/* app/BlueprintFramework/Extensions/AIAssistant/

# Install dependencies
echo "Installing dependencies..."
composer require openai-php/client:^0.16
composer require ratchet/pawl:^0.4.3
composer require react/socket:^1.16
composer require firebase/php-jwt:^6.11
composer require predis/predis:^2.4

# Run migrations and seeders
echo "Running migrations and seeders..."
php artisan migrate --path=app/BlueprintFramework/Extensions/AIAssistant/database/migrations
php artisan db:seed --class=\\Blueprint\\Extensions\\AIAssistant\\Database\\Seeders\\AIAssistantSeeder

# Clear cache
echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Publishing assets and config
echo "Publishing assets and config..."
php artisan vendor:publish --tag=ai-assistant-config
php artisan vendor:publish --tag=ai-assistant-assets

echo "Installation complete! Please configure your AI providers in the admin panel."

# Check if installation was successful
if [ $? -eq 0 ]; then
    echo "Success: Pterodactyl AI Assistant has been installed successfully!"
    echo "Please configure your OpenAI API key in the admin panel."
else
    echo "Error: Installation failed. Please check the error messages above."
    exit 1
fi
