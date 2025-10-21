# Gemini AI Model Documentation

## Overview
Gemini is Google's advanced AI model family, known for its multimodal capabilities, large context windows, and strong reasoning abilities across text, code, and analysis tasks.

## Available Models
- **Gemini 2.5 Pro** (gemini-2.5-pro) - Most capable model with 1M context, deep reasoning
- **Gemini 2.5 Flash** (gemini-2.5-flash) - Fast model with 1M context, rapid analysis
- **Gemini 2.0 Flash** (gemini-2.0-flash) - Latest model with experimental thinking, audio/video support
- **Gemini 2.0 Flash Lite** (gemini-2.0-flash-lite) - Lightweight fast model, text-only

## Key Strengths
- **Large Context**: 1M token context window for processing large codebases
- **Multimodal**: Can process text, images, audio, and video
- **Performance Analysis**: Excellent at identifying performance bottlenecks and optimization opportunities
- **Architecture Analysis**: Strong at system design and architectural decision-making
- **Data Analysis**: Proficient at analyzing datasets and generating insights
- **Reasoning**: Advanced reasoning capabilities with thinking mode

## MCP Server Integration

### Using Zen MCP for Orchestration
```bash
# Access Gemini through Zen MCP server
mcp_zen-mcp-server_chat --model gemini-2.5-pro --prompt "Analyze FWBer performance bottlenecks"
```

### Using Serena MCP for Analysis Context
```bash
# Store analysis patterns and insights in Serena memory
mcp_serena_write_memory --memory_name "gemini_insights" --content "Performance optimization patterns and architectural insights"

# Retrieve analysis context
mcp_serena_read_memory --memory_file_name "gemini_insights"
```

### Using Chroma MCP for Analysis Storage
```bash
# Store analysis results in Chroma
mcp_chroma-knowledge_chroma_add_document --collection_name "performance_analysis" --document "Gemini: FWBer performance analysis..."
```

## Best Practices for Gemini

### 1. Always Work in Parallel
- **Before analysis**: Get input from Claude and GPT-5-Codex on scope and approach
- **During analysis**: Use consensus building for complex architectural decisions
- **After analysis**: Have Claude validate findings and GPT-5-Codex implement optimizations

### 2. Leverage MCP Servers
- **Serena**: Store analysis methodologies, performance patterns, and architectural insights
- **Chroma**: Maintain comprehensive repository of analysis results and recommendations
- **Zen**: Orchestrate multi-model analysis and validation workflows

### 3. Specialized Use Cases
- **Performance Analysis**: Primary tool for identifying bottlenecks and optimization opportunities
- **Architecture Review**: Excellent at evaluating system design and scalability
- **Data Analysis**: Strong at analyzing metrics, logs, and performance data
- **Multimodal Analysis**: Can process screenshots, diagrams, and visual content
- **Large Context Processing**: Ideal for analyzing entire codebases and documentation

### 4. Collaboration Workflow
```
1. Planning: Claude + GPT-5-Codex define analysis scope
2. Analysis: Gemini performs comprehensive analysis
3. Implementation: GPT-5-Codex implements optimizations
4. Validation: Claude reviews security and quality implications
5. Storage: Save analysis in Chroma knowledge base
6. Memory: Update Serena with analysis patterns
```

## Example Commands

### Performance Analysis
```bash
# Comprehensive performance analysis
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Analyze FWBer performance bottlenecks and optimization opportunities" --relevant_files '["C:\\Users\\hyper\\fwber\\styles.css","C:\\Users\\hyper\\fwber\\service-worker.js"]'
```

### Architecture Analysis
```bash
# System architecture evaluation
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Evaluate FWBer architecture for scalability and maintainability" --relevant_files '["C:\\Users\\hyper\\fwber\\MatchingEngine.php","C:\\Users\\hyper\\fwber\\ProfileManager.php"]'
```

### Multimodal Analysis
```bash
# Analyze visual content (screenshots, diagrams)
mcp_zen-mcp-server_chat --model gemini-2.5-pro --prompt "Analyze this system architecture diagram" --images '["path/to/diagram.png"]'
```

### Knowledge Storage
```bash
# Store analysis results
mcp_chroma-knowledge_chroma_add_document --collection_name "fwber_project" --document "Gemini Performance Analysis: [detailed analysis content]"
```

## Integration with Other Models

### Primary Collaborations
- **Claude**: Architecture design and quality validation
- **GPT-5-Codex**: Implementation of performance optimizations
- **Grok 4**: Creative problem-solving and alternative approaches

### Consensus Building for Analysis
Always use Zen MCP's consensus tool for complex analysis decisions:
```bash
mcp_zen-mcp-server_consensus --models '[{"model":"gemini-2.5-pro","stance":"neutral"},{"model":"anthropic/claude-sonnet-4.5","stance":"for"},{"model":"gpt-5-codex","stance":"against"}]' --step "Evaluate microservices vs monolith for FWBer performance"
```

## Memory Management

### Serena Memory Usage
- Store analysis methodologies and performance patterns
- Maintain architectural decision records
- Track optimization strategies and their effectiveness

### Chroma Knowledge Base
- Store all analysis results and recommendations
- Maintain searchable repository of performance insights
- Enable semantic search across analysis knowledge

## Quality Assurance

### Always Validate with Other Models
1. **Analysis**: Gemini performs comprehensive analysis
2. **Architecture Review**: Claude validates architectural implications
3. **Implementation Check**: GPT-5-Codex evaluates implementation feasibility
4. **Consensus Building**: Use Zen MCP to resolve analysis disagreements
5. **Final Validation**: Store in Chroma and update Serena memory

### Analysis Quality Standards
- Always consider multiple perspectives and approaches
- Use large context window effectively for comprehensive analysis
- Store all analysis results in Chroma for future reference
- Use multi-model validation for critical architectural decisions

## Configuration Files
- **Gemini CLI**: `C:\Users\hyper\.gemini\settings.json`
- **Gemini MCP**: `C:\Users\hyper\.gemini\mcp-config.json`

## Specialized Tools

### Performance Analysis
```bash
# Systematic performance analysis
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Analyze FWBer frontend performance" --relevant_files '["C:\\Users\\hyper\\fwber\\styles.css","C:\\Users\\hyper\\fwber\\service-worker.js"]'
```

### Architecture Analysis
```bash
# Comprehensive architecture review
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Review FWBer system architecture" --relevant_files '["C:\\Users\\hyper\\fwber\\MatchingEngine.php","C:\\Users\\hyper\\fwber\\ProfileManager.php"]'
```

### Data Analysis
```bash
# Analyze performance metrics and logs
mcp_zen-mcp-server_chat --model gemini-2.5-pro --prompt "Analyze these performance metrics and identify optimization opportunities"
```

## Advanced Features

### Large Context Processing
- Use 1M token context for analyzing entire codebases
- Process multiple files simultaneously for comprehensive analysis
- Maintain context across long analysis sessions

### Multimodal Capabilities
- Analyze screenshots of UI/UX issues
- Process architecture diagrams and flowcharts
- Review visual performance metrics and charts

### Thinking Mode
- Enable deep reasoning for complex analysis problems
- Use structured thinking for architectural decisions
- Leverage advanced reasoning for optimization strategies

## Troubleshooting
- If analysis seems incomplete, leverage the large context window
- For complex problems, use thinking mode for deeper reasoning
- Always store analysis results in Chroma for future reference
- Use Zen MCP to orchestrate comprehensive analysis workflows
- Validate findings with other models for accuracy
