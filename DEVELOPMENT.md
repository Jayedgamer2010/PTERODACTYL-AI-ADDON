# Blueprint Package Structure

This document outlines the structure of the Pterodactyl AI Assistant Blueprint addon for developers who want to contribute or understand the codebase.

## Directory Structure

```
app/BlueprintFramework/Extensions/AIAssistant/
├── app/
│   ├── Models/
│   │   ├── AIChatHistory.php
│   │   ├── AIMetric.php
│   │   └── AISetting.php
│   ├── Repositories/
│   │   ├── ChatHistoryRepository.php
│   │   ├── MetricsRepository.php
│   │   └── SettingsRepository.php
│   ├── Contracts/
│   │   └── Repositories/
│   │       ├── ChatHistoryRepositoryInterface.php
│   │       ├── MetricsRepositoryInterface.php
│   │       └── SettingsRepositoryInterface.php
│   └── Providers/
│       ├── AIAssistantServiceProvider.php
│       └── AssetsServiceProvider.php
├── config/
│   └── ai-assistant.php
├── database/
│   ├── migrations/
│   │   └── 2025_09_11_000001_create_ai_assistant_tables.php
│   └── seeders/
│       └── AIAssistantSeeder.php
├── resources/
│   ├── assets/
│   ├── views/
│   └── lang/
├── routes/
│   └── web.php
├── blueprint.json
└── install.sh
```

## Component Overview

### Models

- `AIChatHistory`: Stores chat interactions between users and the AI
- `AIMetric`: Records performance and usage metrics
- `AISetting`: Manages configuration settings

### Repositories

- `ChatHistoryRepository`: Handles chat history data operations
- `MetricsRepository`: Manages metric collection and retrieval
- `SettingsRepository`: Handles system settings

### Service Providers

- `AIAssistantServiceProvider`: Main service provider for the addon
- `AssetsServiceProvider`: Handles asset and config publishing

### Database

- Migrations create the necessary tables
- Seeder provides initial configuration values

## Blueprint Configuration

The `blueprint.json` file contains:

```json
{
    "name": "pterodactyl-ai-assistant",
    "version": "1.0.0",
    "type": "blueprint-extension",
    "autoload": {
        "psr-4": {
            "Blueprint\\Extensions\\AIAssistant\\": "app/"
        }
    },
    "extra": {
        "blueprint": {
            "setup": {
                "migration": true,
                "seeder": true,
                "config": "ai-assistant.php",
                "providers": [
                    "Blueprint\\Extensions\\AIAssistant\\Providers\\AIAssistantServiceProvider"
                ]
            }
        }
    }
}
```

## Development Guidelines

1. Follow PSR-4 autoloading standards
2. Maintain interface segregation
3. Use dependency injection
4. Write comprehensive docblocks
5. Follow Laravel best practices
