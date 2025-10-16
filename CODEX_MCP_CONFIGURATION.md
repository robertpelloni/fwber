# Codex CLI MCP Configuration Guide

## Overview
This document describes the configuration of Codex CLI with MCP (Model Context Protocol) servers, focusing on resolving environment variable whitelist issues.

## Configuration Location
- **Config File**: `C:\Users\hyper\.codex\config.toml`
- **Working Directory**: `C:\Users\hyper\fwber`

## Issue Resolved
**Problem**: MCP servers (especially zen-mcp-server) were experiencing timeout errors due to environment variable whitelisting restrictions.

**Solution**: Added environment variables directly to the MCP server configuration sections and added a global whitelist.

## Current Configuration

### Model Providers
Codex is configured with multiple AI model providers:
- **Google** (Gemini models)
- **OpenRouter** (Multi-model access)
- **Anthropic** (Claude models)
- **xAI** (Grok models)
- **Groq** (Fast inference models)

### MCP Servers Configured

1. **serena** - Custom AI orchestration server
   - Command: `uv run serena start-mcp-server`
   - Context: codex
   - Project: FWBer

2. **sequential-thinking** - Enhanced reasoning server
   - Command: `npx @modelcontextprotocol/server-sequential-thinking`

3. **gemini-mcp-tool** - Gemini integration
   - Command: `npx gemini-mcp-tool`

4. **filesystem** - File system operations
   - Command: `npx @modelcontextprotocol/server-filesystem`
   - Path: `C:\Users\hyper\fwber\`

5. **memory** - Persistent memory management
   - Command: `npx @modelcontextprotocol/server-memory`

6. **everything** - Comprehensive tool access
   - Command: `npx @modelcontextprotocol/server-everything`

7. **puppeteer** - Browser automation
   - Command: `npx puppeteer-mcp-server`

8. **smart-crawler** - Web crawling capabilities
   - Command: `npx mcp-smart-crawler`

9. **playwright** - Advanced browser automation
   - Command: `npx @playwright/mcp@latest`

10. **chrome-devtools** - Chrome debugging protocol
    - Command: `npx chrome-devtools-mcp@latest`

11. **terry** - Additional tool support
    - Command: `npx terry-mcp`

12. **zen-mcp-server** - Multi-model orchestration server
    - Command: `uv run zen-mcp-server`
    - **Environment Variables**:
      - `OPENAI_API_KEY`
      - `OPENROUTER_API_KEY`
      - `GEMINI_API_KEY`

## Environment Variable Configuration

### Per-Server Environment Variables
Environment variables are configured per-server in the `[mcp_servers.<name>.env]` section:

```toml
[mcp_servers.zen-mcp-server.env]
OPENAI_API_KEY = "sk-proj-..."
OPENROUTER_API_KEY = "sk-or-v1-..."
GEMINI_API_KEY = "AIzaSy..."
```

### Global Environment Whitelist
To ensure all MCP servers can access necessary environment variables, a whitelist was added:

```toml
[env_whitelist]
allowed = [
    "OPENAI_API_KEY",
    "OPENROUTER_API_KEY",
    "GEMINI_API_KEY",
    "ANTHROPIC_API_KEY",
    "XAI_API_KEY",
    "GROQ_API_KEY",
    "GOOGLE_API_KEY",
    "POSTGRES_CONNECTION_STRING",
    "PATH",
    "PYTHONPATH",
    "NODE_ENV",
    "HOME",
    "USERPROFILE",
    "TEMP",
    "TMP",
    "IJ_MCP_SERVER_PORT"
]
```

## Timeout Configuration
All MCP servers are configured with a 60-second startup timeout:
```toml
startup_timeout_ms = 60_000
```

## WSL Configuration
Codex acknowledges WSL setup:
```toml
windows_wsl_setup_acknowledged = true
```

This allows Codex to work with both Windows native and WSL environments.

## Testing MCP Server Configuration

### List All MCP Servers
```cmd
codex mcp list
```

### Get Server Details
```cmd
codex mcp get <server-name>
```

Example:
```cmd
codex mcp get zen-mcp-server
```

### Start Interactive Session with MCP Tools
```cmd
codex
```

## Verification Status

✅ All MCP servers are recognized by Codex
✅ Environment variables are properly configured
✅ Startup timeouts are set appropriately
✅ WSL compatibility is acknowledged

## Next Steps

1. **Test MCP Server Connectivity**: Run an interactive Codex session to verify all MCP servers connect successfully
2. **Verify Tool Availability**: Ensure all MCP tools are accessible within Codex sessions
3. **Cross-Tool Integration**: Test that Codex can orchestrate between multiple MCP servers
4. **Configure Additional CLI Tools**: Apply similar configuration to other AI CLI tools (gemini, claude, grok, etc.)

## Troubleshooting

### If MCP Servers Still Timeout:
1. Check that `uv` and `npx` are in your PATH
2. Verify Node.js and Python are properly installed
3. Ensure all API keys are valid and not expired
4. Check firewall settings aren't blocking local connections
5. Review logs in `~/.codex/logs/` for detailed error messages

### If Environment Variables Aren't Being Passed:
1. Verify the environment variable names match exactly
2. Check that API keys don't contain special characters that need escaping
3. Ensure the `[mcp_servers.<name>.env]` section is properly formatted
4. Try setting environment variables at the system level as a backup

## Integration with Other Tools

This configuration should be mirrored across:
- **Cursor**: `C:\Users\hyper\.cursor\mcp.json`
- **Cline**: `C:\Users\hyper\AppData\Roaming\Cursor\User\globalStorage\saoudrizwan.claude-dev\settings\cline_mcp_settings.json`
- **Claude Desktop**: `C:\Users\hyper\AppData\Roaming\Claude\claude_desktop_config.json`
- **Gemini**: `C:\Users\hyper\.gemini\settings.json`
- **Copilot**: `C:\Users\hyper\.copilot\mcp-config.json`
- **Grok**: `C:\Users\hyper\.grok\settings.json`
- **Serena**: `C:\Users\hyper\.serena\serena_config.yml`

## Security Considerations

⚠️ **Important**: The configuration file contains API keys in plain text. Ensure:
- The config file has appropriate permissions (user-only read/write)
- The file is not committed to version control
- API keys are rotated regularly
- Use environment variables or secrets management for production deployments
