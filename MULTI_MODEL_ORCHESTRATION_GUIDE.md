# Multi-Model AI Orchestration Guide
**Version**: 2.0.0  
**Date**: 2025-10-18  
**Project**: FWBer Development Environment

---

## Overview

This guide documents the unified configuration for orchestrating multiple AI models (Claude 4.5, GPT-5-Codex, Gemini 2.5, Grok, etc.) across various CLI tools and IDEs using Model Context Protocol (MCP) servers.

**Key Achievement**: Fixed Codex MCP timeout issues by using global npm executables instead of npx, eliminating stdio handshake failures.

---

## Core MCP Servers (Enabled)

### 1. filesystem - File Operations
- **Package**: `@modelcontextprotocol/server-filesystem`
- **Command**: `mcp-server-filesystem C:\Users\mrgen\fwber\`
- **Purpose**: Read, write, list, search files within project
- **Status**: ✅ Essential - enabled in ALL configs

### 2. sequential-thinking - Structured Reasoning
- **Package**: `@modelcontextprotocol/server-sequential-thinking`
- **Command**: `mcp-server-sequential-thinking stdio`
- **Purpose**: Step-by-step problem solving with revision
- **Status**: ✅ Highly useful - enabled in ALL configs

### 3. serena - Memory & Symbol Analysis
- **Package**: Custom (via uv)
- **Command**: `uv run --directory %USERPROFILE%\serena\ serena start-mcp-server --context <cli> --project C:\Users\mrgen\fwber\`
- **Purpose**: Context memory, symbol tracking, large project navigation
- **Status**: ✅ Very useful - enabled in ALL configs

### 4. gemini-mcp - Gemini Integration
- **Package**: `gemini-mcp`
- **Command**: `gemini-mcp stdio`
- **Purpose**: Cross-model consultation with Gemini 2.5
- **Status**: ✅ Useful - enabled in non-Gemini CLIs
- **Requires**: GEMINI_API_KEY

### 5. codex-mcp-server - OpenAI Integration
- **Package**: `codex-mcp-server`
- **Command**: `codex-mcp-server stdio`
- **Purpose**: Cross-model consultation with GPT-5/GPT-5-Codex
- **Status**: ✅ Useful - enabled in non-Codex CLIs
- **Requires**: OPENAI_API_KEY

---

## Configuration Files

### Codex CLI
- **Location**: `%USERPROFILE%\.codex\config.toml`
- **Template**: [codex_config_clean.toml](codex_config_clean.toml:1)
- **Servers**: filesystem, sequential-thinking, serena, gemini-mcp (4 total)
- **Security**: env_allowlist, filesystem_roots restricted

### Cursor IDE
- **Location**: `%USERPROFILE%\.cursor\mcp.json`
- **Template**: [tools_config_files/cursor_mcp_clean.json](tools_config_files/cursor_mcp_clean.json:1)
- **Servers**: filesystem, sequential-thinking, serena, gemini-mcp, codex-mcp-server (5 total)

### Claude CLI
- **Location**: `%USERPROFILE%\.claude.json`
- **Template**: [tools_config_files/claude_mcp_template.json](tools_config_files/claude_mcp_template.json:1)
- **Servers**: filesystem, sequential-thinking, serena, codex-mcp-server, gemini-mcp (5 total)

### Gemini CLI
- **Location**: `%USERPROFILE%\.gemini\settings.json`
- **Template**: [tools_config_files/gemini_mcp_template.json](tools_config_files/gemini_mcp_template.json:1)
- **Servers**: filesystem, sequential-thinking, serena, codex-mcp-server (4 total)

### GitHub Copilot CLI
- **Location**: `%USERPROFILE%\.copilot\mcp-config.json`
- **Template**: [tools_config_files/copilot_mcp_template.json](tools_config_files/copilot_mcp_template.json:1)
- **Servers**: filesystem, sequential-thinking, serena, codex-mcp-server, gemini-mcp (5 total)

---

## Environment Variables Required

Set these via Windows Environment Variables (System Properties → Advanced → Environment Variables):

```
OPENAI_API_KEY=sk-proj-...
OPENAI_ORG=org-...
ANTHROPIC_API_KEY=sk-ant-...
GEMINI_API_KEY=AIza...
XAI_API_KEY=xai-...
GITHUB_TOKEN=ghp_...
PROJECT_ROOT=C:\Users\mrgen\fwber
```

**Security**: Never commit API keys to files. All configs reference `${env:VAR_NAME}` or `${VAR_NAME}`.

---

## Installation Steps

### 1. Install Global MCP Servers
```cmd
npm install -g @modelcontextprotocol/server-filesystem
npm install -g @modelcontextprotocol/server-sequential-thinking
npm install -g gemini-mcp
npm install -g codex-mcp-server
```

### 2. Install uv (for Serena)
```cmd
powershell -c "irm https://astral.sh/uv/install.ps1 | iex"
```

### 3. Clone Serena Repository
```cmd
cd %USERPROFILE%
git clone https://github.com/cyanheads/serena.git
cd serena
uv sync
```

### 4. Deploy Configurations

**Codex**:
```cmd
copy codex_config_clean.toml %USERPROFILE%\.codex\config.toml
codex mcp list
```

**Cursor**:
```cmd
copy tools_config_files\cursor_mcp_clean.json %USERPROFILE%\.cursor\mcp.json
```

**Claude**:
```cmd
copy tools_config_files\claude_mcp_template.json %USERPROFILE%\.claude.json
```

**Gemini**:
```cmd
if not exist "%USERPROFILE%\.gemini" mkdir "%USERPROFILE%\.gemini"
copy tools_config_files\gemini_mcp_template.json %USERPROFILE%\.gemini\settings.json
```

**Copilot**:
```cmd
if not exist "%USERPROFILE%\.copilot" mkdir "%USERPROFILE%\.copilot"
copy tools_config_files\copilot_mcp_template.json %USERPROFILE%\.copilot\mcp-config.json
```

---

## Multi-Model Workflow Examples

### Example 1: Implementation with Cross-Check
```cmd
# Primary: Use GPT-5-Codex for implementation
codex exec -m gpt-5-codex-high "Implement user authentication for FWBer"

# Cross-check: Get Claude's perspective via Claude CLI
claude "Review the authentication implementation and suggest improvements"

# Alternative: Get Gemini's perspective from within Codex via gemini-mcp
codex exec -m gpt-5-codex-high "Use gemini-mcp to get Gemini 2.5 Pro's analysis of the auth implementation"
```

### Example 2: Architecture Planning with Consensus
```cmd
# Claude for architecture
claude "Design the real-time matching system architecture for FWBer"

# Gemini for scalability analysis
gemini -m gemini-2.5-pro "Analyze scalability of the proposed matching architecture"

# Codex for implementation feasibility
codex exec -m gpt-5-codex-high "Evaluate implementation complexity of the matching architecture"

# Use sequential-thinking in any CLI for structured comparison
codex exec "Use sequential-thinking to compare the three architectural perspectives and recommend best approach"
```

### Example 3: Using Serena for Context
```cmd
# Serena maintains memory across sessions
codex exec "Use serena to get symbol overview of MatchingEngine.php"
claude "Use serena to recall our previous discussion about the matching algorithm"
```

---

## Process Lifecycle Management

### Cleanup Lingering Processes
```powershell
# Dry run (see what would be killed)
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\ai_cleanup.ps1 -DryRun

# Actually kill MCP-related processes
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\ai_cleanup.ps1

# Aggressive cleanup (include orphans older than 60 minutes)
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\ai_cleanup.ps1 -IncludeOrphans -OlderThanMinutes 60
```

**When to use**:
- After long MCP server sessions
- If you notice high CPU/memory from node/cmd/conhost
- Before starting fresh development session

---

## Troubleshooting

### MCP Server Won't Start
1. Check global installation: `where mcp-server-filesystem`
2. Verify environment variables are set
3. Check timeout settings (increase if needed)
4. Review logs in terminal output

### Timeout Errors
- **Root cause**: stdio handshake failures with npx
- **Solution**: Use global executables (already configured)
- **If still occurring**: Increase startup_timeout_ms to 120000

### API Key Errors
- Verify environment variables are set: `echo %OPENAI_API_KEY%`
- Restart terminal after setting new env vars
- Check key format and validity

### Serena Won't Start
- Verify uv installed: `uv --version`
- Check Serena repo exists: `dir %USERPROFILE%\serena`
- Run `cd %USERPROFILE%\serena && uv sync`

---

## Model Routing Recommendations

Based on task type, use these models:

- **Implementation**: GPT-5-Codex High, Grok Code Fast
- **Architecture**: Claude 4.5, Gemini 2.5 Pro
- **Reasoning**: Claude 4.5, Gemini 2.5 Pro + sequential-thinking
- **Creative/Brainstorming**: Gemini 2.5 Flash, Claude 4.5
- **Performance**: Cheetah, GPT-5-Codex High
- **Large Context**: Code Supernova 1M, Claude 4.5 + Serena

---

## Security Best Practices

1. **No secrets in config files** - all via environment variables
2. **Least-privilege paths** - filesystem_roots restricted to project
3. **Env allowlists** - only necessary variables whitelisted
4. **Process cleanup** - use ai_cleanup.ps1 regularly
5. **Timeout limits** - prevent zombie processes (90s startup, 30s runtime)

---

## Next Steps

### Immediate
1. ✅ Deploy clean configs to all CLIs
2. ⏳ Test multi-model workflow
3. ⏳ Archive old configs with inline secrets

### Phase 2
1. Research Zen MCP (test if unique value vs sequential-thinking)
2. Research Microsoft Amplifier MCP (verify package exists)
3. Consider AutoGen MCP (if agent simulation needed)

### Phase 3
1. Add Grok CLI config (requires XAI_API_KEY)
2. Add Qwen CLI config
3. Enable browser automation if UI testing becomes priority

---

## Reference

- **Value Assessment**: [MCP_SERVER_VALUE_ASSESSMENT.md](MCP_SERVER_VALUE_ASSESSMENT.md:1)
- **Cleanup Script**: [scripts/ai_cleanup.ps1](scripts/ai_cleanup.ps1:1)
- **Enhanced Settings**: [tools_config_files/enhanced_mcp_settings.json](tools_config_files/enhanced_mcp_settings.json:1)