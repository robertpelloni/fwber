#!/usr/bin/env node

const fs = require('fs');
const { spawn } = require('child_process');
const http = require('http');

console.log('🚀 Starting Optimized AI Orchestrator...');
console.log('📊 Running only essential and useful MCP servers');

// Environment variables for all tools (from environment, not hardcoded)
const env = {
    ...process.env,
    // Ensure required environment variables are set
    NODE_ENV: process.env.NODE_ENV || "development",
    PYTHONPATH: process.env.PYTHONPATH || "C:\\Users\\hyper\\AppData\\Roaming\\uv\\python\\cpython-3.11.13-windows-x86_64-none\\Lib\\site-packages",
    UV_CACHE_DIR: process.env.UV_CACHE_DIR || "C:\\Users\\hyper\\.cache\\uv",
    UV_PYTHON: process.env.UV_PYTHON || "C:\\Users\\hyper\\AppData\\Roaming\\uv\\python\\cpython-3.11.13-windows-x86_64-none\\python.exe",
    WSLENV: process.env.WSLENV || "PATH/u:NODE_PATH/u:PYTHONPATH/u"
};

// Check for required API keys
const requiredKeys = [
    'OPENAI_API_KEY',
    'OPENROUTER_API_KEY',
    'GEMINI_API_KEY',
    'ANTHROPIC_API_KEY',
    'XAI_API_KEY',
    'GROQ_API_KEY'
];

let missingKeys = [];
for (const key of requiredKeys) {
    if (!env[key]) {
        missingKeys.push(key);
    }
}

if (missingKeys.length > 0) {
    console.warn(`⚠️  Missing environment variables:`);
    for (const key of missingKeys) {
        console.warn(`   - ${key}`);
    }
    console.info(`💡 Set these environment variables before running the orchestrator`);
}

// Optimized MCP server configurations - Only essential and useful servers
const mcpServers = {
    // Core AI Orchestration (Priority 1 - Most Important)
    "serena": {
        command: "uv",
        args: ["run", "--python", "3.11", "--directory", "C:\\Users\\hyper\\serena\\", "serena", "start-mcp-server", "--context", "desktop-app", "--project", "C:\\Users\\hyper\\fwber\\", "--transport", "stdio"],
        priority: 1,
        description: "Memory-based orchestration and large project file access"
    },
    "zen-mcp-server": {
        command: "uv",
        args: ["run", "--python", "3.11", "--directory", "C:\\Users\\hyper\\zen-mcp-server\\", "zen-mcp-server"],
        priority: 1,
        description: "Advanced AI orchestration, multi-model consensus, debugging, planning"
    },

    // Essential Development Tools (Priority 2)
    "filesystem": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-filesystem", "C:\\Users\\hyper\\fwber\\"],
        priority: 2,
        description: "File system access and operations"
    },
    "memory": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-memory"],
        priority: 2,
        description: "Persistent memory storage and context retention"
    },
    "sequential-thinking": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-sequential-thinking"],
        priority: 2,
        description: "Structured reasoning and step-by-step problem solving"
    },

    // AI Model Integration (Priority 3)
    "codex-mcp-server": {
        command: "npx",
        args: ["-y", "codex-mcp-server"],
        priority: 3,
        description: "Codex CLI integration as MCP server"
    },
    "gemini-mcp-tool": {
        command: "npx",
        args: ["-y", "gemini-mcp-tool"],
        priority: 3,
        description: "Gemini CLI integration as MCP server"
    },

    // Development Tools (Priority 4)
    "everything": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-everything"],
        priority: 4,
        description: "Comprehensive tool collection and utilities"
    },
    "playwright": {
        command: "npx",
        args: ["-y", "@playwright/mcp@latest"],
        priority: 4,
        description: "Browser automation and web testing"
    }
};

const runningProcesses = {};
let currentPriority = null;
const processStats = {
    started: 0,
    failed: 0,
    running: 0
};

const HTTP_PORT = process.env.ORCHESTRATOR_PORT || 8081;

async function startMcpServer(name, config, priority) {
    console.log(`🚀 Starting MCP server: ${name} (priority: ${priority})`);
    console.log(`   📝 ${config.description}`);
    processStats.started++;

    const child = spawn(config.command, config.args, {
        stdio: ['pipe', 'pipe', 'pipe', 'ipc'],
        env: { ...env, ...config.env },
        shell: true
    });

    runningProcesses[name] = child;

    child.stdout.on('data', (data) => {
        console.log(`[${name}][stdout]: ${data}`);
    });

    child.stderr.on('data', (data) => {
        console.error(`[${name}][stderr]: ${data}`);
    });

    child.on('error', (error) => {
        console.error(`[${name}][error]: ${error}`);
        processStats.failed++;
        delete runningProcesses[name];
        throw new Error(`Failed to start ${name}: ${error.message}`);
    });

    child.on('close', (code) => {
        console.log(`🔚 MCP server ${name} exited with code ${code}`);
        if (code !== 0) {
            processStats.failed++;
        }
        delete runningProcesses[name];
    });

    // Simple check to see if the process is still alive after a short delay
    await new Promise(resolve => setTimeout(resolve, 2000));
    if (runningProcesses[name]) {
        console.log(`✅ [${name}] started successfully`);
        processStats.running++;
    } else {
        console.warn(`⚠️  Failed to start ${name}: Process exited with code ${child.exitCode}`);
        processStats.failed++;
        throw new Error(`Failed to start ${name}: Process exited with code ${child.exitCode}`);
    }
}

async function startAllMcpServers() {
    console.log('📋 Starting optimized MCP servers in priority order...');
    console.log('🎯 Only running essential and useful servers\n');

    const sortedServers = Object.entries(mcpServers).sort(([, a], [, b]) => a.priority - b.priority);

    for (const [name, config] of sortedServers) {
        const priority = config.priority;
        if (!currentPriority || priority !== currentPriority) {
            console.log(`🎯 Starting Priority ${priority} servers...`);
            currentPriority = priority;
        }
        try {
            await startMcpServer(name, config, priority);
        } catch (error) {
            console.error(error.message);
        }
    }

    console.log('\n📊 Optimized server startup summary:');
    console.log(`   Started: ${processStats.started}`);
    console.log(`   Running: ${processStats.running}`);
    console.log(`   Failed: ${processStats.failed}`);

    console.log('\n🎉 Optimized AI Orchestrator is running!');
    console.log('💡 Use Ctrl+C to stop all servers gracefully');
}

// Start all configured MCP servers
startAllMcpServers();

// Simple HTTP server for health check
const server = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'healthy',
            running: processStats.running,
            started: processStats.started,
            failed: processStats.failed,
            servers: Object.keys(runningProcesses),
            description: 'Optimized AI Orchestrator with essential MCP servers only'
        }));
    } else {
        res.writeHead(404);
        res.end();
    }
});

server.listen(HTTP_PORT, () => {
    console.log(`🏥 Health check endpoint available at http://localhost:${HTTP_PORT}/health`);
});

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('\nShutting down all MCP servers and HTTP server...');
    for (const name in runningProcesses) {
        runningProcesses[name].kill('SIGINT');
    }
    server.close(() => {
        console.log('HTTP server closed.');
        process.exit();
    });
});

process.on('SIGTERM', () => {
    console.log('\nShutting down all MCP servers and HTTP server...');
    for (const name in runningProcesses) {
        runningProcesses[name].kill('SIGTERM');
    }
    server.close(() => {
        console.log('HTTP server closed.');
        process.exit();
    });
});
