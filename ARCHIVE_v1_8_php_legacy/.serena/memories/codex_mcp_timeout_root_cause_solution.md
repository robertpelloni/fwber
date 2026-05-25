# Codex MCP Timeout - Root Cause & Solution

## ROOT CAUSE IDENTIFIED
Codex CLI is experiencing JSON-RPC handshake failures with ALL MCP servers. The servers START correctly but the stdio communication between Codex and the servers is not working.

## Evidence
1. `codex mcp list` shows all servers configured correctly
2. Manual `npx -y @modelcontextprotocol/server-memory --help` works
3. All servers timeout at exactly 5 minutes (300000ms)
4. No JSON-RPC messages are being exchanged

## Most Likely Issue
Codex's MCP client implementation may have a bug with stdio transport on Windows, OR the servers are not receiving/responding to the `initialize` message from Codex.

## RECOMMENDED SOLUTIONS (in order of likelihood to work)

### Solution 1: Install MCP Servers Globally
Install all MCP servers as global npm packages to avoid npx resolution issues:
```cmd
npm install -g @modelcontextprotocol/server-memory
npm install -g @modelcontextprotocol/server-everything  
npm install -g @modelcontextprotocol/server-filesystem
npm install -g @modelcontextprotocol/server-sequential-thinking
npm install -g gemini-mcp-tool
npm install -g puppeteer-mcp-server
npm install -g mcp-smart-crawler
npm install -g @bolide-ai/mcp
npm install -g terry-mcp
npm install -g chrome-devtools-mcp
npm install -g @playwright/mcp
```

Then update config to use direct commands instead of npx.

### Solution 2: Direct Node.js Invocation
Use Node.js directly instead of npx:
```toml
command = "C:\\Program Files\\nodejs\\node.exe"
args = ["C:\\Program Files\\nodejs\\node_modules\\npm\\bin\\npx-cli.js", "-y", "package-name", "--stdio"]
```

### Solution 3: Test with Other CLI Tools
Since Codex may have a bug, test the SAME configuration with:
- Claude CLI
- Gemini CLI  
- GitHub Copilot CLI

If they work, it confirms Codex has an implementation issue.

## Files Created
- CODEX_MCP_TIMEOUT_RESEARCH_AND_ANALYSIS.md - Initial research
- CODEX_MCP_FINAL_DIAGNOSIS.md - Root cause analysis and solutions

## Next Steps
1. Test global npm package installation
2. If that doesn't work, try direct Node.js invocation
3. If still failing, test with Claude/Gemini CLI to isolate if it's a Codex-specific bug
4. Once one CLI tool works, replicate configuration to all others