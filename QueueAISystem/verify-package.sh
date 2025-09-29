#!/bin/bash

# QueueAI System Package Verification Script
# Verifies the integrity and completeness of the extension package

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[✓]${NC} $1"
    ((PASSED_CHECKS++))
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
    ((FAILED_CHECKS++))
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

check_file() {
    ((TOTAL_CHECKS++))
    if [[ -f "$1" ]]; then
        print_success "File exists: $1"
        return 0
    else
        print_error "Missing file: $1"
        return 1
    fi
}

check_directory() {
    ((TOTAL_CHECKS++))
    if [[ -d "$1" ]]; then
        print_success "Directory exists: $1"
        return 0
    else
        print_error "Missing directory: $1"
        return 1
    fi
}

check_php_syntax() {
    ((TOTAL_CHECKS++))
    if php -l "$1" &> /dev/null; then
        print_success "PHP syntax valid: $1"
        return 0
    else
        print_error "PHP syntax error: $1"
        return 1
    fi
}

check_yaml_syntax() {
    ((TOTAL_CHECKS++))
    if command -v yamllint &> /dev/null; then
        if yamllint "$1" &> /dev/null; then
            print_success "YAML syntax valid: $1"
            return 0
        else
            print_error "YAML syntax error: $1"
            return 1
        fi
    else
        print_warning "yamllint not available, skipping YAML validation for: $1"
        ((TOTAL_CHECKS--))
        return 0
    fi
}

check_file_permissions() {
    ((TOTAL_CHECKS++))
    local file="$1"
    local expected_perm="$2"
    local actual_perm=$(stat -c "%a" "$file" 2>/dev/null || echo "000")
    
    if [[ "$actual_perm" == "$expected_perm" ]]; then
        print_success "Correct permissions ($expected_perm): $file"
        return 0
    else
        print_error "Wrong permissions ($actual_perm, expected $expected_perm): $file"
        return 1
    fi
}

verify_core_files() {
    print_status "Verifying core files..."
    
    # Configuration
    check_file "conf.yml"
    check_yaml_syntax "conf.yml"
    
    # Documentation
    check_file "README.md"
    check_file "install.sh"
    
    # Controller
    check_file "app/Http/Controllers/QueueAIController.php"
    check_php_syntax "app/Http/Controllers/QueueAIController.php"
    
    # Middleware
    check_file "app/Http/Middleware/ValidateQueueAIRequest.php"
    check_php_syntax "app/Http/Middleware/ValidateQueueAIRequest.php"
    
    # Service Provider
    check_file "app/Providers/QueueAIServiceProvider.php"
    check_php_syntax "app/Providers/QueueAIServiceProvider.php"
    
    # Services
    check_file "app/Services/QueueAI/CacheService.php"
    check_php_syntax "app/Services/QueueAI/CacheService.php"
    
    # Routes
    check_file "routes/web.php"
    check_php_syntax "routes/web.php"
    
    # Views
    check_file "resources/views/admin/dashboard.blade.php"
    
    # Migrations
    check_directory "database/migrations"
}

verify_migrations() {
    print_status "Verifying database migrations..."
    
    local migration_files=(
        "2025_01_01_000000_create_queues_table.php"
        "2025_01_01_000001_create_ai_conversations_table.php"
        "2025_01_01_000002_create_generated_code_table.php"
        "2025_01_01_000003_create_code_executions_table.php"
        "2025_01_01_000004_create_ai_configs_table.php"
        "2025_01_01_000005_create_ai_user_permissions_table.php"
    )
    
    for migration in "${migration_files[@]}"; do
        check_file "database/migrations/$migration"
        check_php_syntax "database/migrations/$migration"
    done
}

verify_structure() {
    print_status "Verifying directory structure..."
    
    local required_dirs=(
        "app"
        "app/Http"
        "app/Http/Controllers"
        "app/Http/Middleware"
        "app/Providers"
        "app/Services"
        "app/Services/QueueAI"
        "database"
        "database/migrations"
        "resources"
        "resources/views"
        "resources/views/admin"
        "routes"
    )
    
    for dir in "${required_dirs[@]}"; do
        check_directory "$dir"
    done
}

verify_configuration() {
    print_status "Verifying configuration..."
    
    # Check conf.yml content
    if [[ -f "conf.yml" ]]; then
        ((TOTAL_CHECKS++))
        if grep -q "QueueAI System" conf.yml; then
            print_success "Configuration contains correct name"
        else
            print_error "Configuration missing correct name"
        fi
        
        ((TOTAL_CHECKS++))
        if grep -q "1.0.0" conf.yml; then
            print_success "Configuration contains version"
        else
            print_error "Configuration missing version"
        fi
        
        ((TOTAL_CHECKS++))
        if grep -q "admin:" conf.yml; then
            print_success "Configuration contains admin section"
        else
            print_error "Configuration missing admin section"
        fi
        
        ((TOTAL_CHECKS++))
        if grep -q "database:" conf.yml; then
            print_success "Configuration contains database section"
        else
            print_error "Configuration missing database section"
        fi
    fi
}

verify_code_quality() {
    print_status "Verifying code quality..."
    
    # Check for common issues in PHP files
    local php_files=(
        "app/Http/Controllers/QueueAIController.php"
        "app/Http/Middleware/ValidateQueueAIRequest.php"
        "app/Providers/QueueAIServiceProvider.php"
        "app/Services/QueueAI/CacheService.php"
        "routes/web.php"
    )
    
    for file in "${php_files[@]}"; do
        if [[ -f "$file" ]]; then
            ((TOTAL_CHECKS++))
            if grep -q "<?php" "$file"; then
                print_success "PHP opening tag present: $file"
            else
                print_error "Missing PHP opening tag: $file"
            fi
            
            ((TOTAL_CHECKS++))
            if grep -q "namespace" "$file"; then
                print_success "Namespace declared: $file"
            else
                print_error "Missing namespace: $file"
            fi
        fi
    done
}

verify_security() {
    print_status "Verifying security measures..."
    
    # Check for potential security issues
    local files_to_check=(
        "app/Http/Controllers/QueueAIController.php"
        "app/Http/Middleware/ValidateQueueAIRequest.php"
    )
    
    for file in "${files_to_check[@]}"; do
        if [[ -f "$file" ]]; then
            ((TOTAL_CHECKS++))
            if grep -q "Auth::" "$file"; then
                print_success "Authentication checks present: $file"
            else
                print_error "Missing authentication checks: $file"
            fi
            
            ((TOTAL_CHECKS++))
            if grep -q "validate\|Validator::" "$file"; then
                print_success "Input validation present: $file"
            else
                print_error "Missing input validation: $file"
            fi
        fi
    done
}

verify_performance() {
    print_status "Verifying performance optimizations..."
    
    # Check for caching implementation
    ((TOTAL_CHECKS++))
    if grep -q "Cache::" "app/Http/Controllers/QueueAIController.php"; then
        print_success "Caching implementation found"
    else
        print_error "Missing caching implementation"
    fi
    
    # Check for database optimization
    ((TOTAL_CHECKS++))
    if grep -q "lockForUpdate\|beginTransaction" "app/Http/Controllers/QueueAIController.php"; then
        print_success "Database optimization found"
    else
        print_error "Missing database optimization"
    fi
    
    # Check for rate limiting
    ((TOTAL_CHECKS++))
    if grep -q "throttle\|RateLimiter" "routes/web.php"; then
        print_success "Rate limiting implementation found"
    else
        print_error "Missing rate limiting implementation"
    fi
}

calculate_package_size() {
    print_status "Calculating package size..."
    
    local total_size=$(du -sh . | cut -f1)
    local file_count=$(find . -type f | wc -l)
    
    print_status "Package size: $total_size"
    print_status "Total files: $file_count"
    
    # Check if package is reasonable size (should be under 10MB)
    local size_bytes=$(du -sb . | cut -f1)
    ((TOTAL_CHECKS++))
    if [[ $size_bytes -lt 10485760 ]]; then  # 10MB in bytes
        print_success "Package size is reasonable ($total_size)"
    else
        print_error "Package size is too large ($total_size)"
    fi
}

generate_report() {
    echo
    echo "=========================================="
    echo "QueueAI System Package Verification Report"
    echo "=========================================="
    echo
    echo "Total checks performed: $TOTAL_CHECKS"
    echo -e "Passed: ${GREEN}$PASSED_CHECKS${NC}"
    echo -e "Failed: ${RED}$FAILED_CHECKS${NC}"
    echo
    
    local success_rate=$((PASSED_CHECKS * 100 / TOTAL_CHECKS))
    echo "Success rate: $success_rate%"
    echo
    
    if [[ $FAILED_CHECKS -eq 0 ]]; then
        echo -e "${GREEN}✓ Package verification PASSED${NC}"
        echo "The QueueAI System package is ready for distribution!"
        return 0
    else
        echo -e "${RED}✗ Package verification FAILED${NC}"
        echo "Please fix the issues above before distributing the package."
        return 1
    fi
}

# Main verification process
main() {
    echo "=========================================="
    echo "QueueAI System Package Verification"
    echo "=========================================="
    echo
    
    # Change to script directory
    cd "$(dirname "$0")"
    
    verify_structure
    verify_core_files
    verify_migrations
    verify_configuration
    verify_code_quality
    verify_security
    verify_performance
    calculate_package_size
    
    generate_report
}

# Run main function
main "$@"