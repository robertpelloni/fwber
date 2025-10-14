# AI Tools and Models Documentation
**Environment**: Claude Code (Cursor Integration)
**Primary Model**: Claude 4.5 Sonnet (`claude-sonnet-4-5-20250929`)
**Knowledge Cutoff**: January 2025
**Generated**: 2025-10-12

---

## Table of Contents
1. [Primary AI Model](#primary-ai-model)
2. [MCP Servers Overview](#mcp-servers-overview)
3. [Serena (Semantic Code Analysis)](#serena-semantic-code-analysis)
4. [Gemini MCP Tool](#gemini-mcp-tool)
5. [Codex MCP Server](#codex-mcp-server)
6. [Sequential Thinking](#sequential-thinking)
7. [Claude Code Native Tools](#claude-code-native-tools)
8. [Orchestration Patterns](#orchestration-patterns)

---

## Primary AI Model

### Claude 4.5 Sonnet
- **Model ID**: `claude-sonnet-4-5-20250929`
- **Context Window**: 200,000 tokens (budget tracked per session)
- **Capabilities**:
  - Advanced code generation and analysis
  - Multi-tool orchestration
  - Semantic code understanding
  - Task planning and execution
  - Real-time collaboration within IDE

### Operational Context
- **Platform**: Windows (PowerShell environment)
- **IDE**: Cursor with Claude Code integration
- **Working Directory**: `c:\Users\mrgen\fwber`
- **Git Repository**: Active (branch: main)

---

## MCP Servers Overview

Model Context Protocol (MCP) servers extend Claude's capabilities with specialized tools. Currently configured servers:

| Server | Status | Primary Function | Tools Count |
|--------|--------|------------------|-------------|
| **Serena** | ✅ Active | Semantic code analysis & editing | 20+ |
| **Gemini MCP Tool** | ⚠️ Requires gemini CLI | Multi-model AI assistance | 7 |
| **Codex MCP Server** | ⚠️ Connection issues | GPT-based code assistance | 4 |
| **Sequential Thinking** | ✅ Active | Complex problem-solving | 1 |

---

## Serena (Semantic Code Analysis)

**Status**: ✅ Fully operational with onboarding completed

### Purpose
Professional semantic coding agent for intelligent, resource-efficient codebase interaction. Focuses on symbol-level understanding rather than full-file reads.

### Available Memories
Serena maintains project context through memory files:
- `fwber_project_summary` - Overall project architecture
- `fwber_status_2025-10-11` - Recent project status
- `multi_model_orchestration_design` - Multi-AI coordination patterns

### Tools (20 total)

#### Directory & File Discovery
1. **`mcp__serena__list_dir`**
   - Lists non-gitignored files/directories
   - Supports recursive scanning
   - Configurable file filtering
   ```typescript
   Parameters: {
     relative_path: string,  // "." for project root
     recursive: boolean,
     skip_ignored_files?: boolean,
     max_answer_chars?: number
   }
   ```

2. **`mcp__serena__find_file`**
   - Find files by mask (wildcards: *, ?)
   - Fast pattern-based file discovery
   ```typescript
   Parameters: {
     file_mask: string,      // e.g., "*.php", "test_*.ts"
     relative_path: string
   }
   ```

#### Code Search & Analysis
3. **`mcp__serena__search_for_pattern`**
   - Regex pattern search across codebase
   - Flexible file filtering (globs, code-only option)
   - Context lines before/after matches
   ```typescript
   Parameters: {
     substring_pattern: string,        // Regex pattern
     relative_path?: string,
     restrict_search_to_code_files?: boolean,
     paths_include_glob?: string,
     paths_exclude_glob?: string,
     context_lines_before?: number,
     context_lines_after?: number,
     max_answer_chars?: number
   }
   ```

4. **`mcp__serena__get_symbols_overview`**
   - High-level view of file symbols
   - **First tool to call** when exploring new files
   - Shows classes, functions, methods without full bodies
   ```typescript
   Parameters: {
     relative_path: string,
     max_answer_chars?: number
   }
   ```

5. **`mcp__serena__find_symbol`**
   - Locate symbols by name path
   - Supports substring matching
   - Optional depth for nested symbols (e.g., class methods)
   ```typescript
   Parameters: {
     name_path: string,           // "class/method" or "/absolute/path"
     relative_path?: string,      // Restrict to file/directory
     substring_matching?: boolean,
     include_body?: boolean,
     depth?: number,              // 0=symbol only, 1=children, etc.
     include_kinds?: number[],    // LSP symbol kinds
     exclude_kinds?: number[],
     max_answer_chars?: number
   }
   ```

   **LSP Symbol Kinds Reference**:
   - 5 = Class
   - 6 = Method
   - 12 = Function
   - 13 = Variable
   - [Full list in tool description]

6. **`mcp__serena__find_referencing_symbols`**
   - Find all references to a symbol
   - Returns code snippets around references
   - Useful for impact analysis before edits
   ```typescript
   Parameters: {
     name_path: string,
     relative_path: string,       // Must be a file (not directory)
     include_kinds?: number[],
     exclude_kinds?: number[],
     max_answer_chars?: number
   }
   ```

#### Code Editing (Symbol-Based)
7. **`mcp__serena__replace_symbol_body`**
   - Replace entire symbol body
   - Use for method/function/class rewrites
   - Does NOT include docstrings/imports
   ```typescript
   Parameters: {
     name_path: string,
     relative_path: string,
     body: string                 // New symbol definition
   }
   ```

8. **`mcp__serena__insert_after_symbol`**
   - Insert code after symbol definition
   - Typical use: Add new methods to class
   ```typescript
   Parameters: {
     name_path: string,
     relative_path: string,
     body: string
   }
   ```

9. **`mcp__serena__insert_before_symbol`**
   - Insert code before symbol definition
   - Typical use: Add imports before first symbol
   ```typescript
   Parameters: {
     name_path: string,
     relative_path: string,
     body: string
   }
   ```

#### Memory Management
10. **`mcp__serena__write_memory`**
    - Save project insights for future sessions
    - Markdown format
    ```typescript
    Parameters: {
      memory_name: string,        // Meaningful identifier
      content: string,
      max_answer_chars?: number
    }
    ```

11. **`mcp__serena__read_memory`**
    - Retrieve saved project knowledge
    - Only read when relevant to current task
    ```typescript
    Parameters: {
      memory_file_name: string,
      max_answer_chars?: number
    }
    ```

12. **`mcp__serena__list_memories`**
    - Show all available memory files

13. **`mcp__serena__delete_memory`**
    - Remove outdated/irrelevant memory
    - Requires explicit user request

#### Onboarding & Meta-Cognition
14. **`mcp__serena__check_onboarding_performed`**
    - Check if project has been analyzed
    - **Call after activating project**

15. **`mcp__serena__onboarding`**
    - Initialize project understanding
    - Called once per project

16. **`mcp__serena__think_about_collected_information`**
    - **ALWAYS call after search sequences**
    - Reflect on information sufficiency

17. **`mcp__serena__think_about_task_adherence`**
    - **ALWAYS call before code edits**
    - Verify still on track with user's goal

18. **`mcp__serena__think_about_whether_you_are_done`**
    - Evaluate task completion
    - Call when believing task is finished

### Serena Usage Philosophy
- **Token Efficiency**: Read only necessary code, avoid full-file reads
- **Symbol-First**: Use overview → find_symbol → targeted reads
- **Two Editing Approaches**:
  1. **Symbol-based**: Replace/insert entire symbols
  2. **Regex-based**: Edit specific lines (use native Edit tool)
- **Think Before Acting**: Use meta-cognition tools before major operations

---

## Gemini MCP Tool

**Status**: ⚠️ Requires `gemini` CLI installation (currently failing with ENOENT)

### Purpose
Multi-model orchestration enabling Gemini-powered analysis alongside Claude.

### Tools (7 total)

1. **`mcp__gemini-mcp-tool__ask-gemini`**
   - Send prompts to Gemini models
   - Supports file inclusion with `@filename` syntax
   - **changeMode**: Structured edit suggestions for Claude to apply
   ```typescript
   Parameters: {
     prompt: string,
     model?: string,              // Default: gemini-2.5-pro
     sandbox?: boolean,           // Isolated execution environment
     changeMode?: boolean,        // Format for Claude edits
     chunkCacheKey?: string,      // For continuation
     chunkIndex?: number | string
   }
   ```

   **Available Models**:
   - `gemini-2.5-pro` (default)
   - `gemini-2.5-flash`

2. **`mcp__gemini-mcp-tool__brainstorm`**
   - Creative ideation with frameworks
   - Domain-specific context integration
   ```typescript
   Parameters: {
     prompt: string,
     domain?: string,             // software|business|creative|research|product|marketing
     methodology?: string,        // divergent|convergent|scamper|design-thinking|lateral|auto
     ideaCount?: number,          // Default: 12
     existingContext?: string,
     constraints?: string,
     includeAnalysis?: boolean,   // Default: true
     model?: string
   }
   ```

3. **`mcp__gemini-mcp-tool__fetch-chunk`**
   - Retrieve subsequent chunks from changeMode responses
   ```typescript
   Parameters: {
     cacheKey: string,
     chunkIndex: number           // 1-based
   }
   ```

4. **`mcp__gemini-mcp-tool__timeout-test`**
   - Test tool timeout prevention
   ```typescript
   Parameters: {
     duration: number             // Minimum: 10ms
   }
   ```

5. **`mcp__gemini-mcp-tool__ping`**
   - ✅ Test connection (working)

6. **`mcp__gemini-mcp-tool__Help`**
   - ⚠️ Get help (currently failing)

### Installation Required
To enable Gemini MCP Tool:
```powershell
# Install gemini CLI (method depends on distribution)
# Then verify installation:
gemini --version
```

---

## Codex MCP Server

**Status**: ⚠️ Connection issues with help, but ping works

### Purpose
GPT-5 Codex and GPT-4 powered code assistance with session management.

### Tools (4 total)

1. **`mcp__codex-mcp-server__codex`**
   - Execute Codex CLI for AI assistance
   ```typescript
   Parameters: {
     prompt: string,
     model?: string,              // gpt-5-codex|gpt-4|gpt-3.5-turbo
     sessionId?: string,          // For conversational context
     resetSession?: boolean,
     reasoningEffort?: string     // low|medium|high
   }
   ```

2. **`mcp__codex-mcp-server__listSessions`**
   - ✅ View active conversation sessions (working - reports no sessions)

3. **`mcp__codex-mcp-server__ping`**
   - ✅ Test connection (working)

4. **`mcp__codex-mcp-server__help`**
   - ⚠️ Get Codex help (currently failing)

### Notes
- Ping successful, indicates server is reachable
- Help/codex execution may require additional configuration
- Session management allows multi-turn conversations with GPT models

---

## Sequential Thinking

**Status**: ✅ Active

### Purpose
Dynamic, reflective problem-solving through structured thought chains. Enables iterative hypothesis generation and verification.

### Tool

**`mcp__sequential-thinking__sequentialthinking`**
- Chain-of-thought reasoning with revision capability
- Hypothesis generation and verification
- Branch and backtrack through solution space

```typescript
Parameters: {
  thought: string,
  nextThoughtNeeded: boolean,
  thoughtNumber: number,          // 1-indexed
  totalThoughts: number,          // Adjustable estimate
  isRevision?: boolean,
  revisesThought?: number,
  branchFromThought?: number,
  branchId?: string,
  needsMoreThoughts?: boolean
}
```

### When to Use
- Breaking down complex problems into steps
- Planning with room for course correction
- Analysis where scope may not be initially clear
- Multi-step solutions requiring context maintenance
- Filtering irrelevant information

### Key Features
- **Dynamic planning**: Adjust `totalThoughts` up/down as you progress
- **Revision support**: Question or revise previous thoughts
- **Non-linear thinking**: Branch or backtrack when needed
- **Hypothesis-driven**: Generate → verify → repeat until satisfied
- **Single answer goal**: Process concludes with one correct answer

---

## Claude Code Native Tools

These are built-in tools provided by the Claude Code environment (not MCP servers):

### File Operations
1. **Read** - Read files (supports images, PDFs, Jupyter notebooks)
2. **Write** - Create new files
3. **Edit** - Exact string replacements in files
4. **Glob** - Fast file pattern matching
5. **Grep** - Ripgrep-powered content search
6. **NotebookEdit** - Edit Jupyter notebook cells

### Execution & Testing
7. **Bash** - Execute PowerShell commands (Windows environment)
8. **BashOutput** - Retrieve background shell output
9. **KillShell** - Terminate background shells

### Web & Network
10. **WebFetch** - Fetch and analyze web content
11. **WebSearch** - Search the web (US only)

### MCP Integration
12. **ListMcpResourcesTool** - List resources from MCP servers
13. **ReadMcpResourceTool** - Read specific MCP resource

### Task Management
14. **Task** - Launch specialized sub-agents for complex tasks
    - `general-purpose` - Research, multi-step tasks
    - `statusline-setup` - Configure status line
    - `output-style-setup` - Create output styles

15. **TodoWrite** - Track task progress (critical for planning)

### IDE Integration
16. **SlashCommand** - Execute custom slash commands from `.claude/commands/`

### Planning Mode
17. **ExitPlanMode** - Transition from planning to execution

### Notes on Tool Usage
- **Prefer specialized tools** over Bash commands
  - Use Read instead of `cat`/`Get-Content`
  - Use Edit instead of `sed`/`awk`
  - Use Grep instead of command-line `grep`
  - Use Glob instead of `find`/`Get-ChildItem`

- **PowerShell Environment**
  - Commands are Windows PowerShell cmdlets, not bash
  - Use proper quoting for paths with spaces
  - Sequential commands: use `&&` for dependencies, `;` for independent

- **Parallel Execution**
  - Make multiple independent tool calls in single message
  - Use sequential calls only when outputs inform next inputs

---

## Orchestration Patterns

### Multi-Model Strategy
Based on available servers, you can orchestrate:

#### Pattern 1: Claude + Gemini Dual Analysis
```
Claude (this session) → Analyze code structure
  ↓
Gemini (via MCP) → Generate alternative implementation
  ↓
Claude → Integrate and refine
```

#### Pattern 2: Symbol-First Code Editing
```
Serena: get_symbols_overview → Understand file structure
  ↓
Serena: find_symbol (include_body) → Read specific symbols
  ↓
Serena: find_referencing_symbols → Check impact
  ↓
Serena: think_about_task_adherence → Verify approach
  ↓
Serena: replace_symbol_body → Execute edit
```

#### Pattern 3: Complex Problem Solving
```
Sequential Thinking → Break down problem
  ↓
Serena → Gather code context
  ↓
Claude → Generate solution
  ↓
Sequential Thinking → Verify hypothesis
  ↓
Serena → Implement changes
```

#### Pattern 4: Cross-Model Brainstorming
```
Gemini: brainstorm → Generate ideas (SCAMPER/Design Thinking)
  ↓
Claude → Evaluate feasibility with codebase context
  ↓
Serena → Check for existing patterns
  ↓
Claude → Synthesize final approach
```

### Memory Management Pattern
```
Session Start → Serena: check_onboarding_performed
  ↓
If needed → Serena: read_memory (relevant files only)
  ↓
Work on task
  ↓
Session End → Serena: write_memory (key insights)
```

### Tool Selection Hierarchy
1. **Understanding Code**: Serena (symbol-based) > Native Read
2. **Editing Code**: Serena (symbols) or Native Edit (lines)
3. **File Discovery**: Serena find_file > Native Glob
4. **Content Search**: Serena search_for_pattern > Native Grep
5. **Alternative Perspectives**: Gemini ask-gemini (when CLI available)
6. **Complex Reasoning**: Sequential Thinking
7. **Task Delegation**: Native Task tool for sub-agents

---

## Configuration & Setup

### Working Environment
- **OS**: Windows
- **Shell**: PowerShell
- **IDE**: Cursor with Claude Code integration
- **Working Directory**: `c:\Users\mrgen\fwber`
- **Git**: Active repository (branch: main)

### MCP Server Status Summary
| Server | Operational | Notes |
|--------|-------------|-------|
| Serena | ✅ Yes | Fully configured with project onboarding |
| Sequential Thinking | ✅ Yes | Always available |
| Gemini MCP | ⚠️ Partial | Requires `gemini` CLI installation |
| Codex MCP | ⚠️ Partial | Server responsive, execution unclear |

### Enabling Full Functionality

#### For Gemini MCP Tool
```powershell
# Check if gemini CLI is available
Get-Command gemini -ErrorAction SilentlyContinue

# If not found, install per Gemini CLI documentation
# Verify with:
gemini --version
```

#### For Codex MCP Server
- Ping test successful
- May need API key configuration
- Check Codex MCP server documentation

---

## Best Practices

### General Guidelines
1. **Start with Serena** for code exploration
2. **Use Sequential Thinking** for complex multi-step problems
3. **Leverage Gemini** for alternative perspectives (when available)
4. **Track progress** with TodoWrite for non-trivial tasks
5. **Think before editing** using Serena's meta-cognition tools

### Token Efficiency
- Avoid full-file reads when symbol analysis suffices
- Use `get_symbols_overview` before diving into code
- Set `max_answer_chars` limits when appropriate
- Read memories only when relevant to current task

### Error Handling
- If Gemini tools fail, fall back to Claude-only workflow
- If Codex fails, use Claude's native code generation
- Always verify tool availability before relying on it

### Multi-Model Coordination
- Claude (this) = Primary reasoning and orchestration
- Gemini = Alternative implementation strategies
- Codex = GPT-5 specialized code assistance
- Sequential Thinking = Complex problem decomposition
- Serena = Efficient codebase navigation

---

## Quick Reference Card

### Most Frequently Used Tools

**Code Understanding**:
- `mcp__serena__get_symbols_overview` - Start here
- `mcp__serena__find_symbol` - Locate specific code
- `Read` - Read full files when needed

**Code Editing**:
- `mcp__serena__replace_symbol_body` - Rewrite symbols
- `Edit` - Line-level changes
- `Write` - Create new files

**Search**:
- `mcp__serena__search_for_pattern` - Regex search
- `Grep` - Fast content search
- `Glob` - Find files by pattern

**Problem Solving**:
- `mcp__sequential-thinking__sequentialthinking` - Break down complexity
- `TodoWrite` - Plan and track tasks

**Multi-Model**:
- `mcp__gemini-mcp-tool__ask-gemini` - Gemini analysis
- `mcp__gemini-mcp-tool__brainstorm` - Creative ideation

**Execution**:
- `Bash` - Run PowerShell commands
- `Task` - Launch sub-agents

---

## Limitations & Known Issues

1. **Gemini MCP Tool**: Requires `gemini` CLI binary in PATH
2. **Codex MCP Server**: Help/execution endpoints have connection issues
3. **Web Tools**: WebSearch US-only, WebFetch may have redirect handling
4. **Token Budget**: 200,000 tokens per session (monitor usage)
5. **Platform**: Windows-specific (PowerShell, not bash)

---

## Version Information
- **Claude Model**: 4.5 Sonnet (Build 20250929)
- **Claude Code Environment**: Cursor Integration
- **Documentation Generated**: 2025-10-12
- **Project**: FWBer.me (Multi-stack dating platform)

---

## Additional Resources

### Claude Code Documentation
- Help: `/help` command or https://github.com/anthropics/claude-code/issues
- Docs Map: https://docs.claude.com/en/docs/claude-code/claude_code_docs_map.md

### MCP Protocol
- Official Spec: https://modelcontextprotocol.io/
- Server Development: Check individual MCP server repositories

### Related Project Files
- [CLAUDE.md](./CLAUDE.md) - Project-specific Claude instructions
- [MCP_SERVERS_AND_TOOLS_DOCUMENTATION.md](./MCP_SERVERS_AND_TOOLS_DOCUMENTATION.md) - Previous documentation (if exists)

---

*This documentation reflects the actual capabilities discovered through tool introspection and testing. Update as new servers are added or configurations change.*
