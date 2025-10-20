# Claude AI Model Documentation

## Overview
Claude is an AI assistant developed by Anthropic, known for its strong reasoning capabilities, safety focus, and comprehensive understanding of complex topics.

## Available Models
- **Claude Sonnet 4.5** (anthropic/claude-sonnet-4.5) - Latest version with enhanced reasoning
- **Claude Opus 4.1** (anthropic/claude-opus-4.1) - High-performance model
- **Claude Sonnet 4.1** (anthropic/claude-sonnet-4.1) - Balanced performance
- **Claude 3.5 Haiku** (anthropic/claude-3.5-haiku) - Fast and efficient

## Key Strengths
- **Reasoning & Analysis**: Excellent at complex problem-solving and multi-step reasoning
- **Code Review**: Strong at identifying bugs, security issues, and code quality problems
- **Documentation**: Exceptional at creating clear, comprehensive documentation
- **Safety**: Built with safety-first principles and constitutional AI
- **Context Understanding**: Strong at maintaining context across long conversations

## MCP Server Integration

### Using Zen MCP for Orchestration
```bash
# Access Claude through Zen MCP server
mcp_zen-mcp-server_chat --model anthropic/claude-sonnet-4.5 --prompt "Your question here"
```

### Using Serena MCP for Memory Management
```bash
# Store important information in Serena memory
mcp_serena_write_memory --memory_name "claude_insights" --content "Key insights from Claude analysis"

# Retrieve stored information
mcp_serena_read_memory --memory_file_name "claude_insights"
```

### Using Chroma MCP for Knowledge Storage
```bash
# Store Claude's analysis in Chroma knowledge base
mcp_chroma-knowledge_chroma_add_document --collection_name "project_analysis" --document "Claude's comprehensive analysis..."
```

## Best Practices for Claude

### 1. Always Work in Parallel
- **Before starting any task**: Get input from at least 2-3 other models (GPT-5-Codex, Gemini 2.5 Pro, Grok 4)
- **During analysis**: Use consensus building to validate findings
- **After completion**: Have another model review Claude's work

### 2. Leverage MCP Servers
- **Serena**: Use for storing project context, user preferences, and long-term memory
- **Chroma**: Store all analysis results, code reviews, and documentation
- **Zen**: Orchestrate multi-model collaboration and consensus building

### 3. Specialized Use Cases
- **Architecture Analysis**: Claude excels at system design and architectural decisions
- **Security Audits**: Strong at identifying vulnerabilities and security best practices
- **Code Quality**: Excellent at code review and refactoring suggestions
- **Documentation**: Best-in-class for creating comprehensive technical documentation

### 4. Collaboration Workflow
```
1. Initial Planning: Get input from Claude + 2 other models
2. Analysis Phase: Claude leads with Serena memory context
3. Validation: Cross-check with GPT-5-Codex and Gemini 2.5 Pro
4. Storage: Save results in Chroma knowledge base
5. Memory: Update Serena with key insights and decisions
```

## Example Commands

### Architecture Analysis
```bash
# Get multi-model consensus on architecture
mcp_zen-mcp-server_consensus --models '[{"model":"anthropic/claude-sonnet-4.5","stance":"neutral"},{"model":"gpt-5-codex","stance":"for"},{"model":"gemini-2.5-pro","stance":"against"}]' --step "Evaluate microservices vs monolith architecture for FWBer project"
```

### Code Review
```bash
# Comprehensive code review with multiple models
mcp_zen-mcp-server_codereview --model anthropic/claude-sonnet-4.5 --step "Review FWBer security implementation" --relevant_files '["C:\\Users\\hyper\\fwber\\security-manager.php"]'
```

### Knowledge Storage
```bash
# Store Claude's analysis
mcp_chroma-knowledge_chroma_add_document --collection_name "fwber_project" --document "Claude Architecture Analysis: [detailed analysis content]"
```

## Integration with Other Models

### Primary Collaborations
- **GPT-5-Codex**: Code implementation and technical execution
- **Gemini 2.5 Pro**: Performance analysis and optimization
- **Grok 4**: Creative problem-solving and alternative approaches

### Consensus Building
Always use Zen MCP's consensus tool to get multiple perspectives:
```bash
mcp_zen-mcp-server_consensus --models '[{"model":"anthropic/claude-sonnet-4.5"},{"model":"gpt-5-codex"},{"model":"gemini-2.5-pro"}]' --step "Your question or analysis request"
```

## Memory Management

### Serena Memory Usage
- Store user preferences and project context
- Maintain conversation history and key decisions
- Track project evolution and lessons learned

### Chroma Knowledge Base
- Store all technical analysis and findings
- Maintain searchable knowledge repository
- Enable semantic search across all project information

## Quality Assurance

### Always Validate with Other Models
1. **Primary Analysis**: Claude performs initial analysis
2. **Secondary Review**: GPT-5-Codex or Gemini 2.5 Pro reviews
3. **Consensus Building**: Use Zen MCP to resolve differences
4. **Final Validation**: Store in Chroma and update Serena memory

### Error Prevention
- Always cross-reference findings with multiple models
- Use Serena memory to avoid repeating past mistakes
- Store all decisions and rationale in Chroma for future reference

## Configuration Files
- **Claude CLI**: `C:\Users\hyper\.claude.json`
- **Cursor MCP**: `C:\Users\hyper\.cursor\mcp.json`
- **Claude Desktop**: `C:\Users\hyper\AppData\Roaming\Claude\claude_desktop_config.json`

## Troubleshooting
- If Claude seems to lose context, check Serena memory for stored information
- For complex analysis, always use multi-model consensus
- Store all important findings in Chroma for future reference
- Use Zen MCP to orchestrate complex multi-step workflows
