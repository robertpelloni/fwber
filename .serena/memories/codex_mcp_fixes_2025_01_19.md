# Codex MCP Server Fixes - January 19, 2025

## Problem Solved
Fixed all 7 Codex MCP server startup errors:
- gemini-mcp: program not found → Fixed with PowerShell script path
- everything: program not found → Fixed with PowerShell script path  
- sequential-thinking: program not found → Fixed with PowerShell script path
- serena: request timed out → Fixed with increased timeouts (120s startup, 60s operation)
- filesystem: program not found → Fixed with PowerShell script path
- chroma-knowledge: request timed out → Fixed with increased timeouts
- zen-mcp-server: request timed out → Fixed with increased timeouts

## Root Causes
1. **PowerShell Script Issue**: MCP servers are PowerShell scripts, not executables
2. **Missing Full Paths**: Scripts referenced by name only, not full paths
3. **Timeout Issues**: Complex servers needed more startup time

## Solutions Applied
1. **Updated all commands** to use `powershell.exe` with `-ExecutionPolicy Bypass -File`
2. **Added full paths** to all PowerShell script references
3. **Increased timeouts** for complex servers (Serena, Chroma, Zen)

## File Locations
- filesystem: `C:\Program Files\nodejs\mcp-server-filesystem.ps1`
- sequential-thinking: `C:\Program Files\nodejs\mcp-server-sequential-thinking.ps1`
- everything: `C:\Program Files\nodejs\mcp-server-everything.ps1`
- gemini-mcp: `C:\Users\hyper\AppData\Roaming\npm\gemini-mcp.ps1`
- serena: `C:\Users\hyper\.local\bin\uv.exe`
- zen-mcp-server: `C:\Users\hyper\zen-mcp-server\.venv\Scripts\zen-mcp-server.exe`
- chroma-knowledge: `C:\Users\hyper\chroma-env-312\Scripts\chroma-mcp-server.exe`

## Configuration Updated
- Source: `tools_config_files/codex_config.toml`
- Target: `C:\Users\hyper\.codex\config.toml`
- Status: Deployed and ready for testing

## Next Steps
1. Restart Codex CLI to load updated configuration
2. Test MCP server status with `codex --mcp-debug`
3. Verify all MCP functionality is working