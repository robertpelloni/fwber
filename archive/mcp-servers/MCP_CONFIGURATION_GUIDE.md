# MCP Server Configuration Guide

## Overview
This guide provides step-by-step instructions for configuring all MCP servers across different AI tools and platforms.

## Current MCP Server Status

### ✅ Working MCP Servers
- **Zen MCP Server** - Multi-model orchestration and consensus building
- **Serena MCP Server** - Memory management and code context
- **Chroma Knowledge Server** - Vector database for semantic search
- **Sequential Thinking** - Structured reasoning and problem-solving
- **Filesystem** - File system access and manipulation
- **Memory** - Basic memory operations
- **Everything** - Comprehensive tool access

### ⚠️ Configuration Issues
- **Codex MCP Server** - May need API key configuration
- **Gemini MCP Tool** - May need API key configuration

## Configuration Files

### 1. Claude Desktop (`C:\Users\hyper\AppData\Roaming\Claude\claude_desktop_config.json`)
```json
{
  "mcpServers": {
    "serena": {
      "command": "uv",
      "args": ["run", "--directory", "C:\\Users\\hyper\\serena\\", "serena", "start-mcp-server", "--context", "desktop-app", "--project", "C:\\Users\\hyper\\fwber\\"]
    },
    "zen-mcp-server": {
      "command": "uv",
      "args": ["run", "--directory", "C:\\Users\\hyper\\zen-mcp-server\\", "zen-mcp-server"],
      "env": {
        "OPENAI_API_KEY": "your-openai-key",
        "OPENROUTER_API_KEY": "your-openrouter-key",
        "GEMINI_API_KEY": "your-gemini-key"
      }
    },
    "chroma-knowledge": {
      "command": "C:\\Users\\hyper\\chroma-env-312\\Scripts\\chroma-mcp-server.exe",
      "args": ["--mode", "stdio", "--client-type", "http", "--host", "localhost", "--port", "8000", "--ssl", "false"]
    }
  }
}
```

### 2. Cursor IDE (`C:\Users\hyper\.cursor\mcp.json`)
```json
{
  "mcpServers": {
    "serena": {
      "command": "uv",
      "args": ["run", "--directory", "C:\\Users\\hyper\\serena\\", "serena", "start-mcp-server", "--context", "cursor-ide", "--project", "C:\\Users\\hyper\\fwber\\"]
    },
    "zen-mcp-server": {
      "command": "uv",
      "args": ["run", "--directory", "C:\\Users\\hyper\\zen-mcp-server\\", "zen-mcp-server"],
      "env": {
        "OPENAI_API_KEY": "your-openai-key",
        "OPENROUTER_API_KEY": "your-openrouter-key",
        "GEMINI_API_KEY": "your-gemini-key"
      }
    },
    "chroma-knowledge": {
      "command": "C:\\Users\\hyper\\chroma-env-312\\Scripts\\chroma-mcp-server.exe",
      "args": ["--mode", "stdio", "--client-type", "http", "--host", "localhost", "--port", "8000", "--ssl", "false"]
    }
  }
}
```

### 3. Claude CLI (`C:\Users\hyper\.claude.json`)
```json
{
  "mcpServers": {
    "serena": {
      "command": "uv",
      "args": ["run", "--directory", "C:\\Users\\hyper\\serena\\", "serena", "start-mcp-server", "--context", "claude-cli", "--project", "C:\\Users\\hyper\\fwber\\"]
    },
    "zen-mcp-server": {
      "command": "uv",
      "args": ["run", "--directory", "C:\\Users\\hyper\\zen-mcp-server\\", "zen-mcp-server"],
      "env": {
        "OPENAI_API_KEY": "your-openai-key",
        "OPENROUTER_API_KEY": "your-openrouter-key",
        "GEMINI_API_KEY": "your-gemini-key"
      }
    },
    "chroma-knowledge": {
      "command": "C:\\Users\\hyper\\chroma-env-312\\Scripts\\chroma-mcp-server.exe",
      "args": ["--mode", "stdio", "--client-type", "http", "--host", "localhost", "--port", "8000", "--ssl", "false"]
    }
  }
}
```

## Prerequisites

### 1. Python Environment
- **Chroma Environment**: `C:\Users\hyper\chroma-env-312\`
- **Activation**: `C:\Users\hyper\chroma-env-312\Scripts\activate`
- **Chroma Server**: Must be running on `localhost:8000`

### 2. UV Package Manager
- **Serena**: `C:\Users\hyper\serena\`
- **Zen**: `C:\Users\hyper\zen-mcp-server\`

### 3. API Keys Required
- **OpenAI API Key**: For GPT models
- **OpenRouter API Key**: For multiple model access
- **Gemini API Key**: For Google models

## Testing MCP Servers

### 1. Test Chroma Server
```bash
# Start Chroma server
cd C:\Users\hyper\chroma-env-312
Scripts\activate
chroma run --host localhost --port 8000

# Test MCP server
chroma-mcp-server --mode stdio --client-type http --host localhost --port 8000 --ssl false
```

### 2. Test Serena Server
```bash
cd C:\Users\hyper\serena
uv run serena start-mcp-server --context test --project C:\Users\hyper\fwber
```

### 3. Test Zen Server
```bash
cd C:\Users\hyper\zen-mcp-server
uv run zen-mcp-server
```

## Troubleshooting

### Common Issues

#### 1. Chroma Server Not Running
- **Error**: `Could not connect to a Chroma server`
- **Solution**: Start Chroma server with `chroma run --host localhost --port 8000`

#### 2. SSL/TLS Errors
- **Error**: `SSL: WRONG_VERSION_NUMBER`
- **Solution**: Use `--ssl false` flag in chroma-mcp-server command

#### 3. Python Environment Issues
- **Error**: `ModuleNotFoundError`
- **Solution**: Ensure you're using the correct Python environment path

#### 4. API Key Issues
- **Error**: Authentication failures
- **Solution**: Verify API keys are correctly set in environment variables

### Debug Commands

#### Check Chroma Server Status
```bash
curl http://localhost:8000/api/v1/heartbeat
```

#### Test MCP Server Connection
```bash
# Test chroma-mcp-server
C:\Users\hyper\chroma-env-312\Scripts\chroma-mcp-server.exe --help

# Test serena
cd C:\Users\hyper\serena && uv run serena --help

# Test zen
cd C:\Users\hyper\zen-mcp-server && uv run zen-mcp-server --help
```

## Configuration Validation

### 1. Validate JSON Syntax
```bash
# Check Claude Desktop config
python -m json.tool "C:\Users\hyper\AppData\Roaming\Claude\claude_desktop_config.json"

# Check Cursor config
python -m json.tool "C:\Users\hyper\.cursor\mcp.json"
```

### 2. Test MCP Server Startup
```bash
# Test each server individually
# Chroma
C:\Users\hyper\chroma-env-312\Scripts\chroma-mcp-server.exe --mode stdio --client-type http --host localhost --port 8000 --ssl false

# Serena
cd C:\Users\hyper\serena && uv run serena start-mcp-server --context test --project C:\Users\hyper\fwber

# Zen
cd C:\Users\hyper\zen-mcp-server && uv run zen-mcp-server
```

## Next Steps

1. **Start Chroma Server**: Ensure it's running on localhost:8000
2. **Test Each MCP Server**: Verify individual functionality
3. **Configure AI Tools**: Update configuration files
4. **Test Integration**: Verify MCP servers work with AI tools
5. **Monitor Performance**: Check for any issues or errors

## Support

For issues with MCP server configuration:
1. Check the troubleshooting section above
2. Verify all prerequisites are met
3. Test each server individually
4. Check logs for specific error messages
5. Consult the individual MCP server documentation
