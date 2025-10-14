# MCP Servers, Tools, and AI Interfaces Documentation

## Overview
This document provides a comprehensive summary of all MCP (Model Context Protocol) servers, tools, and AI CLI interfaces available to Cline running the "code-supernova-1-million" model within Cursor IDE.

## Connected MCP Servers

### 1. Serena MCP Server
**Command:** `uv run --directory C:\Users\mrgen\serena\ serena start-mcp-server --context ide-assistant --project C:\Users\mrgen\fwber\`

#### Available Tools:

**list_dir**
- **Purpose:** Lists all non-gitignored files and directories in the given directory (optionally with recursion)
- **Returns:** JSON object with names of directories and files
- **Key Parameters:**
  - `relative_path`: Path to scan (use "." for project root)
  - `recursive`: Boolean for recursive scanning
  - `skip_ignored_files`: Boolean to skip ignored files

**find_file**
- **Purpose:** Finds non-gitignored files matching given file mask within relative path
- **Returns:** JSON object with list of matching files
- **Key Parameters:**
  - `file_mask`: Filename pattern with wildcards (* or ?)
  - `relative_path`: Directory to search in

**search_for_pattern**
- **Purpose:** Flexible pattern search in codebase with advanced filtering options
- **Returns:** Mapping of file paths to matched lines with context
- **Key Features:**
  - Regex pattern matching with DOTALL support
  - Context lines before/after matches
  - Include/exclude glob patterns
  - Code file restriction option
  - Maximum answer character limits

**get_symbols_overview**
- **Purpose:** High-level understanding of code symbols in a file
- **Returns:** JSON object with top-level symbols information
- **Use Case:** First tool to call when understanding a new file

**find_symbol**
- **Purpose:** Retrieves information on code entities (classes, methods, etc.) by name pattern
- **Returns:** List of symbols with locations
- **Key Features:**
  - Name path pattern matching (simple names, relative paths, absolute paths)
  - Depth parameter for retrieving children
  - Include/exclude symbol kinds
  - Substring matching option

**find_referencing_symbols**
- **Purpose:** Finds references to symbols at given name path
- **Returns:** List of JSON objects with referencing symbols and code snippets
- **Use Case:** Understanding symbol usage across codebase

**replace_symbol_body**
- **Purpose:** Replaces the body of a symbol with new content
- **Use Case:** Modifying function/method implementations

**insert_after_symbol**
- **Purpose:** Inserts content after symbol definition
- **Use Case:** Adding new classes, functions, methods, or assignments

**insert_before_symbol**
- **Purpose:** Inserts content before symbol definition
- **Use Case:** Adding imports, new symbols, or content before existing definitions

**write_memory**
- **Purpose:** Store project information for future tasks in markdown format
- **Use Case:** Documenting important project context and decisions

**read_memory**
- **Purpose:** Retrieve previously stored memory content
- **Use Case:** Accessing historical project information

**list_memories**
- **Purpose:** List all available memory files
- **Returns:** List of stored memory files

**delete_memory**
- **Purpose:** Delete memory files when no longer relevant
- **Use Case:** Cleaning up outdated project information

**check_onboarding_performed**
- **Purpose:** Check if project onboarding was completed
- **Use Case:** Determining if initial project setup was done

**onboarding**
- **Purpose:** Perform project onboarding if not previously completed
- **Returns:** Instructions for creating onboarding information

**think_about_collected_information**
- **Purpose:** Analyze collected information for sufficiency and relevance
- **Use Case:** Post-analysis reflection after information gathering

**think_about_task_adherence**
- **Purpose:** Evaluate if current work aligns with the task
- **Use Case:** Mid-task progress evaluation

**think_about_whether_you_are_done**
- **Purpose:** Determine if task completion criteria are met
- **Use Case:** Final task evaluation before completion

### 2. Sequential Thinking MCP Server
**Command:** `npx -y @modelcontextprotocol/server-sequential-thinking`

#### Available Tools:

**sequentialthinking**
- **Purpose:** Dynamic and reflective problem-solving through structured thinking
- **Key Features:**
  - Adaptive thinking process that can evolve
  - Hypothesis generation and verification
  - Iterative refinement capabilities
  - Branching and backtracking support
- **Use Cases:**
  - Complex problem decomposition
  - Planning with revision capabilities
  - Multi-step analysis requiring course correction
  - Context maintenance over multiple steps

### 3. Codex MCP Server
**Command:** `npx -y codex-mcp-server`

#### Available Tools:

**codex**
- **Purpose:** Execute Codex CLI in non-interactive mode for AI assistance
- **Key Parameters:**
  - `prompt`: Coding task, question, or analysis request
  - `sessionId`: Optional session ID for conversational context
  - `resetSession`: Boolean to reset session history
  - `model`: Model selection (gpt-5-codex, gpt-4, gpt-3.5-turbo)
  - `reasoningEffort`: Control reasoning depth (low/medium/high)

**ping**
- **Purpose:** Test MCP server connection
- **Use Case:** Connection verification

**help**
- **Purpose:** Get Codex CLI help information
- **Use Case:** Documentation and usage guidance

**listSessions**
- **Purpose:** List all active conversation sessions with metadata
- **Use Case:** Session management and tracking

### 4. Gemini MCP Tool Server
**Command:** `npx -y gemini-mcp-tool`

#### Available Tools:

**ask-gemini**
- **Purpose:** Advanced model selection with sandbox and change mode capabilities
- **Key Features:**
  - File inclusion with @ syntax (@filename.ext)
  - Multiple model support (gemini-2.5-flash, gemini-2.5-pro)
  - Sandbox mode for safe code testing
  - Structured change mode for edit suggestions
  - Chunk-based responses for large outputs

**ping**
- **Purpose:** Echo service for connection testing

**Help**
- **Purpose:** Receive help information about Gemini tools

**brainstorm**
- **Purpose:** Generate novel ideas with dynamic context gathering
- **Key Features:**
  - Multiple creative frameworks (SCAMPER, Design Thinking, etc.)
  - Domain context integration
  - Idea clustering and feasibility analysis
  - Iterative refinement

**fetch-chunk**
- **Purpose:** Retrieve cached chunks from changeMode responses
- **Use Case:** Handling large responses in chunks

**timeout-test**
- **Purpose:** Test timeout prevention mechanisms
- **Use Case:** Development and debugging

## Available CLI Tools (System Level)
Based on system detection, the following CLI tools are available:
- **git**: Version control operations
- **npm**: Node.js package management
- **pip**: Python package management
- **curl**: HTTP requests and API interactions
- **python**: Python runtime for scripting
- **node**: Node.js runtime
- **mysql**: Database operations
- **dotnet**: .NET development tools

## AI Model Context
- **Current Model:** code-supernova-1-million
- **Runtime Environment:** Cline within Cursor IDE
- **Operating System:** Windows 11
- **Default Shell:** /bin/bash

## Integration Capabilities
The system supports:
1. **Tool Chaining:** Sequential tool execution for complex workflows
2. **File Operations:** Reading, writing, and editing project files
3. **Browser Automation:** Web interaction through Puppeteer
4. **Terminal Operations:** Command execution in project environment
5. **Memory Management:** Persistent storage of project context
6. **Multi-Model Access:** Integration with various AI models and services

## Best Practices for Tool Usage
1. **Sequential Operations:** Use tools one at a time, waiting for confirmation
2. **Context Preservation:** Leverage memory tools for project continuity
3. **Error Handling:** Verify tool success before proceeding
4. **Resource Management:** Clean up unused memory and sessions
5. **Pattern Matching:** Use appropriate search patterns for code analysis

## Project Context Integration
All tools are integrated with the fwber project context:
- **Project Root:** `c:\Users\mrgen\fwber`
- **Git Integration:** Connected to `https://github.com/robertpelloni/fwber.git`
- **File System Access:** Full access to project files and directories
- **Database Integration:** Access to MySQL and SQLite databases

This documentation serves as a reference for leveraging the full capabilities of the available MCP servers and tools within the development environment.
