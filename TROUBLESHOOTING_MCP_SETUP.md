# Troubleshooting MCP Setup

## Issues Encountered and Solutions

### Issue 1: "program not found" for MCP servers

**Problem**: When Codex tries to start MCP servers, it can't find `npx`:
```
ERROR: MCP client for `puppeteer` failed to start: program not found
ERROR: MCP client for `filesystem` failed to start: program not found
```

**Root Cause**: `npx` is not in the PATH when Codex spawns MCP server processes.

**Solutions**:

#### Solution A: Add Node.js to System PATH (Recommended)
1. Find your Node.js installation:
```cmd
where node
where npx
```

2. Add Node.js to System PATH:
   - Open System Properties → Environment Variables
   - Add to System PATH (or User PATH):
     - `C:\Program Files\nodejs\`
     - `C:\Users\hyper\AppData\Roaming\npm\`

3. Restart terminal and test:
```cmd
npx --version
```

#### Solution B: Use Full Path to npx in Config
Update `C:\Users\hyper\.codex\config.toml` to use full paths:

```toml
[mcp_servers.filesystem]
command = "C:\\Program Files\\nodejs\\npx.cmd"
args = ["-y", "@modelcontextprotocol/server-filesystem", "C:\\Users\\hyper\\fwber\\"]
startup_timeout_ms = 60_000
```

#### Solution C: Use Node Path Environment Variable
Add this to Codex config:
```toml
[shell_environment_policy]
inherit = "all"  # Inherit all environment variables
```

### Issue 2: "request timed out" for Python MCP servers

**Problem**: 
```
ERROR: MCP client for `zen-mcp-server` failed to start: request timed out
ERROR: MCP client for `serena` failed to start: request timed out
```

**Root Cause**: Python/UV not in PATH, or servers take too long to start.

**Solutions**:

#### Check UV Installation
```cmd
uv --version
where uv
```

#### Add UV to PATH
If uv is not found, install it or add to PATH:
```powershell
# Install uv if needed
pip install uv

# Or add existing uv to PATH
# Example: C:\Users\hyper\.local\bin
```

#### Increase Timeout
Already set to 60 seconds, but can increase if needed:
```toml
startup_timeout_ms = 120_000  # 2 minutes
```

### Issue 3: OpenAI API Quota Exceeded

**Problem**:
```
ERROR: You exceeded your current quota, please check your plan and billing details
```

**Solution**: Switch to a different model provider.

#### Option A: Use Anthropic (Claude)
```cmd
codex -c model_provider=anthropic exec "your prompt here"
```

Or update default in config:
```toml
model_provider = "anthropic"
model = "claude-sonnet-4"
```

#### Option B: Use OpenRouter
```cmd
codex -c model_provider=openrouter -c model="anthropic/claude-sonnet-4" exec "your prompt"
```

#### Option C: Use Google (Gemini)
```cmd
codex -c model_provider=google -c model="gemini-2.0-flash-exp" exec "your prompt"
```

## Quick Fix Script

Create `scripts/fix_codex_path.ps1`:

```powershell
# Add Node.js and Python to current session PATH
$env:PATH = "C:\Program Files\nodejs;$env:PATH"
$env:PATH = "C:\Users\hyper\AppData\Roaming\npm;$env:PATH"
$env:PATH = "C:\Users\hyper\.local\bin;$env:PATH"

Write-Host "✓ Added Node.js and Python to PATH for this session" -ForegroundColor Green
Write-Host ""
Write-Host "Testing tools:" -ForegroundColor Cyan

# Test commands
Write-Host "node: " -NoNewline
node --version

Write-Host "npx: " -NoNewline  
npx --version

Write-Host "uv: " -NoNewline
uv --version

Write-Host ""
Write-Host "Now try: codex exec 'your prompt'" -ForegroundColor Yellow
```

## Alternative: Use Local MCP Server Installation

Instead of using `npx -y` (which downloads on demand), install MCP servers globally:

```cmd
npm install -g @modelcontextprotocol/server-filesystem
npm install -g @modelcontextprotocol/server-memory
npm install -g @modelcontextprotocol/server-everything
npm install -g @modelcontextprotocol/server-sequential-thinking
npm install -g puppeteer-mcp-server
npm install -g mcp-smart-crawler
npm install -g @playwright/mcp
npm install -g chrome-devtools-mcp
npm install -g terry-mcp
npm install -g codex-mcp-server
npm install -g gemini-mcp-tool
```

Then update config to not use `-y`:
```toml
[mcp_servers.filesystem]
command = "npx"
args = ["@modelcontextprotocol/server-filesystem", "C:\\Users\\hyper\\fwber\\"]
```

## Testing MCP Servers Independently

Test if MCP servers can start outside of Codex:

```cmd
# Test filesystem server
npx -y @modelcontextprotocol/server-filesystem C:\Users\hyper\fwber\

# Test memory server
npx -y @modelcontextprotocol/server-memory

# Test zen-mcp-server
cd C:\Users\hyper\zen-mcp-server
uv run zen-mcp-server
```

If these work independently, the issue is with Codex's environment.

## Recommended Fix Order

1. **Add Node.js and Python to System PATH** (permanent fix)
2. **Restart terminal** to pick up PATH changes
3. **Test individual tools**: `npx --version`, `uv --version`
4. **Try Codex with alternative model**: `codex -c model_provider=anthropic exec "test"`
5. **Verify MCP servers load**: `codex mcp list`

## Verification Commands

After applying fixes:

```cmd
# Check PATH
echo %PATH%

# Verify tools
npx --version
node --version
uv --version

# Test Codex with Anthropic (no quota issues)
codex -c model_provider=anthropic exec "Hello"

# Check MCP servers
codex mcp list
codex mcp get filesystem
```

## Environment Setup Script

Create a permanent fix by adding to PowerShell profile:

```powershell
# Edit profile
notepad $PROFILE

# Add these lines:
$env:PATH = "C:\Program Files\nodejs;$env:PATH"
$env:PATH = "$env:APPDATA\npm;$env:PATH"  
$env:PATH = "$env:USERPROFILE\.local\bin;$env:PATH"
```

## Summary of Root Causes

1. ✅ **Configuration is correct** - all MCP servers are properly defined
2. ❌ **PATH issue** - Codex can't find npx/uv when spawning MCP processes  
3. ❌ **API quota** - OpenAI key is over limit, need to use alternative provider

## Next Steps

1. Fix PATH issue (Solution A recommended)
2. Switch to Anthropic or OpenRouter for API calls
3. Test with: `codex -c model_provider=anthropic mcp list`
4. Verify MCP servers load successfully
5. Run actual task with working configuration
