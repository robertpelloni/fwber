# Multi-AI Orchestration Workflow Guide

## Overview
This guide provides comprehensive instructions for using multiple AI models and MCP servers in parallel to achieve optimal results for planning, designing, and implementing software projects.

## Core Principles (Consensus-Enhanced)

### 1. Intelligent Model Routing (Not Always Parallel)
- **High-Stakes Tasks**: Use primary model + 2-3 other models for consensus
- **Medium Complexity**: Use primary model + 1 validator model
- **Simple Tasks**: Single model approach with quality gates
- **Progressive Escalation**: Start with efficient models, escalate when complexity warrants

### 2. Enhanced MCP Server Usage
- **Zen MCP**: Dynamic routing with confidence-based validation and quality gates
- **Serena MCP**: Hierarchical memory (immediate/project/cross-project) with automatic context tagging
- **Chroma MCP**: Enhanced semantic search with code-aware chunking and contextual filtering

### 3. Quality-Gated Validation
- **Quality Gates**: Automated validation before consensus building
- **Confidence-Based Routing**: Use model confidence scores to determine validation needs
- **Execution-Based Validation**: Primary quality measure through automated testing
- **Progressive Fidelity**: Fast pass with strict gates, escalate on failure

## Model Specializations (Consensus-Defined)

### Claude (Anthropic) - **Architect/Synthesizer**
- **Primary Role**: Complex reasoning, architectural design, task decomposition, synthesizing outputs
- **Specialization Zones**: Complex architecture, large codebases, nuanced requirements, security analysis, algorithmic optimization
- **Best for**: System design, code review, documentation, high-level integration
- **Use when**: Need comprehensive analysis, quality validation, or architectural decisions

### GPT-5-Codex (OpenAI) - **Technical Specialist**
- **Primary Role**: Code-intensive tasks, deep domain knowledge, technical implementation
- **Specialization Zones**: Code generation, debugging, refactoring, testing, technical documentation, API development
- **Best for**: Writing code, debugging, technical implementation, security implementation
- **Use when**: Need to implement features, fix code issues, or perform technical analysis

### Gemini 2.5 Pro (Google) - **Performance Specialist**
- **Primary Role**: Performance analysis, large context processing, optimization
- **Specialization Zones**: Performance optimization, architecture analysis, data analysis, multimodal processing
- **Best for**: Performance optimization, large codebase analysis, data analysis
- **Use when**: Need to analyze large codebases, performance issues, or data processing

### Grok 4 (X.AI) - **Creative Specialist**
- **Primary Role**: Creative problem-solving, UX/UI design, alternative approaches
- **Specialization Zones**: Innovation, user experience, challenging conventional solutions, creative problem-solving
- **Best for**: Innovation, user experience, alternative approaches, creative solutions
- **Use when**: Need creative solutions, alternative approaches, or UX/UI improvements

## Standard Workflows

### 1. Project Planning Workflow
```bash
# Step 1: Get input from multiple models
mcp_zen-mcp-server_planner --model claude-4.5 --step "Define project scope and requirements"
mcp_zen-mcp-server_planner --model gemini-2.5-pro --step "Analyze technical feasibility"
mcp_zen-mcp-server_planner --model grok-4 --step "Explore innovative approaches"

# Step 2: Build consensus
mcp_zen-mcp-server_consensus --models '[{"model":"claude-4.5","stance":"neutral"},{"model":"gemini-2.5-pro","stance":"for"},{"model":"grok-4","stance":"against"}]' --step "Finalize project plan"

# Step 3: Store in knowledge base
mcp_chroma-knowledge_chroma_add_document --collection_name "project_planning" --document "Comprehensive project plan with multi-model input"
```

### 2. Architecture Design Workflow
```bash
# Step 1: Multi-model architecture analysis
mcp_zen-mcp-server_analyze --model claude-4.5 --step "Analyze system architecture requirements"
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Evaluate performance implications"
mcp_zen-mcp-server_analyze --model gpt-5-codex --step "Assess implementation feasibility"

# Step 2: Consensus building
mcp_zen-mcp-server_consensus --models '[{"model":"claude-4.5"},{"model":"gemini-2.5-pro"},{"model":"gpt-5-codex"}]' --step "Finalize architecture decisions"

# Step 3: Store architecture decisions
mcp_chroma-knowledge_chroma_add_document --collection_name "architecture" --document "Architecture decisions with multi-model validation"
```

### 3. Code Implementation Workflow
```bash
# Step 1: Get implementation guidance
mcp_zen-mcp-server_chat --model gpt-5-codex --prompt "Generate secure authentication system"
mcp_zen-mcp-server_chat --model claude-4.5 --prompt "Review security best practices"

# Step 2: Validate implementation
mcp_zen-mcp-server_codereview --model claude-4.5 --step "Security and quality review"
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Performance analysis"

# Step 3: Store implementation
mcp_chroma-knowledge_chroma_add_document --collection_name "code_solutions" --document "Secure authentication implementation with validation"
```

### 4. Security Audit Workflow
```bash
# Step 1: Multi-model security analysis
mcp_zen-mcp-server_secaudit --model gpt-5-codex --step "Comprehensive security audit"
mcp_zen-mcp-server_secaudit --model claude-4.5 --step "Security best practices review"

# Step 2: Consensus on security findings
mcp_zen-mcp-server_consensus --models '[{"model":"gpt-5-codex"},{"model":"claude-4.5"}]' --step "Validate security findings"

# Step 3: Store security analysis
mcp_chroma-knowledge_chroma_add_document --collection_name "security_audits" --document "Security audit results with multi-model validation"
```

### 5. Performance Optimization Workflow
```bash
# Step 1: Performance analysis
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Identify performance bottlenecks"
mcp_zen-mcp-server_analyze --model gpt-5-codex --step "Evaluate optimization strategies"

# Step 2: Implementation planning
mcp_zen-mcp-server_consensus --models '[{"model":"gemini-2.5-pro"},{"model":"gpt-5-codex"}]' --step "Finalize optimization approach"

# Step 3: Store optimization plan
mcp_chroma-knowledge_chroma_add_document --collection_name "performance" --document "Performance optimization plan with multi-model analysis"
```

## Memory Management Strategies

### Serena Memory Usage
```bash
# Store project context
mcp_serena_write_memory --memory_name "project_context" --content "FWBer is a dating platform with PHP/Laravel backend and Next.js frontend"

# Store user preferences
mcp_serena_write_memory --memory_name "user_preferences" --content "User prefers security-first approach and performance optimization"

# Store lessons learned
mcp_serena_write_memory --memory_name "lessons_learned" --content "Key insights from previous development cycles"

# Retrieve context when needed
mcp_serena_read_memory --memory_file_name "project_context"
```

### Chroma Knowledge Base Usage
```bash
# Create project collections
mcp_chroma-knowledge_chroma_create_collection --collection_name "fwber_project"
mcp_chroma-knowledge_chroma_create_collection --collection_name "code_solutions"
mcp_chroma-knowledge_chroma_create_collection --collection_name "architecture_decisions"

# Store analysis results
mcp_chroma-knowledge_chroma_add_document --collection_name "fwber_project" --document "Comprehensive project analysis..."

# Semantic search
mcp_chroma-knowledge_chroma_query_documents --collection_name "fwber_project" --query_texts '["authentication system"]'
```

## Quality Assurance Process

### 1. Multi-Model Validation
- **Primary Model**: Performs the main task
- **Secondary Model**: Reviews and challenges the work
- **Consensus Building**: Resolves disagreements through structured debate
- **Final Validation**: Stores results and updates memory

### 2. Knowledge Storage
- **All analysis results** stored in Chroma knowledge base
- **Project context** maintained in Serena memory
- **Lessons learned** captured for future reference
- **Decision rationale** documented for transparency

### 3. Continuous Improvement
- **Track what works** and what doesn't
- **Update memory** with successful patterns
- **Learn from mistakes** and avoid repetition
- **Share insights** across the team

## Example: Complete FWBer Analysis Workflow

### Phase 1: Project Understanding
```bash
# Get project context from Serena
mcp_serena_read_memory --memory_file_name "fwber_context"

# Multi-model project analysis
mcp_zen-mcp-server_analyze --model claude-4.5 --step "Analyze FWBer architecture and design patterns"
mcp_zen-mcp-server_analyze --model gemini-2.5-pro --step "Evaluate FWBer performance characteristics"
mcp_zen-mcp-server_analyze --model gpt-5-codex --step "Assess FWBer code quality and security"
mcp_zen-mcp-server_analyze --model grok-4 --step "Explore innovative FWBer features and UX improvements"
```

### Phase 2: Consensus Building
```bash
# Build consensus on findings
mcp_zen-mcp-server_consensus --models '[{"model":"claude-4.5"},{"model":"gemini-2.5-pro"},{"model":"gpt-5-codex"},{"model":"grok-4"}]' --step "Synthesize FWBer analysis findings"
```

### Phase 3: Knowledge Storage
```bash
# Store comprehensive analysis
mcp_chroma-knowledge_chroma_add_document --collection_name "fwber_project" --document "Multi-model FWBer analysis with consensus findings"

# Update project memory
mcp_serena_write_memory --memory_name "fwber_analysis" --content "Key findings and recommendations from comprehensive analysis"
```

## Best Practices

### 1. Always Use Multiple Models
- Never rely on a single model's opinion
- Get at least 2-3 different perspectives
- Use consensus building to resolve differences

### 2. Leverage MCP Servers
- Use Zen MCP for orchestration and consensus
- Use Serena MCP for code access and memory
- Use Chroma MCP for knowledge storage and retrieval

### 3. Maintain Context
- Store project context in Serena memory
- Keep analysis results in Chroma knowledge base
- Update memory with lessons learned

### 4. Validate Everything
- Have other models review your work
- Use consensus building for important decisions
- Store validation results for future reference

### 5. Learn and Improve
- Track successful patterns
- Learn from mistakes
- Share insights across the team
- Continuously improve workflows

## Troubleshooting

### Common Issues
1. **Model disagreements**: Use consensus building to resolve
2. **Missing context**: Check Serena memory and Chroma knowledge base
3. **Incomplete analysis**: Ensure all models have been consulted
4. **Storage issues**: Verify MCP server connections

### Solutions
- Always use multi-model validation
- Maintain comprehensive memory systems
- Use structured workflows for complex tasks
- Leverage consensus building for important decisions
