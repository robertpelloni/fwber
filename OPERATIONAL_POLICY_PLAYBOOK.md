# Operational Policy and Playbook for Multi-Model Orchestration (FWBer)

**Version:** 1.1.0  
**Date:** 2025-10-17  
**Updated:** 2025-10-18 (adapted for mrgen user)

## Purpose and Scope

- Deliver a secure, reliable, parallel orchestration environment on Windows for FWBer and future projects.
- Use stable MCP servers and CLIs that are already working; minimize custom orchestration by default.
- Standardize environment variables, paths, security, error handling, and logging across tools and servers.

## Project Roots and Paths (Windows)

```
PROJECT_ROOT: C:\Users\mrgen\fwber
AI_COORDINATION_DIR: C:\Users\mrgen\fwber\AI_COORDINATION
MCP_CONFIG_PATH: C:\Users\mrgen\fwber\tools_config_files\enhanced_mcp_settings.json
MCP_FILESYSTEM_ROOTS: C:\Users\mrgen\fwber
USERPROFILE: C:\Users\mrgen
```

## Secrets and API Keys (set via environment, not files)

```
OPENAI_API_KEY
OPENAI_ORG (optional)
ANTHROPIC_API_KEY
GEMINI_API_KEY
XAI_API_KEY (Grok)
OPENROUTER_API_KEY (optional)
POSTGRES_URL (optional; only if database tools are enabled)
```

## Current Tooling and Model Inventory (prioritized)

### Models in Order of Importance
1. **Claude 4.5**
2. **GPT-5-Codex** (low/medium/high)
3. **Cheetah** (Claude in Cursor)
4. **Code-Supernova-1-Million** (Claude in Cline)
5. **GPT-5** (low/medium/high)
6. **Gemini 2.5 Pro/Flash** (Flash is newer, nano-banana is newest)
7. **Grok 4 Code** (fast)

### Active CLIs/Tools
- ✅ **Codex CLI** - GPT-5-Codex, GPT-5, MCP client & server
- ✅ **Gemini CLI** - Gemini 2.5 Pro/Flash, MCP client
- ⏳ **Claude Code CLI** - Claude 4.5, MCP client (currently crashes)
- ⏳ **GitHub Copilot CLI** - GPT-5-Codex, GPT-5, MCP client
- ⏳ **Grok CLI** (unofficial) - Grok 4 Code, MCP client
- ⏳ **Qwen CLI**
- ⏳ **Cursor CLI** (WSL only)

### IDE Integrations
- ✅ **Cursor IDE** + built-in AI panel: All models, MCP client
- ✅ **Codex plugin** in Cursor IDE
- ⏳ **Claude Code plugin** in Cursor IDE
- ⏳ **Gemini Code Assist plugin** in Cursor IDE
- ⏳ **Copilot plugin** in Cursor IDE
- ✅ **Cline plugin** in Cursor IDE: All models, MCP client
- ⏳ **JetBrains WebStorm IDE** + AI panel: GPT-5-Codex
- ❌ **JetBrains MCP** - Disabled (stability issues)

### MCP Servers
- ✅ **Serena MCP** - Memory-based orchestration, large project access
- ✅ **Sequential Thinking MCP** - Structured reasoning
- ⏳ **Codex MCP Server** - MCP server capability
- ✅ **Gemini MCP Tool** - Gemini integration
- ⏳ **Zen MCP Server** - Multi-model orchestration
- ⏳ **Microsoft Amplifier MCP**
- ⏳ **Microsoft AutoGen**
- ❌ **JetBrains MCP** - Disabled by policy until stable

### Optional Orchestration
- Custom AI Orchestrator script is present but **disabled by default** in MCP config
- Use only if you need bespoke flows; otherwise prefer Serena + model MCPs

## MCP Server Selection and Policy

### Enabled (Stable)
- ✅ `serena` - 20+ code analysis tools
- ✅ `sequential-thinking` - Structured reasoning
- ✅ `codex-mcp-server` - Codex integration
- ✅ `gemini-mcp-tool` - Gemini integration
- ✅ `memory` - Knowledge graph (globally installed)
- ✅ `filesystem` - File operations (globally installed)
- ✅ `everything` - Comprehensive tools (globally installed)

### Disabled (Policy or Stability)
- ❌ `jetbrains` - Requires IDE runtime; prior timeouts
- ❌ `zenable` - Requires authentication setup
- ❌ `enhanced-postgres-mcp-server` - No database configured

### Configuration Defaults
- **Timeouts:** timeoutMs=30s, startupTimeoutMs=60s
- **Retries:** maxRetries=2
- **Backoff:** base=500ms, factor=2.0, max=15s, jitter on
- **Health Checks:** Use "--version" or "--help" where available
- **Allowed Paths:** Restricted to `C:\Users\mrgen\fwber`

## Codex CLI Environment Allowlist (Explicit Policy)

**File:** `C:\Users\mrgen\.codex\config.toml`

### Minimal Allowlist (Recommended)
```toml
[security]
env_allowlist = [
  "PROJECT_ROOT",
  "MCP_CONFIG_PATH",
  "MCP_FILESYSTEM_ROOTS",
  "OPENAI_API_KEY",
  "ANTHROPIC_API_KEY",
  "GEMINI_API_KEY",
  "XAI_API_KEY",
  "OPENROUTER_API_KEY",
  "USERPROFILE",
  "PATH"
]
filesystem_roots = ["C:\\Users\\mrgen\\fwber"]
```

### Secure-Expanded Allowlist (Opt-in After Validation)
```toml
[security.expanded]
env_allowlist = [
  "PROJECT_ROOT",
  "AI_COORDINATION_DIR",
  "MCP_CONFIG_PATH",
  "MCP_FILESYSTEM_ROOTS",
  "SERENA_HOME",
  "ZEN_HOME",
  "OPENAI_API_KEY",
  "OPENAI_ORG",
  "ANTHROPIC_API_KEY",
  "GEMINI_API_KEY",
  "XAI_API_KEY",
  "OPENROUTER_API_KEY",
  "POSTGRES_URL",
  "USERPROFILE",
  "PATH"
]
filesystem_roots = ["C:\\Users\\mrgen\\fwber"]
```

## Operational Defaults for Orchestration

- **Prefer orchestration via:** Serena MCP, Sequential Thinking MCP, and native provider MCPs
- **Custom orchestrator:** Available but disabled by default (enable only if needed)
- **Concurrency:** 4 sessions max (configurable by AI_MAX_CONCURRENCY)
- **Task timeout:** 5 minutes (AI_TASK_TIMEOUT_MS=300000)
- **Model timeout:** 30 seconds (AI_MODEL_TIMEOUT_MS=30000)
- **Retries:** 2 with exponential backoff + jitter
- **Circuit breaker:** Open after 3 fails in 2 minutes; 5-minute cooldown

## Security and Privacy Practices

1. **Store secrets only in environment variables** - Never commit keys to version control
2. **Redact secrets from logs** - Enable AI_LOG_REDACT=1
3. **Restrict filesystem access** - C:\Users\mrgen\fwber unless explicitly opted in
4. **Least-privilege env allowlists** - For MCP clients (Codex, etc.)
5. **Limit high-risk flags** - ("full-auto", raw mode) prefer "secure" profiles

## Structured Logging and Auditing

```
AI_LOG_JSONL=1
Log file: C:\Users\mrgen\fwber\AI_COORDINATION\logs\orchestrator.jsonl
Rotation: 10 MB or 10,000 events; keep 5 rotations; 14-day retention
Event keys: ts, level, event, taskId, model, durationMs, attempt, outcome, error, meta
```

### Sample Events
- task_started
- model_consult_started
- model_consult_finished
- consensus_finalized
- task_completed
- task_failed

## Standard Operating Workflow (Recommended)

### 1. Verify MCP Servers
```bash
# Test globally installed servers
mcp-server-memory --version
mcp-server-filesystem --help
mcp-server-everything --version
```

### 2. Verify CLIs and Authentication
```bash
# Codex
codex login status

# Gemini
gemini auth status

# Grok (set XAI_API_KEY first)
grok --help
```

### 3. Use Model-Specific CLI for Primary Tasks
```bash
# Implementation
codex exec --model gpt-5-codex-high "Implement feature X"

# Architecture
gemini -m gemini-2.5-pro "Analyze scalability needs"
```

### 4. Use Serena MCP for Contextual Code Ops
- Symbol overviews
- References
- Memory-backed summaries
- Constrained filesystem paths

### 5. Parallel Perspectives (Optional)
- Use Serena + provider MCP tools to collect multiple opinions; OR
- Enable custom orchestrator (disabled by default) for weighted consensus

### 6. Documentation and Change Capture
- Append decisions to shared logs
- Periodically persist summaries via Serena memory

## Recommended Policies per Task Type

| Task Type | Primary Model | Secondary Model |
|-----------|--------------|-----------------|
| Implementation | GPT-5-Codex (high) | Grok (fast code) |
| Architecture/Reasoning | Claude 4.5 | Gemini 2.5 Pro |
| Creative/Brainstorming | Gemini 2.5 Flash | Claude 4.5 |
| Performance Optimization | Cheetah | GPT-5-Codex High |
| Large Context/Continuity | Code Supernova 1M | Claude 4.5 |

## Troubleshooting Quick Reference

### MCP Server Timeouts
- Increase startupTimeoutMs to 90s
- Validate Node/NPX/UV availability
- Check PATH issues

### JetBrains MCP Issues
- Keep disabled unless WebStorm running
- Use HTTP/SSE only if stable

### Codex "env whitelist" Failures
- Confirm [security] env_allowlist includes required names
- Avoid wildcarding

### Package Not Found (NPM)
- Use exact package names
- Pin version if community package fails
- Temporarily disable that server

### Windows Console Encoding
- Prefer UTF-8 (chcp 65001)
- Avoid installers with Unicode glyphs if they break

## Performance and Reliability

- **Modest concurrency:** 4 sessions max to avoid rate limits
- **Retries:** Use sparingly; respect provider limits
- **Circuit breaker:** Prevents thrashing on flaky providers

## When to Enable the Custom Orchestrator

Enable when you need:
- Strict, policy-driven routing
- Per-model quotas
- Explicit consensus math
- Audit trails beyond current MCP servers

**To enable:** Set `"claude-code-orchestrator.enabled"` to true in `enhanced_mcp_settings.json`

## WSL vs Native Windows

- **Prefer:** Native Windows paths (already in use)
- **If WSL:** Ensure consistent path translation and env allowlists
- **Avoid:** Mixing both in same command chain

## Acceptance Checklist for Any New Tool/Server

- [ ] **Paths:** Only absolute Windows paths; no ~
- [ ] **Env:** Documented names only; keys set via OS, not hardcoded
- [ ] **Security:** Least-privilege allowlists; no broad roots
- [ ] **Logging:** Redact secrets; structured JSONL if enabled
- [ ] **Timeouts:** 30s/60s with 2 retries and exponential backoff
- [ ] **Health checks:** "--version/--help" or disabled with justification

## Configuration File Locations

```
C:\Users\mrgen\.claude\claude.json
C:\Users\mrgen\.cursor\mcp.json
C:\Users\mrgen\AppData\Roaming\Cursor\User\globalStorage\saoudrizwan.claude-dev\settings\cline_mcp_settings.json
C:\Users\mrgen\.codex\config.toml
C:\Users\mrgen\.gemini\mcp-config.json (or settings.json)
C:\Users\mrgen\.copilot\mcp-config.json
C:\Users\mrgen\.grok\settings.json (or user-settings.json)
C:\Users\mrgen\AppData\Roaming\JetBrains\WebStorm2025.3\options\llm.mcpServers.xml
C:\Users\mrgen\AppData\Roaming\Claude\claude_desktop_config.json
C:\Users\mrgen\AppData\Local\github-copilot\intellij\mcp.json
C:\Users\mrgen\.serena\serena_config.yml
```

## Change Log

### 1.1.1 (2025-10-18)
- Adapted for mrgen user (was hyper)
- Added current status of working/broken tools
- Integrated lessons learned from Codex stdio bug discovery
- Added globally installed MCP servers to enabled list

### 1.1.0 (2025-10-17)
- Rewrote into operational policy with security, orchestration workflow
- Standardized Windows paths (mrgen → hyper)
- Set JetBrains MCP disabled by default
- Custom orchestrator disabled by default
- Introduced JSONL structured logging and rotation guidance

---

**Status:** ✅ POLICY DOCUMENTED  
**Next Step:** Implement environment variables and MCP server walkthrough  
**Priority:** HIGH
