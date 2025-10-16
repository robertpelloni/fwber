# Codex MCP Environment Variable Whitelist Fix

## Problem Identified
The user correctly identified that Codex has environment variable whitelisting that was preventing MCP servers from starting properly. The error "Could not determine Node.js install directory" indicated that Node.js-related environment variables weren't being passed to the subprocesses.

## Solution Implemented
1. **Switched back to PowerShell configuration** as requested
2. **Enhanced environment variables** for all npx-based MCP servers:
   - `PATH`: Complete system PATH including Node.js directories
   - `NODE_PATH`: Node.js module path
   - `NODE`: Absolute path to node.exe
   - `NPM_CONFIG_PREFIX`: NPM global prefix
   - `NODE_ENV`: Production environment setting

## Configuration Applied
Updated `C:\Users\mrgen\.codex\config.toml` with comprehensive environment variables for all servers:
- `gemini-mcp-tool`
- `sequential-thinking`
- `filesystem`
- `memory`
- `everything`
- `enhanced-postgres-mcp-server`
- `puppeteer`
- `smart-crawler`
- `bolide-ai`
- `terry`
- `chrome-devtools`
- `playwright`

## Key Changes
- PowerShell command preserved: `C:\Program Files\PowerShell\7\pwsh.exe`
- Enhanced env vars with Node.js paths: `NODE = "C:\Program Files\nodejs\node.exe"`
- Maintained API keys for `zen-mcp-server` and other servers

This should resolve the timeout issues caused by environment variable whitelisting in Codex.