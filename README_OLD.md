# Pterodactyl AI Assistant Blueprint Addon

An AI-powered assistant addon for the Pterodactyl Panel, providing intelligent support and automation features.

## Features

- AI-powered chat interface
- Server performance analysis and recommendations
- Command suggestions and automation
- User interaction history tracking
- Metric collection and analysis
- WebSocket support for real-time communication
- Rate limiting and caching support
- Configurable AI provider settings

## Requirements

- PHP 8.2 or higher
- Pterodactyl Panel 1.0 or higher
- Composer
- Node.js and NPM
- OpenAI API key

## Installation

1. Download the latest release
2. Extract to your Pterodactyl installation directory
3. Run the installation script:

```bash
chmod +x install.sh
./install.sh
```

4. Configure your OpenAI API key in the admin panel

## Configuration

The addon can be configured through the admin panel or by editing the `config/ai-assistant.php` file.

### Available Settings

- OpenAI Configuration
  - Model selection
  - Maximum tokens
  - Temperature
- WebSocket Settings
  - Enable/Disable
  - Port configuration
- Rate Limiting
  - Enable/Disable
  - Request limits
- Caching
  - TTL configuration

## Database Structure

The addon uses three main tables:

1. `ai_chat_history`
   - Stores user-AI interactions
   - Links to users and servers
   - Tracks context and responses

2. `ai_metrics`
   - Collects performance metrics
   - Server-specific measurements
   - Time-series data

3. `ai_settings`
   - Stores configuration values
   - System-wide settings
   - Cached preferences

## Security

The addon includes several security features:

- Rate limiting to prevent abuse
- JWT authentication for WebSocket connections
- Input validation and sanitization
- Permission-based access control

## Permissions

Two main permission nodes are available:

- `ai.use` - Allows users to interact with the AI assistant
- `ai.admin` - Grants access to configuration and management features



<br/><h2 align="center">💖 Donate</h2>

Blueprint is free and open-source software. We play a vital role in the Pterodactyl modding community and empower developers with tools to bring their ideas to life. To keep everything up and running, we rely heavily on [donations](https://hcb.hackclub.com/blueprint/donations). We're also nonprofit!

[**Donate to our nonprofit organization**](https://hcb.hackclub.com/donations/start/blueprint) or [view our open finances](https://hcb.hackclub.com/blueprint).


<!-- Contributors -->
<br/><h2 align="center">👥 Contributors</h2>

Contributors help shape the future of the Blueprint modding framework. To start contributing you have to [fork this repository](https://github.com/BlueprintFramework/framework/fork) and [open a pull request](https://github.com/BlueprintFramework/framework/compare).

<a href="https://github.com/BlueprintFramework/framework/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=BlueprintFramework/framework" />
</a>



<!-- Stargazers -->
<br/><h2 align="center">🌟 Stargazers</h2>

<a href="https://github.com/BlueprintFramework/framework/stargazers/">
  <picture>
    <source media="(prefers-color-scheme: light)" srcset="http://reporoster.com/stars/BlueprintFramework/framework">
    <img alt="stargazer-widget" src="http://reporoster.com/stars/dark/BlueprintFramework/framework">
  </picture>
</a>



<!-- Related Links -->
<br/><h2 align="center">🔗 Related Links</h2>

[**Pterodactyl**](https://pterodactyl.io/) is a free, open-source game server management panel built with PHP, React, and Go.\
[**BlueprintFramework/docker**](https://github.com/BlueprintFramework/docker) is the image for running Blueprint and Pterodactyl with Docker.\
[**BlueprintFramework/templates**](https://github.com/BlueprintFramework/templates) is a repository with initialization templates for extension development.\
[**BlueprintFramework/web**](https://github.com/BlueprintFramework/web) is our open-source documentation and landing website.


<br/><br/>
<p align="center">
  © 2023-2025 Emma (prpl.wtf)
  <br/><br/><img src="https://github.com/user-attachments/assets/e6ff62c3-6d99-4e43-850d-62150706e5dd"/>
</p>


