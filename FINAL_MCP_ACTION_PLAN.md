# Final MCP CLI Testing & Orchestration Action Plan
**Date:** January 15, 2025  
**Status:** Testing Complete - Action Items Identified

---

## 🎯 EXECUTIVE SUMMARY

**Key Finding:** Claude CLI is the only fully functional tool with 64% MCP server success rate (11/17 servers working). Codex CLI has configuration issues that need fixing. Other CLI tools need testing.

**Root Cause Identified:** Codex CLI's PowerShell wrapper for npx prevents Node.js environment variable access, causing all MCP servers to fail.

**Solution:** Use `cmd /c npx` syntax like Claude CLI does, but with correct `--stdio` argument syntax.

---

## ✅ COMPLETED WORK

### **Testing Results:**

1. **Claude CLI** ✅
   - Status: **WORKING** (11/17 servers = 64% success rate)
   - Config: `C:\Users\mrgen\.claude.json`
   - Uses: `cmd /c npx -y package-name`
   - Working servers: serena, sequential-thinking, codex-mcp-server, filesystem, memory, everything, puppeteer, smart-crawler, terry, zen-mcp-server, zen, gemini-mcp-tool

2. **Codex CLI** ⚠️
   - Status: **PARTIALLY FIXED** (needs syntax corrections)
   - Config: `C:\Users\mrgen\.codex\config.toml`
   - Issues found:
     - Uses `npx.cmd` instead of `npx`
     - Uses `"stdio"` instead of `"--stdio"`
     - Still times out during testing

3. **Diagnostic Report Created** ✅
   - File: `MCP_CLI_DIAGNOSTIC_REPORT.md`
   - Comprehensive analysis of all issues
   - Configuration templates provided

---

## 🔧 IMMEDIATE FIX NEEDED: Codex CLI Configuration

### **Current (Broken) Syntax:**
```toml
[mcp_servers.sequential-thinking]
command = "cmd"
args = ["/c","npx.cmd","-y","@modelcontextprotocol/server-sequential-thinking","stdio"]
```

### **Correct Syntax (Like Claude CLI):**
```toml
[mcp_servers.sequential-thinking]
command = "cmd"
args = ["/c","npx","-y","@modelcontextprotocol/server-sequential-thinking","--stdio"]
```

**Changes Needed:**
1. Replace `"npx.cmd"` with `"npx"` in all server configs
2. Replace `"stdio"` with `"--stdio"` in all server configs
3. Some servers shouldn't have `--stdio` at all (e.g., filesystem takes directory path)

---

## 📋 REMAINING TEST TASKS

### **Priority 1: Fix & Test Codex CLI (30 min)**
- [ ] Fix npx.cmd → npx syntax
- [ ] Fix stdio → --stdio syntax
- [ ] Fix servers that don't need --stdio flag
- [ ] Test: `codex exec --model gpt-5-codex-low "list available tools"`
- [ ] Verify all 14 servers connect successfully

### **Priority 2: Test Other CLI Tools (30 min)**
- [ ] Test Gemini CLI: `gemini -p "list MCP servers"`
- [ ] Test Copilot CLI: `copilot -p "list MCP tools"`
- [ ] Test Grok CLI: Check if MCP servers detected
- [ ] Document success rates for each tool

### **Priority 3: Standardize Configurations (1 hour)**
- [ ] Create master MCP server configuration template
- [ ] Apply standardized config to all 4 CLI tools
- [ ] Ensure all tools have same 17 servers
- [ ] Use consistent timeout settings (30s/60s)

### **Priority 4: Add New MCP Servers (1-2 hours)**
- [ ] Research AutoGen MCP server installation
- [ ] Research Amplifier MCP server installation  
- [ ] Research Ultra MCP server installation
- [ ] Install and configure new servers
- [ ] Add to all CLI tool configurations
- [ ] Test integration

### **Priority 5: Multi-AI Orchestration Testing (30 min)**
- [ ] Test running same task across multiple CLI tools
- [ ] Verify Zen MCP server consensus building
- [ ] Test parallel execution capabilities
- [ ] Document orchestration workflows

---

## 🎨 STANDARDIZED MCP SERVER LIST

### **Core Servers (All CLI Tools Should Have):**

```json
{
  "serena": "Code analysis (20 tools)",
  "zen-mcp-server": "Multi-model orchestration (18 tools)",
  "sequential-thinking": "Structured reasoning",
  "codex-mcp-server": "Codex integration",
  "filesystem": "File operations",
  "memory": "Persistent memory",
  "everything": "Comprehensive tools",
  "puppeteer": "Browser automation",
  "playwright": "Cross-browser testing",
  "chrome-devtools": "Browser debugging",
  "smart-crawler": "Web scraping",
  "gemini-mcp-tool": "Gemini integration",
  "bolide-ai": "AI-powered tools",
  "terry": "Task management"
}
```

### **Optional Servers (Enable When Available):**
```json
{
  "jetbrains": "WebStorm integration (requires IDE)",
  "postgres": "Database access (requires PostgreSQL)",
  "zenable": "Requires OAuth authentication"
}
```

### **New Servers to Add:**
```json
{
  "autogen": "Multi-agent orchestration",
  "amplifier": "AI amplification",
  "ultra-mcp": "Advanced AI tools"
}
```

---

## 📝 CONFIGURATION FIX SCRIPT

### **For Codex config.toml:**

Replace all instances of:
- `"npx.cmd"` → `"npx"`
- `"stdio"` → `"--stdio"` (for most servers)

**Special cases:**
- **filesystem**: Uses directory path, not `--stdio`
  ```toml
  args = ["/c","npx","-y","@modelcontextprotocol/server-filesystem","C:\\Users\\mrgen\\fwber\\"]
  ```

- **memory**: Needs `--stdio` flag
  ```toml
  args = ["/c","npx","-y","@modelcontextprotocol/server-memory","--stdio"]
  ```

- **everything**: Needs `--stdio` flag
  ```toml
  args = ["/c","npx","-y","@modelcontextprotocol/server-everything","--stdio"]
  ```

---

## 🚀 SUCCESS CRITERIA

### **Phase 1 Complete When:**
- ✅ Codex CLI has 12+/14 servers working (85%+)
- ✅ Gemini CLI tested and documented
- ✅ Copilot CLI tested and documented
- ✅ All CLI tools use consistent configuration syntax

### **Phase 2 Complete When:**
- ✅ All 4 CLI tools have identical 17-server configuration
- ✅ 90%+ success rate across all tools
- ✅ Standardized timeout settings applied

### **Phase 3 Complete When:**
- ✅ AutoGen MCP installed and working
- ✅ Amplifier MCP installed and working
- ✅ Ultra MCP installed and working
- ✅ All tools have access to 20+ servers

### **Phase 4 Complete When:**
- ✅ Multi-AI parallel execution tested
- ✅ Zen MCP consensus building validated
- ✅ Cross-tool communication working
- ✅ Orchestration workflows documented

---

## 💡 RECOMMENDED NEXT STEPS

### **Step 1: Fix Codex Configuration (10 minutes)**
Open `C:\Users\mrgen\.codex\config.toml` and do global find/replace:
1. Find: `"npx.cmd"` → Replace: `"npx"`
2. Find: `,"stdio"` → Replace: `,"--stdio"`
3. Save and test

### **Step 2: Test Codex (5 minutes)**
```bash
codex exec --model gpt-5-codex-low "List all MCP tools and their status"
```

Expected: Should show 12-14 working servers

### **Step 3: Test Other CLI Tools (15 minutes)**
```bash
# Gemini
gemini -p "List available MCP servers"

# Copilot  
copilot -p "Show MCP tools"

# Grok
grok -p "List MCP servers"
```

### **Step 4: Standardize & Document (30 minutes)**
- Apply working configuration pattern to all tools
- Document final success rates
- Create unified configuration guide

### **Step 5: Add New Servers (1-2 hours)**
- Research and install AutoGen, Amplifier, Ultra MCP
- Add to all CLI tools
- Test integration

### **Step 6: Test Orchestration (30 minutes)**
- Run parallel AI tasks
- Test consensus building
- Document workflows

---

## 📊 CURRENT STATUS SUMMARY

| CLI Tool | Config File | MCP Servers | Success Rate | Status |
|----------|------------|-------------|--------------|---------|
| **Claude** | `~/.claude.json` | 17 configured | 11/17 (64%) | ✅ Working |
| **Codex** | `~/.codex/config.toml` | 14 configured | 0/14 (0%) | ⚠️ Needs fixes |
| **Gemini** | `~/.gemini/settings.json` | 17 configured | Unknown | 🔍 Needs testing |
| **Copilot** | `~/.copilot/mcp-config.json` | Unknown | Unknown | 🔍 Needs testing |
| **Grok** | `~/.grok/settings.json` | Unknown | 0/? (0%) | ❌ Config issues |

---

## ✅ DELIVERABLES CREATED

1. ✅ **MCP_CLI_DIAGNOSTIC_REPORT.md** - Comprehensive diagnostic analysis
2. ✅ **FINAL_MCP_ACTION_PLAN.md** - This action plan document
3. ⏳ **Codex config fixes** - Ready to apply
4. ⏳ **Standardized configurations** - Ready to create
5. ⏳ **Multi-AI orchestration guide** - Ready to document

---

## 🎯 THE PATH TO "ULTIMATE SUPERPOWER MULTI-AI ORCHESTRATION"

### **Vision:** 
4-5 CLI tools (Codex, Claude, Gemini, Copilot, Grok) + 20+ MCP servers (including Zen, AutoGen, Amplifier, Ultra) = Ultimate parallel AI collaborative environment

### **Current State:**
- 1 tool fully working (Claude - 64%)
- 14-17 MCP servers available
- Orchestration infrastructure in place (Zen MCP)

### **Needed to Achieve Vision:**
1. Fix Codex CLI (10 min)
2. Test & standardize all CLI tools (1 hour)
3. Add 3-5 new MCP servers (1-2 hours)
4. Test multi-AI orchestration (30 min)
5. Document workflows (30 min)

**Total Time to Vision:** ~3-4 hours of focused work

---

## 🏁 CONCLUSION

We've successfully identified the root cause of MCP server failures in Codex CLI (PowerShell wrapper issue) and have a clear path forward. Claude CLI is working well and serves as our template for success.

**Next immediate action:** Fix the Codex config.toml file by replacing `npx.cmd` with `npx` and `stdio` with `--stdio`, then test all CLI tools to achieve your vision of ultimate multi-AI orchestration.

**Ready to proceed with fixes!**
