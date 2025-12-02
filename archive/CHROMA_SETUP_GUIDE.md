# Chroma Database Setup Guide

## ✅ Installation Complete

Chroma has been successfully installed and is running via Docker!

## 🚀 Quick Start

### Current Status
- **Container Name**: `chroma-db`
- **Port**: `8000`
- **Status**: ✅ Running
- **API URL**: `http://localhost:8000`
- **Documentation**: `http://localhost:8000/docs`

### Basic Commands

#### Start Chroma
```bash
docker start chroma-db
```

#### Stop Chroma
```bash
docker stop chroma-db
```

#### Check Status
```bash
docker ps --filter "name=chroma-db"
```

#### View Logs
```bash
docker logs chroma-db
```

#### Remove Container (if needed)
```bash
docker stop chroma-db
docker rm chroma-db
```

## 🔧 Configuration Files Created

1. **`docker-compose-chroma.yml`** - Docker Compose configuration for easy management
2. **`start-chroma.ps1`** - PowerShell script to start Chroma
3. **`test-chroma-simple.ps1`** - Simple test script

## 📚 Using Chroma

### Python Client Example
```python
import chromadb

# Initialize Chroma client
client = chromadb.HttpClient(host="localhost", port=8000)

# Create a collection
collection = client.create_collection("my_documents")

# Add documents
collection.add(
    documents=["This is a document about AI", "Another document about machine learning"],
    metadatas=[{"source": "doc1"}, {"source": "doc2"}],
    ids=["id1", "id2"]
)

# Query the collection
results = collection.query(
    query_texts=["What is AI?"],
    n_results=2
)
```

### JavaScript/Node.js Client Example
```javascript
import { ChromaClient } from 'chromadb'

const client = new ChromaClient({
    path: "http://localhost:8000"
})

// Create a collection
const collection = await client.createCollection({
    name: "my_documents"
})

// Add documents
await collection.add({
    documents: ["This is a document about AI", "Another document about machine learning"],
    metadatas: [{source: "doc1"}, {source: "doc2"}],
    ids: ["id1", "id2"]
})

// Query the collection
const results = await collection.query({
    queryTexts: ["What is AI?"],
    nResults: 2
})
```

## 🎯 Common Use Cases

### 1. Document Search and Retrieval
- Store documents as embeddings
- Perform semantic search
- Retrieve relevant documents based on queries

### 2. AI Chat Applications
- Store conversation history
- Retrieve relevant context for responses
- Maintain long-term memory

### 3. RAG (Retrieval-Augmented Generation)
- Store knowledge base as embeddings
- Retrieve relevant information for AI generation
- Improve AI responses with context

## 🔍 API Endpoints

- **Health Check**: `GET /api/v1/heartbeat`
- **Version**: `GET /api/v1/version`
- **Collections**: `GET /api/v1/collections`
- **Documentation**: `GET /docs`

## 🛠️ Advanced Configuration

### Environment Variables
- `CHROMA_SERVER_HOST`: Server host (default: 0.0.0.0)
- `CHROMA_SERVER_HTTP_PORT`: Server port (default: 8000)
- `CHROMA_SERVER_GRPC_PORT`: gRPC port (default: 50051)

### Persistent Storage
The current setup uses Docker volumes for persistent storage. Data will persist between container restarts.

### Scaling
For production use, consider:
- Using a proper database backend (PostgreSQL, ClickHouse)
- Setting up authentication
- Configuring SSL/TLS
- Setting up monitoring and logging

## 🚨 Troubleshooting

### Container Won't Start
```bash
# Check Docker logs
docker logs chroma-db

# Check if port is in use
netstat -an | findstr :8000
```

### Connection Issues
```bash
# Test connectivity
curl http://localhost:8000/api/v1/version

# Check container status
docker ps --filter "name=chroma-db"
```

### Performance Issues
- Monitor memory usage: `docker stats chroma-db`
- Check logs for errors: `docker logs chroma-db`
- Consider increasing Docker memory limits

## 🎉 Next Steps

1. **Test the API**: Visit `http://localhost:8000/docs` to explore the API
2. **Install Client Libraries**: Install Chroma client for your preferred language
3. **Create Your First Collection**: Start storing and querying documents
4. **Integrate with Your AI Applications**: Use Chroma for RAG, search, or memory

Chroma is now ready to use for your AI applications! 🚀
