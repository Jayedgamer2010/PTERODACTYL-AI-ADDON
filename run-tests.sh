#!/usr/bin/env bash

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "Starting Pterodactyl AI Assistant Test Suite"
echo "=========================================="

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to run PHP tests
run_php_tests() {
    echo -e "\n${GREEN}Running PHP Tests...${NC}"
    ./vendor/bin/phpunit --testdox --colors=always
}

# Function to run integration tests
run_integration_tests() {
    echo -e "\n${GREEN}Running Integration Tests...${NC}"
    node test/run-tests.js
}

# Check dependencies
echo "Checking dependencies..."

# Check PHP
if ! command_exists php; then
    echo -e "${RED}PHP is not installed${NC}"
    exit 1
fi

# Check Node.js
if ! command_exists node; then
    echo -e "${RED}Node.js is not installed${NC}"
    exit 1
fi

# Check Composer
if ! command_exists composer; then
    echo -e "${RED}Composer is not installed${NC}"
    exit 1
fi

# Update dependencies
echo "Updating dependencies..."
composer install --prefer-dist --no-interaction
npm install --no-audit

# Create test database
echo "Setting up test environment..."
if [ ! -f "database/database.sqlite" ]; then
    mkdir -p database
    touch database/database.sqlite
fi

# Run migrations for testing
php artisan migrate:fresh --database=sqlite_testing --env=testing

# Run the tests
run_php_tests
PHP_EXIT_CODE=$?

run_integration_tests
NODE_EXIT_CODE=$?

# Generate coverage report
echo -e "\n${GREEN}Generating coverage report...${NC}"
./vendor/bin/phpunit --coverage-html coverage

# Final status
echo -e "\n${GREEN}Test Summary${NC}"
echo "=============="
echo "PHP Tests Exit Code: $PHP_EXIT_CODE"
echo "Integration Tests Exit Code: $NODE_EXIT_CODE"

# Exit with error if any test suite failed
if [ $PHP_EXIT_CODE -ne 0 ] || [ $NODE_EXIT_CODE -ne 0 ]; then
    echo -e "\n${RED}Tests failed!${NC}"
    exit 1
else
    echo -e "\n${GREEN}All tests passed!${NC}"
    exit 0
fi
