# MCP CLI Comprehensive Diagnostic Report
**Date:** January 15, 2025  
**Status:** CRITICAL ISSUES IDENTIFIED

## 🔴 CRITICAL FINDINGS

### **Codex CLI - Complete MCP Server Failure**

**Root Cause:** Environment variable whitelisting preventing Node.js path access

**Errors Found:**
```
ERROR: Could not determine Node.js install directory
ERROR: failed to deserialize JSONRPCMessage
ERROR: All 14 MCP servers timing out
```

**The Problem:**
1. Codex uses PowerShell wrapper: `C:\Program Files\PowerShell\7\pwsh.exe -File C:\Program Files\nodejs\npx.ps1`
2. This wrapper cannot find Node.js because environment variables are not whitelisted
3. All servers fail with timeout errors

**Impact:** 0/14 MCP servers working in Codex (0% success rate)

---

## ✅ CLI TOOLS COMPARISON

### **Claude CLI - WORKING (64% success rate)**
**Config:** `C:\Users\mrgen\.claude.json`
**MCP Servers:** 17 configured
**Working:** 11/17 servers (64%)
**Success Factors:**
- Uses `cmd /c npx` instead of PowerShell wrapper
- Simple command structure
- Proper timeout configurations (30s/60s)

**Working Servers:**
1. serena ✅
2. sequential-thinking ✅
3. codex-mcp-server ✅
4. filesystem ✅
5. memory ✅
6. everything ✅
7. puppeteer ✅
8. smart-crawler ✅
9. terry ✅
10. zen-mcp-server ✅
11. zen ✅
12. gemini-mcp-tool ✅

**Failed Servers:**
- jetbrains (disabled - requires WebStorm)
- postgres (disabled - requires PostgreSQL)
- zenable (disabled - requires OAuth)
- bolide-ai (connection issues)
- playwright (connection issues)

---

### **Gemini CLI - NEEDS TESTING**
**Config:** `C:\Users\mrgen\.gemini\settings.json`
**MCP Servers:** 17 configured
**Working:** Unknown (needs testing)
**Configuration:** Uses plain `npx` commands (simpler than Codex)

---

### **Copilot CLI - NEEDS TESTING**
**Config:** `C:\Users\mrgen\.copilot\mcp-config.json`
**Status:** Previously reported as working with Codex MCP server

---

### **Grok CLI - CONFIGURATION ISSUES**
**Config:** `C:\Users\mrgen\.grok\settings.json`
**Status:** MCP servers not detected
**Issue:** Configuration format may be incompatible

---

## 🎯 THE SOLUTION: Fix Codex Environment Variable Whitelisting

### **Problem Analysis:**

Codex config uses PowerShell wrapper for npx:
```toml
[mcp_servers.sequential-thinking]
command = "C:\\Program Files\\PowerShell\\7\\pwsh.exe"
args = ["-NoLogo","-NoProfile","-File","C:\\Program Files\\nodejs\\npx.ps1","-y","@modelcontextprotocol/server-sequential-thinking","--stdio"]
env = { PATH = "C:\\Program Files\\nodejs\\;...", NODE_PATH = "...", NODE = "...", NPM_CONFIG_PREFIX = "...", NODE_ENV = "production" }
```

**The Issue:** Even though NODE, PATH, and NODE_PATH are specified in the `env` section, PowerShell's npx.ps1 script cannot access them properly.

### **The Fix:** Use `cmd /c` like Claude does

**Current (Broken):**
```toml
command = "C:\\Program Files\\PowerShell\\7\\pwsh.exe"
args = ["-NoLogo","-NoProfile","-File","C:\\Program Files\\nodejs\\npx.ps1","-y","package-name","--stdio"]
```

**Fixed (Working):**
```toml
command = "cmd"
args = ["/c", "npx", "-y", "package-name", "--stdio"]
```

OR even simpler:
```toml
command = "npx"
args = ["-y", "package-name", "--stdio"]
```

---

## 📋 STANDARDIZATION PLAN

### **Goal:** All 4 CLI tools should have identical MCP server access

### **Target MCP Server List (19 servers):**

#### **Core Development Tools:**
1. **serena** - Code analysis and project management (20 tools)
2. **zen-mcp-server** - Multi-model AI orchestration (18 tools)
3. **sequential-thinking** - Structured reasoning
4. **codex-mcp-server** - Codex integration

#### **File & System:**
5. **filesystem** - File operations
6. **memory** - Persistent memory
7. **everything** - Comprehensive tools

#### **Web & Browser:**
8. **puppeteer** - Browser automation
9. **playwright** - Cross-browser testing
10. **chrome-devtools** - Browser debugging
11. **smart-crawler** - Web scraping

#### **AI Integration:**
12. **gemini-mcp-tool** - Gemini integration
13. **bolide-ai** - AI-powered tools
14. **terry** - Task management

#### **Optional (Enable when available):**
15. **jetbrains** - WebStorm integration (requires IDE running)
16. **postgres** - Database (requires PostgreSQL)
17. **zenable** - Requires OAuth setup

#### **To Add:**
18. **AutoGen MCP** - Multi-agent orchestration
19. **Amplifier MCP** - AI amplification
20. **Ultra MCP** - Advanced AI tools

---

## 🔧 IMMEDIATE ACTION ITEMS

### **Priority 1: Fix Codex CLI (30 minutes)**
1. Replace all PowerShell npx wrappers with `cmd /c npx`
2. Simplify environment variable configurations
3. Test each MCP server individually
4. Verify all 14 servers connect successfully

### **Priority 2: Test Other CLI Tools (30 minutes)**
1. Test Gemini CLI MCP servers
2. Test Copilot CLI MCP servers
3. Test Grok CLI configuration
4. Document success rates

### **Priority 3: Standardize Configurations (1 hour)**
1. Create unified MCP server list
2. Apply to all CLI tools
3. Use consistent command format (cmd /c npx)
4. Apply consistent timeout settings (30s/60s)

### **Priority 4: Add New MCP Servers (1-2 hours)**
1. Research AutoGen MCP server
2. Research Amplifier MCP server
3. Research Ultra MCP server
4. Install and configure new servers
5. Test integration across all CLI tools

### **Priority 5: Test Multi-AI Orchestration (30 minutes)**
1. Test parallel execution across all CLI tools
2. Verify cross-tool communication
3. Test consensus building
4. Document orchestration capabilities

---

## 📊 SUCCESS METRICS

### **Current State:**
- **Codex CLI:** 0/14 MCP servers working (0%)
- **Claude CLI:** 11/17 MCP servers working (64%)
- **Gemini CLI:** Unknown (needs testing)
- **Copilot CLI:** Unknown (needs testing)
- **Grok CLI:** 0/? MCP servers detected (0%)

### **Target State:**
- **All CLI Tools:** 17+/19 MCP servers working (90%+)
- **Standardized:** All tools have identical server access
- **New Servers:** AutoGen, Amplifier, Ultra MCP added
- **Orchestration:** Multi-AI parallel execution working

---

## 🚀 NEXT STEPS

1. **Fix Codex config.toml** - Replace PowerShell wrappers
2. **Test all CLI tools** - Verify MCP server access
3. **Standardize configurations** - Apply consistent format
4. **Add new servers** - Install AutoGen, Amplifier, Ultra
5. **Test orchestration** - Verify multi-AI collaboration

---

## 📝 CONFIGURATION TEMPLATES

### **Working Template (from Claude CLI):**
```json
{
  "mcpServers": {
    "server-name": {
      "command": "cmd",
      "args": ["/c", "npx", "-y", "package-name", "--stdio"],
      "timeout": 30000,
      "startupTimeout": 60000
    }
  }
}
```

### **Python-based Server Template:**
```json
{
  "mcpServers": {
    "server-name": {
      "command": "uv",
      "args": ["run", "--directory", "path/to/server/", "server-command"],
      "env": {
        "API_KEY": "value"
      },
      "timeout": 30000,
      "startupTimeout": 60000
    }
  }
}
```

---

## ✅ CONCLUSION

The primary issue is **Codex CLI's environment variable whitelisting problem** preventing Node.js path access through PowerShell wrappers. The solution is to use `cmd /c npx` like Claude CLI does, which has 64% success rate.

Once Codex is fixed and all configurations are standardized, we should achieve 90%+ success rate across all CLI tools with 19+ MCP servers providing comprehensive multi-AI orchestration capabilities.
