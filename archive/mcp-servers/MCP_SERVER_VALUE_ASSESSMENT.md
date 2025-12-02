# MCP Server Value Assessment for Multi-Model Orchestration
**Date**: 2025-10-18  
**Purpose**: Evaluate each MCP server's utility for the FWBer project and multi-model AI orchestration workflow

---

## Currently Enabled Servers (5)

### 1. **filesystem** - mcp-server-filesystem
**Status**: ✅ ESSENTIAL - Keep enabled  
**Command**: `mcp-server-filesystem C:\Users\hyper\fwber\`  
**What it does**: Provides file system operations (read, write, list, search) within the project directory  
**Value for you**:
- **Critical**: All AI models need to read/write code files
- **Use cases**: Reading source code, writing new files, searching across project
- **FWBer specific**: Essential for PHP backend, frontend code, config files
- **Multi-model**: Every model (Claude, GPT, Gemini, Grok) needs this
**Recommendation**: Keep in ALL CLI configs

---

### 2. **sequential-thinking** - @modelcontextprotocol/server-sequential-thinking
**Status**: ✅ HIGHLY USEFUL - Keep enabled  
**Command**: `mcp-server-sequential-thinking stdio`  
**What it does**: Enables structured, step-by-step reasoning with revision capabilities  
**Value for you**:
- **Architecture planning**: Breaking down complex features (matching algorithm, real-time updates)
- **Debugging**: Systematic problem-solving for issues
- **Design decisions**: Evaluating trade-offs (e.g., SQLite vs PostgreSQL)
- **Multi-model orchestration**: Helps models think through consensus-building
**Recommendation**: Keep in ALL CLI configs - especially valuable for Claude 4.5 and Gemini 2.5 Pro

---

### 3. **gemini-mcp** - gemini-mcp
**Status**: ✅ USEFUL - Keep enabled  
**Command**: `gemini-mcp stdio`  
**Requires**: GEMINI_API_KEY environment variable  
**What it does**: Direct integration with Gemini 2.5 Pro/Flash models  
**Value for you**:
- **Multi-model orchestration**: Allows non-Gemini CLIs to consult Gemini
- **Cross-checking**: Get Gemini's perspective from Codex/Claude sessions
- **Brainstorming**: Gemini Flash is fast for creative ideation
- **FWBer specific**: Good for UI/UX suggestions, creative matching algorithm ideas
**Recommendation**: Keep in Codex, Claude CLI, Copilot CLI configs (not needed in Gemini CLI itself)

---

### 4. **codex-mcp-server** - codex-mcp-server
**Status**: ✅ USEFUL - Keep enabled  
**Command**: `codex-mcp-server stdio`  
**Requires**: OPENAI_API_KEY environment variable  
**What it does**: Direct integration with OpenAI models (GPT-5, GPT-5-Codex)  
**Value for you**:
- **Multi-model orchestration**: Allows non-OpenAI CLIs to consult GPT models
- **Implementation tasks**: GPT-5-Codex excels at code generation
- **Cross-checking**: Get OpenAI's perspective from Claude/Gemini sessions
- **FWBer specific**: Strong for backend PHP logic, API design
**Recommendation**: Keep in Claude CLI, Gemini CLI, Copilot CLI configs (not needed in Codex CLI itself)

---

### 5. **serena** - Serena MCP (via uv)
**Status**: ✅ VERY USEFUL - Keep enabled  
**Command**: `uv run --directory %USERPROFILE%\serena\ serena start-mcp-server --context codex --project C:\Users\hyper\fwber\`  
**Requires**: uv installed, Serena repository cloned  
**What it does**: 
- Memory-based context management across sessions
- Symbol analysis (classes, functions, methods)
- Large project file navigation
- Code reference tracking
**Value for you**:
- **Large codebase**: FWBer has PHP backend, frontend, matching engine - Serena helps navigate
- **Continuity**: Remembers context between sessions
- **Refactoring**: Tracks symbol usage across files
- **Multi-model**: Provides shared memory for model collaboration
**Recommendation**: Keep in ALL CLI configs - critical for large project work

---

## Proposed Additional Servers

### 6. **Zen MCP** - zen-mcp-server (via uv)
**Status**: 🔍 RESEARCH NEEDED  
**Command**: `uv run --directory %USERPROFILE%\zen-mcp-server\ zen-mcp-server`  
**Requires**: OPENAI_API_KEY, GEMINI_API_KEY, OPENROUTER_API_KEY  
**What it claims to do**: Advanced reasoning, step-by-step analysis  
**Concerns**:
- ⚠️ Overlaps with sequential-thinking server
- ⚠️ Requires multiple API keys (cost implications)
- ⚠️ May be redundant if sequential-thinking already provides structured reasoning
**Recommendation**: 
- **Test first**: Enable temporarily and compare with sequential-thinking
- **If unique value found**: Keep and document specific use cases
- **If redundant**: Disable to reduce complexity and API costs
**Decision**: DEFER - Test after core workflow is stable

---

### 7. **Microsoft Amplifier MCP**
**Status**: 🔍 RESEARCH NEEDED  
**What it claims to do**: Code amplification, enhancement, optimization  
**Concerns**:
- ❓ Package availability unclear (not found in npm search)
- ❓ Documentation sparse
- ❓ May overlap with native model capabilities
**Recommendation**: 
- **Research**: Search for official Microsoft MCP server packages
- **If found**: Test for unique value over native model code generation
- **If not found or redundant**: Skip
**Decision**: DEFER - Research package availability first

---

### 8. **Microsoft AutoGen MCP**
**Status**: 🤔 POTENTIALLY USEFUL  
**What it does**: Multi-agent simulation framework  
**Value for you**:
- **Multi-model orchestration**: Could enable agent-based collaboration
- **Consensus building**: Agents could debate solutions
- **Task delegation**: Assign subtasks to specialized agents
**Concerns**:
- ⚠️ Adds significant complexity
- ⚠️ May be overkill for current needs
- ⚠️ Your custom orchestrator already handles parallel model consultation
**Recommendation**: 
- **Phase 2**: Consider after core multi-model workflow is proven
- **Use case**: Only if you need true agent-based simulation (not just model consultation)
**Decision**: DEFER - Not needed for initial multi-model orchestration

---

## Servers to REMOVE/DISABLE

### ❌ **memory** - @modelcontextprotocol/server-memory
**Status**: REDUNDANT - Disable  
**Reason**: Serena MCP provides superior memory with symbol analysis  
**Action**: Remove from all configs

### ❌ **everything** - @modelcontextprotocol/server-everything
**Status**: TOO BROAD - Disable  
**Reason**: Overly permissive, violates least-privilege principle  
**Action**: Remove from all configs

### ❌ **puppeteer** - puppeteer-mcp-server
**Status**: NOT NEEDED YET - Disable  
**Reason**: Browser automation not required for current FWBer development  
**Action**: Disable; re-enable only if testing web UI becomes priority

### ❌ **playwright** - @playwright/mcp
**Status**: NOT NEEDED YET - Disable  
**Reason**: Duplicate of puppeteer functionality  
**Action**: Disable; re-enable only if E2E testing becomes priority

### ❌ **chrome-devtools** - chrome-devtools-mcp
**Status**: NOT NEEDED YET - Disable  
**Reason**: DevTools integration not required for current workflow  
**Action**: Disable

### ❌ **smart-crawler** - mcp-smart-crawler
**Status**: NOT NEEDED - Disable  
**Reason**: Web crawling not relevant to FWBer development  
**Action**: Remove from all configs

### ❌ **bolide-ai** - @bolide-ai/mcp
**Status**: UNKNOWN VALUE - Disable  
**Reason**: Unclear purpose, not documented in your requirements  
**Action**: Remove from all configs

### ❌ **terry-mcp** - terry-mcp
**Status**: UNKNOWN VALUE - Disable  
**Reason**: Unclear purpose, not documented in your requirements  
**Action**: Remove from all configs

### ❌ **enhanced-postgres-mcp-server**
**Status**: NOT READY - Keep disabled  
**Reason**: No PostgreSQL instance running yet  
**Action**: Keep disabled until you set up PostgreSQL for FWBer

### ❌ **zenable** - https://mcp.zenable.app/
**Status**: NOT CONFIGURED - Keep disabled  
**Reason**: Requires authentication setup  
**Action**: Keep disabled until you decide to use this service

### ❌ **jetbrains** - JetBrains MCP Server
**Status**: UNSTABLE - Keep disabled  
**Reason**: Prior timeout issues, requires WebStorm runtime  
**Action**: Keep disabled per your policy

---

## Recommended Final Server List

### Core Servers (Enable in ALL CLIs)
1. ✅ **filesystem** - Essential file operations
2. ✅ **sequential-thinking** - Structured reasoning
3. ✅ **serena** - Memory and symbol analysis

### Cross-Model Integration (Enable selectively)
4. ✅ **gemini-mcp** - Enable in: Codex, Claude, Copilot (not Gemini CLI)
5. ✅ **codex-mcp-server** - Enable in: Claude, Gemini, Copilot (not Codex CLI)

### Total: 5 servers (down from 15+ in old configs)

---

## CLI-Specific Recommendations

### Codex CLI
- filesystem ✅
- sequential-thinking ✅
- gemini-mcp ✅ (to consult Gemini)
- serena ✅
- codex-mcp-server ❌ (redundant - Codex already has OpenAI access)

### Claude CLI
- filesystem ✅
- sequential-thinking ✅
- codex-mcp-server ✅ (to consult OpenAI)
- gemini-mcp ✅ (to consult Gemini)
- serena ✅

### Gemini CLI
- filesystem ✅
- sequential-thinking ✅
- codex-mcp-server ✅ (to consult OpenAI)
- serena ✅
- gemini-mcp ❌ (redundant - Gemini CLI already has Gemini access)

### Copilot CLI
- filesystem ✅
- sequential-thinking ✅
- codex-mcp-server ✅ (to consult OpenAI)
- gemini-mcp ✅ (to consult Gemini)
- serena ✅

### Grok CLI
- filesystem ✅
- sequential-thinking ✅
- codex-mcp-server ✅ (to consult OpenAI)
- gemini-mcp ✅ (to consult Gemini)
- serena ✅

### Qwen CLI
- filesystem ✅
- sequential-thinking ✅
- codex-mcp-server ✅ (to consult OpenAI)
- gemini-mcp ✅ (to consult Gemini)
- serena ✅

---

## Implementation Priority

### Phase 1: Core Cleanup (NOW)
1. Remove unnecessary servers from Codex config
2. Update Cursor config to match
3. Clean up old config files with inline secrets

### Phase 2: CLI Expansion (NEXT)
1. Create Claude CLI config with recommended servers
2. Create Gemini CLI config with recommended servers
3. Create Copilot CLI config with recommended servers
4. Create Grok CLI config with recommended servers
5. Create Qwen CLI config with recommended servers

### Phase 3: Optional Enhancements (LATER)
1. Research and test Zen MCP (if unique value vs sequential-thinking)
2. Research Microsoft Amplifier MCP (if package exists)
3. Consider AutoGen MCP (if agent simulation needed)
4. Enable browser automation (puppeteer/playwright) if UI testing becomes priority

---

## Security Notes

- All configs use global executables (no npx) to avoid stdio handshake issues
- No API keys in config files - all via environment variables
- Filesystem roots restricted to C:\Users\hyper\fwber
- Env allowlists enforce least-privilege access
- Process cleanup script available to prevent zombie processes

---

## Next Actions

1. ✅ Update Codex config to remove unnecessary servers
2. ✅ Update Cursor config to match
3. ⏳ Create CLI templates for Claude, Gemini, Copilot, Grok, Qwen
4. ⏳ Archive old configs with inline secrets
5. ⏳ Test multi-model workflow with cleaned-up server list