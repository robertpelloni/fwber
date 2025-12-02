# MCP Servers Documentation

## Overview
Model Context Protocol (MCP) servers provide specialized capabilities for AI model orchestration, memory management, knowledge storage, and large-scale code analysis.

## Available MCP Servers

### 1. Zen MCP Server
**Purpose**: Multi-model orchestration and collaboration
**Capabilities**: Consensus building, parallel analysis, structured workflows

#### Key Tools
- `mcp_zen-mcp-server_chat` - Direct communication with specific models
- `mcp_zen-mcp-server_consensus` - Multi-model consensus building
- `mcp_zen-mcp-server_analyze` - Comprehensive analysis workflows
- `mcp_zen-mcp-server_codereview` - Systematic code review
- `mcp_zen-mcp-server_secaudit` - Security audit workflows
- `mcp_zen-mcp-server_planner` - Project planning and strategy
- `mcp_zen-mcp-server_refactor` - Code refactoring analysis
- `mcp_zen-mcp-server_testgen` - Test generation
- `mcp_zen-mcp-server_debug` - Systematic debugging
- `mcp_zen-mcp-server_docgen` - Documentation generation
- `mcp_zen-mcp-server_tracer` - Code tracing and flow analysis
- `mcp_zen-mcp-server_precommit` - Pre-commit validation
- `mcp_zen-mcp-server_thinkdeep` - Deep reasoning workflows

#### Usage Examples
```bash
# Multi-model consensus
mcp_zen-mcp-server_consensus --models '[{"model":"claude-4.5","stance":"neutral"},{"model":"gpt-5-codex","stance":"for"}]' --step "Evaluate microservices architecture"

# Comprehensive analysis
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Analyze FWBer performance" --relevant_files '["file1.php","file2.js"]'

# Security audit
mcp_zen-mcp-server_secaudit --model gpt-5-codex --step "Audit FWBer security" --relevant_files '["security.php"]'
```

### 2. Serena MCP Server
**Purpose**: Memory management and large-scale file access
**Capabilities**: Persistent memory, project context, file analysis

#### Key Tools
- `mcp_serena_write_memory` - Store information in persistent memory
- `mcp_serena_read_memory` - Retrieve stored information
- `mcp_serena_list_memories` - List available memories
- `mcp_serena_delete_memory` - Remove memories
- `mcp_serena_list_dir` - Directory listing with filtering
- `mcp_serena_find_file` - File search with patterns
- `mcp_serena_search_for_pattern` - Pattern search in files
- `mcp_serena_get_symbols_overview` - Code symbol analysis
- `mcp_serena_find_symbol` - Symbol search and analysis
- `mcp_serena_find_referencing_symbols` - Reference analysis
- `mcp_serena_replace_symbol_body` - Code modification
- `mcp_serena_insert_after_symbol` - Code insertion
- `mcp_serena_insert_before_symbol` - Code insertion

#### Usage Examples
```bash
# Store project context
mcp_serena_write_memory --memory_name "fwber_context" --content "FWBer is a dating platform with PHP backend and Next.js frontend"

# Retrieve context
mcp_serena_read_memory --memory_file_name "fwber_context"

# Search for patterns
mcp_serena_search_for_pattern --substring_pattern "function.*match" --relative_path "C:\\Users\\hyper\\fwber"

# Find symbols
mcp_serena_find_symbol --name_path "MatchingEngine" --relative_path "C:\\Users\\hyper\\fwber"
```

### 3. Chroma Knowledge MCP Server
**Purpose**: Vector database for semantic search and knowledge management
**Capabilities**: Document storage, semantic search, knowledge retrieval

#### Key Tools
- `mcp_chroma-knowledge_chroma_create_collection` - Create knowledge collections
- `mcp_chroma-knowledge_chroma_add_document` - Add documents to collections
- `mcp_chroma-knowledge_chroma_query_documents` - Semantic search
- `mcp_chroma-knowledge_chroma_get_documents_by_ids` - Retrieve specific documents
- `mcp_chroma-knowledge_chroma_update_document_content` - Update documents
- `mcp_chroma-knowledge_chroma_delete_document_by_id` - Remove documents
- `mcp_chroma-knowledge_chroma_sequential_thinking` - Store thinking processes
- `mcp_chroma-knowledge_chroma_find_similar_thoughts` - Find similar reasoning
- `mcp_chroma-knowledge_chroma_log_chat` - Log chat interactions

#### Usage Examples
```bash
# Create knowledge collection
mcp_chroma-knowledge_chroma_create_collection --collection_name "fwber_project"

# Add project documentation
mcp_chroma-knowledge_chroma_add_document --collection_name "fwber_project" --document "FWBer is a sophisticated dating platform..."

# Semantic search
mcp_chroma-knowledge_chroma_query_documents --collection_name "fwber_project" --query_texts '["authentication system"]'

# Store thinking process
mcp_chroma-knowledge_chroma_sequential_thinking --thought "Analyzing FWBer architecture" --thought_number 1 --total_thoughts 5
```

## Best Practices for MCP Server Usage

### 1. Always Work in Parallel
- **Before any task**: Use Zen MCP to get input from multiple models
- **During work**: Use consensus building for complex decisions
- **After completion**: Have other models validate the work

### 2. Leverage Memory Systems
- **Serena Memory**: Store project context, user preferences, and long-term information
- **Chroma Knowledge**: Store all analysis results, code reviews, and documentation
- **Sequential Thinking**: Track reasoning processes and decision-making

### 3. Use Structured Workflows
- **Planning**: Use Zen MCP planner for project strategy
- **Analysis**: Use Zen MCP analyze for comprehensive evaluation
- **Implementation**: Use Zen MCP with GPT-5-Codex for coding
- **Review**: Use Zen MCP codereview for quality assurance
- **Storage**: Use Chroma for knowledge management

### 4. Multi-Model Collaboration
```
1. Planning Phase: Zen MCP planner with multiple models
2. Analysis Phase: Zen MCP analyze with specialized models
3. Implementation Phase: Zen MCP with GPT-5-Codex
4. Review Phase: Zen MCP codereview with Claude
5. Storage Phase: Chroma knowledge base
6. Memory Phase: Serena memory updates
```

## Configuration Files

### Zen MCP Server
- **Configuration**: Integrated into CLI tools
- **Models**: Access to 61+ AI models across multiple providers
- **Providers**: Google Gemini, OpenAI, OpenRouter

### Serena MCP Server
- **Configuration**: `C:\Users\hyper\.serena\serena_config.yml`
- **Memory Storage**: Persistent across sessions
- **File Access**: Full project directory access

### Chroma Knowledge MCP Server
- **Server**: Running on `http://localhost:8000`
- **Collections**: Organized by project and topic
- **Search**: Semantic search across all stored knowledge

## Integration Workflows

### Project Analysis Workflow
```bash
# 1. Store project context in Serena
mcp_serena_write_memory --memory_name "project_context" --content "Project overview and goals"

# 2. Analyze with multiple models via Zen
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Comprehensive project analysis"

# 3. Get consensus on findings
mcp_zen-mcp-server_consensus --models '[{"model":"claude-4.5"},{"model":"gpt-5-codex"}]' --step "Validate analysis findings"

# 4. Store results in Chroma
mcp_chroma-knowledge_chroma_add_document --collection_name "project_analysis" --document "Analysis results..."
```

### Code Review Workflow
```bash
# 1. Get code context from Serena
mcp_serena_find_symbol --name_path "function_name" --relative_path "project_path"

# 2. Review with multiple models via Zen
mcp_zen-mcp-server_codereview --model gpt-5-codex --step "Security and quality review"

# 3. Store review results in Chroma
mcp_chroma-knowledge_chroma_add_document --collection_name "code_reviews" --document "Review findings..."
```

### Knowledge Retrieval Workflow
```bash
# 1. Search Chroma for relevant information
mcp_chroma-knowledge_chroma_query_documents --collection_name "project_knowledge" --query_texts '["search terms"]'

# 2. Get additional context from Serena
mcp_serena_read_memory --memory_file_name "relevant_context"

# 3. Use Zen for multi-model analysis
mcp_zen-mcp-server_chat --model claude-4.5 --prompt "Analyze this information and provide recommendations"
```

## Troubleshooting

### Common Issues
1. **MCP Server Connection**: Ensure servers are running and accessible
2. **Memory Access**: Check Serena memory permissions and file access
3. **Knowledge Storage**: Verify Chroma server is running on localhost:8000
4. **Model Access**: Confirm API keys are configured for all providers

### Best Practices
- Always use multiple models for validation
- Store all important information in Chroma
- Maintain project context in Serena memory
- Use structured workflows for complex tasks
- Leverage consensus building for important decisions
