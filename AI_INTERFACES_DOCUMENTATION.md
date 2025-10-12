# AI Interfaces and MCP Servers Documentation

**Generated:** January 2025  
**AI Model:** Cheetah (Cursor Integrated AI Panel)  
**Project:** FWBer.me Dating Platform  

## Overview

This document catalogs all available AI interfaces, MCP (Model Context Protocol) servers, tools, and CLI interfaces accessible through the Cursor integrated AI panel running the "Cheetah" model.

## MCP Servers

### 1. Serena MCP Server (`mcp_serena_*`)
**Status:** ✅ Active and Functional  
**Purpose:** Advanced code analysis, symbol manipulation, and project management

#### Available Tools:
- **File Operations:**
  - `mcp_serena_list_dir` - List directories with recursive option
  - `mcp_serena_find_file` - Find files by mask/pattern
  - `mcp_serena_search_for_pattern` - Search for regex patterns in codebase

- **Symbol Analysis:**
  - `mcp_serena_get_symbols_overview` - Get high-level symbol overview of files
  - `mcp_serena_find_symbol` - Find specific symbols/entities in code
  - `mcp_serena_find_referencing_symbols` - Find references to symbols

- **Code Manipulation:**
  - `mcp_serena_replace_symbol_body` - Replace symbol bodies
  - `mcp_serena_insert_after_symbol` - Insert content after symbols
  - `mcp_serena_insert_before_symbol` - Insert content before symbols

- **Memory Management:**
  - `mcp_serena_write_memory` - Write project information to memory
  - `mcp_serena_read_memory` - Read stored project memories
  - `mcp_serena_list_memories` - List available memories
  - `mcp_serena_delete_memory` - Delete memory files

- **Project Management:**
  - `mcp_serena_check_onboarding_performed` - Check if onboarding completed
  - `mcp_serena_onboarding` - Perform project onboarding
  - `mcp_serena_think_about_collected_information` - Analyze collected info
  - `mcp_serena_think_about_task_adherence` - Verify task alignment
  - `mcp_serena_think_about_whether_you_are_done` - Check completion status

#### Current Project Memories:
- `fwber_project_summary` - Project state and architecture overview
- `fwber_status_2025-10-11` - Recent development status and decisions

### 2. Codex MCP Server (`mcp_codex-mcp-server_*`)
**Status:** ✅ Active (Limited Functionality)  
**Purpose:** AI-assisted coding via Codex CLI

#### Available Tools:
- `mcp_codex-mcp-server_codex` - Execute Codex CLI for AI assistance
- `mcp_codex-mcp-server_ping` - Test server connection ✅ Working
- `mcp_codex-mcp-server_help` - Get help information ❌ Error
- `mcp_codex-mcp-server_listSessions` - List active sessions ✅ Working

#### Configuration Options:
- **Models:** gpt-5-codex (default), gpt-4, gpt-3.5-turbo
- **Reasoning Effort:** low, medium, high
- **Session Management:** Supports session IDs and reset functionality

### 3. Gemini MCP Server (`mcp_gemini-mcp-tool_*`)
**Status:** ⚠️ Partially Functional  
**Purpose:** Google Gemini AI integration

#### Available Tools:
- `mcp_gemini-mcp-tool_ask-gemini` - Query Gemini AI models
- `mcp_gemini-mcp-tool_ping` - Test connection ✅ Working
- `mcp_gemini-mcp-tool_Help` - Get help ❌ ENOENT Error
- `mcp_gemini-mcp-tool_brainstorm` - Creative brainstorming
- `mcp_gemini-mcp-tool_fetch-chunk` - Retrieve cached chunks
- `mcp_gemini-mcp-tool_timeout-test` - Test timeout prevention

#### Configuration Options:
- **Models:** gemini-2.5-pro (default), gemini-2.5-flash
- **Modes:** Sandbox mode, Change mode
- **Features:** Chunk-based responses, timeout testing

### 4. Sequential Thinking MCP Server (`mcp_sequential-thinking_*`)
**Status:** ✅ Available  
**Purpose:** Advanced reasoning and problem-solving

#### Available Tools:
- `mcp_sequential-thinking_sequentialthinking` - Dynamic problem-solving through thoughts

#### Features:
- Flexible thinking process with revision capabilities
- Branching and backtracking support
- Hypothesis generation and verification
- Thought numbering and organization

## Core Development Tools

### File System Operations
- `read_file` - Read file contents with optional line limits
- `write` - Create or overwrite files
- `delete_file` - Delete files
- `list_dir` - List directory contents
- `glob_file_search` - Search files by glob patterns

### Code Analysis and Search
- `codebase_search` - Semantic code search
- `grep` - Powerful regex-based search using ripgrep
- `read_lints` - Read linter errors and diagnostics

### Code Editing
- `search_replace` - Exact string replacements
- `MultiEdit` - Multiple edits to single file
- `edit_notebook` - Jupyter notebook editing

### Terminal Operations
- `run_terminal_cmd` - Execute terminal commands
- **Platform:** Windows PowerShell (WSL available)
- **Command Style:** PowerShell cmdlets and Windows commands

### Web and External Services
- `web_search` - Real-time web information retrieval

### Task Management
- `todo_write` - Create and manage structured task lists

## AI Model Information

### Current Model: Cheetah
- **Provider:** Unknown (as per system prompt)
- **Integration:** Cursor Integrated AI Panel
- **Capabilities:** Code analysis, file manipulation, terminal operations, web search
- **Specialization:** Development assistance and code generation

## Project Context

### FWBer.me Platform
- **Type:** Adult dating/matching platform
- **Architecture:** Hybrid (Legacy PHP + Modern Laravel/Next.js)
- **Status:** Active development with multiple AI collaboration sessions
- **Key Features:** AI avatar generation, location-based matching, B2B venue platform

### Development Environment
- **OS:** Windows 10 (Build 22631)
- **Shell:** WSL (Windows Subsystem for Linux)
- **Workspace:** C:\Users\mrgen\fwber
- **Git Status:** Clean working tree, up to date with origin/main

## Usage Patterns

### Recommended Workflow
1. **Project Analysis:** Use Serena MCP for code analysis and symbol manipulation
2. **Code Generation:** Use Codex MCP for AI-assisted coding
3. **Creative Tasks:** Use Gemini MCP for brainstorming and creative solutions
4. **Complex Reasoning:** Use Sequential Thinking for multi-step problem solving
5. **File Operations:** Use core tools for direct file manipulation
6. **Terminal Operations:** Use PowerShell commands for system operations

### Memory Management
- Store project insights using Serena memory functions
- Reference existing memories before starting new tasks
- Update memories as project evolves

## Limitations and Known Issues

### Codex MCP Server
- Help command returns error
- Limited documentation available

### Gemini MCP Server
- Help command fails with ENOENT error
- May require additional configuration

### General Considerations
- Some tools may require specific environment setup
- PowerShell commands preferred over Linux equivalents
- Memory functions help maintain context across sessions

## Security and Privacy

- All MCP servers operate within Cursor's security context
- Project-specific memories stored locally
- External API calls (web search) respect privacy settings
- Terminal operations require user approval

## Future Enhancements

### Potential Additions
- Additional AI model integrations
- Enhanced debugging capabilities
- Improved documentation generation
- Better error handling and diagnostics

### Monitoring
- Track MCP server status and functionality
- Monitor tool usage patterns
- Update documentation as new tools become available

---

**Last Updated:** January 2025  
**Maintained By:** Cheetah AI Model (Cursor Integrated)  
**Next Review:** As new tools or MCP servers become available
