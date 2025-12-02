# Chroma MCP Server Integration Guide

**Date:** 2025-01-27  
**Project:** FWBer Multi-AI Orchestration  
**Status:** ✅ Configuration Complete, ⚠️ Installation Requires Resolution

## Overview

The Chroma knowledge server has been successfully integrated into all MCP configuration files. However, due to Python dependency conflicts in the current environment, the server requires manual dependency resolution to function properly.

## ✅ What's Been Completed

### 1. Configuration Integration
The `chroma-knowledge` server has been added to all MCP configuration files:

- **Enhanced MCP Settings**: `tools_config_files/enhanced_mcp_settings.json`
- **Unified Orchestrators**: `unified-ai-orchestrator-secure.js`, `unified-ai-orchestrator-optimized.js`
- **Template Configs**: `tools_config_files/claude_mcp_template.json`, `tools_config_files/copilot_mcp_template.json`

### 2. Configuration Details
```json
{
  "chroma-knowledge": {
    "type": "stdio",
    "command": "python",
    "args": ["-m", "chroma_mcp_server"],
    "description": "Chroma vector database for semantic search and knowledge management",
    "enabled": true,
    "priority": 3,
    "env": {
      "CHROMA_CLIENT_TYPE": "http",
      "CHROMA_HOST": "localhost",
      "CHROMA_PORT": "8000"
    }
  }
}
```

### 3. Server Capabilities
- **Vector Search**: Semantic document retrieval using embeddings
- **Knowledge Management**: CRUD operations for documents and collections
- **Metadata Support**: Custom metadata fields and filtering
- **Persistent Storage**: SQLite backend for reliable storage

## ⚠️ Installation Status

### Current Issue
The `chroma-mcp-server` package installation fails due to dependency conflicts:
- **numpy version conflict**: Package requires numpy<2.0.0, but system has numpy 2.3.4
- **onnxruntime dependency**: Missing or incompatible version
- **Build failures**: DuckDB and other native dependencies fail to compile

### Installation Attempts Made
1. ✅ `pip install chroma-mcp-server` - Failed due to dependency conflicts
2. ✅ `pip install --no-deps chroma-mcp-server` - Package installed but non-functional
3. ⚠️ `pip install numpy pandas` - Installed but version conflicts remain

## 🔧 Resolution Options

### Option 1: Virtual Environment (Recommended)
Create a dedicated Python environment for Chroma:

```bash
# Create virtual environment
python -m venv chroma-env
chroma-env\Scripts\activate

# Install with compatible versions
pip install numpy==1.26.4
pip install chroma-mcp-server
```

### Option 2: Alternative Chroma Server
Use a different Chroma MCP implementation:

```bash
# Alternative package
pip install chromadb
# Use direct ChromaDB client instead of MCP server
```

### Option 3: Docker Container
Run Chroma in a Docker container:

```dockerfile
FROM chromadb/chroma:latest
EXPOSE 8000
CMD ["chroma", "run", "--host", "0.0.0.0", "--port", "8000"]
```

### Option 4: Manual Dependency Resolution
```bash
# Install specific compatible versions
pip install numpy==1.26.4
pip install chromadb==0.4.22
pip install fastapi==0.115.0
pip install onnxruntime==1.21.0
pip install sentence-transformers==2.2.2
pip install transformers==4.41.0
```

## 🚀 Usage Once Installed

### 1. Start Chroma Server
```bash
# Start Chroma server
chroma run --host localhost --port 8000

# Or with persistent storage
chroma run --host localhost --port 8000 --persist-directory ./chroma_data
```

### 2. Verify MCP Integration
The Chroma MCP server will be available through your configured MCP clients:
- **Cursor IDE**: Already configured in enhanced_mcp_settings.json
- **Claude CLI**: Available through claude_mcp_template.json
- **Copilot CLI**: Available through copilot_mcp_template.json

### 3. Test Functionality
```python
# Test Chroma MCP server
import chromadb
client = chromadb.HttpClient(host="localhost", port=8000)
collections = client.list_collections()
print(f"Available collections: {collections}")
```

## 📋 Integration Checklist

- [x] Add Chroma server to enhanced_mcp_settings.json
- [x] Add Chroma server to unified orchestrators
- [x] Add Chroma server to template configurations
- [x] Create test script for connectivity
- [x] Document integration process
- [ ] Resolve Python dependency conflicts
- [ ] Install Chroma MCP server successfully
- [ ] Test server connectivity
- [ ] Verify MCP integration functionality

## 🔍 Configuration Files Updated

1. **`tools_config_files/enhanced_mcp_settings.json`**
   - Added chroma-knowledge server with full configuration
   - Priority 3 (AI Model Integration)
   - HTTP health check on localhost:8000

2. **`unified-ai-orchestrator-secure.js`**
   - Added chroma-knowledge to mcpServers object
   - Environment variables configured

3. **`unified-ai-orchestrator-optimized.js`**
   - Added chroma-knowledge with description
   - Priority 3 integration

4. **`tools_config_files/claude_mcp_template.json`**
   - Added chroma-knowledge server configuration
   - 30-second timeout configured

5. **`tools_config_files/copilot_mcp_template.json`**
   - Added chroma-knowledge server configuration
   - Environment variables set

## 🎯 Next Steps

1. **Choose Resolution Option**: Select one of the four resolution approaches above
2. **Install Dependencies**: Follow the chosen installation method
3. **Test Connectivity**: Run the test script to verify functionality
4. **Start Chroma Server**: Launch the Chroma server on localhost:8000
5. **Verify MCP Integration**: Test through your configured MCP clients

## 📚 Additional Resources

- **Chroma Documentation**: https://docs.trychroma.com/
- **Chroma MCP Server**: https://github.com/chroma-core/chroma-mcp
- **MCP Protocol**: https://modelcontextprotocol.io/

## 🔧 Troubleshooting

### Common Issues
1. **Dependency Conflicts**: Use virtual environment or specific version pins
2. **Build Failures**: Install Visual Studio Build Tools for native dependencies
3. **Port Conflicts**: Ensure port 8000 is available
4. **Path Issues**: Add Python Scripts directory to PATH

### Debug Commands
```bash
# Check Python version
python --version

# Check installed packages
pip list | findstr chroma

# Test Chroma server
chroma-mcp-server --help

# Check port availability
netstat -an | findstr :8000
```

---

**Note**: The Chroma MCP server configuration is complete and ready for use once the dependency conflicts are resolved. The server will provide powerful semantic search and knowledge management capabilities for your FWBer project.
