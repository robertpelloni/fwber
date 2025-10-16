#!/usr/bin/env node

/**
 * Unified AI Orchestrator - Secure Version
 * Consolidates all AI tools and MCP servers into a single process
 * Uses environment variables for API keys (no hardcoded keys)
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

console.log('🚀 Starting Unified AI Orchestrator (Secure Version)...');
console.log('📊 This will consolidate all AI models and MCP servers into minimal processes');

// Environment variables for all tools (from environment, not hardcoded)
const env = {
    ...process.env,
    // Ensure required environment variables are set
    NODE_ENV: process.env.NODE_ENV || "development",
    PYTHONPATH: process.env.PYTHONPATH || "C:\\Python313\\Lib\\site-packages",
    UV_CACHE_DIR: process.env.UV_CACHE_DIR || "C:\\Users\\hyper\\.cache\\uv",
    UV_PYTHON: process.env.UV_PYTHON || "C:\\Python313\\python.exe",
    WSLENV: process.env.WSLENV || "PATH/u:NODE_PATH/u:PYTHONPATH/u",
    IJ_MCP_SERVER_PORT: process.env.IJ_MCP_SERVER_PORT || "64342"
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

const missingKeys = requiredKeys.filter(key => !process.env[key]);
if (missingKeys.length > 0) {
    console.log('⚠️  Missing environment variables:');
    missingKeys.forEach(key => console.log(`   - ${key}`));
    console.log('💡 Set these environment variables before running the orchestrator');
    console.log('   Example: set OPENAI_API_KEY=your_key_here');
}

// Unified MCP server configurations
const mcpServers = {
    // Core AI Tools (commented out due to Python version conflicts)
    // "serena": {
    //     command: "uv",
    //     args: ["run", "--directory", "C:\\Users\\hyper\\serena\\", "serena", "start-mcp-server", "--context", "unified-orchestrator", "--project", "C:\\Users\\hyper\\fwber\\"],
    //     priority: 1
    // },
    // "zen-mcp-server": {
    //     command: "uv",
    //     args: ["run", "--directory", "C:\\Users\\hyper\\zen-mcp-server\\", "zen-mcp-server"],
    //     priority: 1
    // },
    
    // Essential MCP Servers
    "filesystem": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-filesystem", "C:\\Users\\hyper\\fwber\\"],
        priority: 2
    },
    "memory": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-memory"],
        priority: 2
    },
    "sequential-thinking": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-sequential-thinking"],
        priority: 2
    },
    
    // AI Model MCP Servers
    "codex-mcp-server": {
        command: "npx",
        args: ["-y", "codex-mcp-server"],
        priority: 3
    },
    "gemini-mcp-tool": {
        command: "npx",
        args: ["-y", "gemini-mcp-tool"],
        priority: 3
    },
    
    // Development Tools
    "everything": {
        command: "npx",
        args: ["-y", "@modelcontextprotocol/server-everything"],
        priority: 4
    },
    "puppeteer": {
        command: "npx",
        args: ["-y", "puppeteer-mcp-server"],
        priority: 4
    },
    "smart-crawler": {
        command: "npx",
        args: ["-y", "mcp-smart-crawler"],
        priority: 4
    },
    "playwright": {
        command: "npx",
        args: ["-y", "@playwright/mcp@latest"],
        priority: 4
    },
    "chrome-devtools": {
        command: "npx",
        args: ["-y", "chrome-devtools-mcp@latest"],
        priority: 4
    },
    "terry": {
        command: "npx",
        args: ["-y", "terry-mcp"],
        priority: 4
    },
    
    // Database
    "postgres": {
        command: "npx",
        args: ["-y", "enhanced-postgres-mcp-server", "postgresql://user:password@localhost:5432/fwber"],
        priority: 5
    }
};

// JetBrains IDE Server (commented out due to path issues)
// const jetbrainsServer = {
//     command: "cmd",
//     args: [
//         "/c",
//         "\"C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\jbr\\bin\\java\"",
//         "-classpath",
//         "\"C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\plugins\\mcpserver\\lib\\mcpserver-frontend.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\util-8.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.ktor.client.cio.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.ktor.client.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.ktor.network.tls.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.ktor.io.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.ktor.utils.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.kotlinx.io.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.kotlinx.serialization.core.jar;C:\\Program Files\\JetBrains\\WebStorm 253.25908.30\\lib\\module-intellij.libraries.kotlinx.serialization.json.jar\"",
//         "com.intellij.mcpserver.stdio.McpStdioRunnerKt"
//     ],
//     env: { ...env, IJ_MCP_SERVER_PORT: "64342" }
// };

const runningProcesses = {};
const processStats = {
    started: 0,
    failed: 0,
    running: 0
};

function startMcpServer(name, config, priority = 5) {
    return new Promise((resolve, reject) => {
        console.log(`🚀 Starting MCP server: ${name} (priority: ${priority})`);
        
        const child = spawn(config.command, config.args, {
            stdio: ['pipe', 'pipe', 'pipe', 'ipc'],
            env: { ...env, ...config.env },
            shell: true,
            windowsHide: true
        });

        runningProcesses[name] = child;
        processStats.started++;

        // Set up logging with priority-based formatting
        const priorityColors = {
            1: '\x1b[32m', // Green for high priority
            2: '\x1b[36m', // Cyan for medium priority  
            3: '\x1b[33m', // Yellow for low priority
            4: '\x1b[35m', // Magenta for dev tools
            5: '\x1b[37m'  // White for database
        };
        const color = priorityColors[priority] || '\x1b[37m';
        const reset = '\x1b[0m';

        child.stdout.on('data', (data) => {
            console.log(`${color}[${name}][stdout]:${reset} ${data.toString().trim()}`);
        });

        child.stderr.on('data', (data) => {
            console.error(`${color}[${name}][stderr]:${reset} ${data.toString().trim()}`);
        });

        child.on('error', (error) => {
            console.error(`❌ [${name}][error]: ${error.message}`);
            processStats.failed++;
            delete runningProcesses[name];
            reject(error);
        });

        child.on('close', (code) => {
            console.log(`🔚 MCP server ${name} exited with code ${code}`);
            processStats.running--;
            delete runningProcesses[name];
            if (code === 0) {
                resolve();
            } else {
                reject(new Error(`Process exited with code ${code}`));
            }
        });

        // Give the process time to start
        setTimeout(() => {
            if (child.exitCode === null) {
                processStats.running++;
                console.log(`✅ [${name}] started successfully`);
                resolve();
            }
        }, 2000);
    });
}

async function startAllServers() {
    console.log('📋 Starting MCP servers in priority order...');
    
    // Group servers by priority
    const serversByPriority = {};
    for (const [name, config] of Object.entries(mcpServers)) {
        const priority = config.priority || 5;
        if (!serversByPriority[priority]) {
            serversByPriority[priority] = [];
        }
        serversByPriority[priority].push([name, config]);
    }

    // Start servers by priority
    for (const priority of [1, 2, 3, 4, 5]) {
        if (serversByPriority[priority]) {
            console.log(`\n🎯 Starting Priority ${priority} servers...`);
            
            // Start servers in parallel within each priority
            const promises = serversByPriority[priority].map(([name, config]) => 
                startMcpServer(name, config, priority).catch(error => {
                    console.error(`⚠️  Failed to start ${name}: ${error.message}`);
                })
            );
            
            await Promise.allSettled(promises);
            
            // Small delay between priority groups
            if (priority < 5) {
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }
    }

    // JetBrains server commented out due to path issues
    // console.log('\n🎯 Starting JetBrains IDE server...');
    // try {
    //     await startMcpServer('jetbrains', jetbrainsServer, 1);
    // } catch (error) {
    //     console.error(`⚠️  Failed to start JetBrains server: ${error.message}`);
    // }

    console.log('\n📊 Server startup summary:');
    console.log(`   Started: ${processStats.started}`);
    console.log(`   Running: ${processStats.running}`);
    console.log(`   Failed: ${processStats.failed}`);
    console.log(`\n🎉 Unified AI Orchestrator is running!`);
    console.log(`💡 Use Ctrl+C to stop all servers gracefully`);
}

// Graceful shutdown
function shutdown() {
    console.log('\n🛑 Shutting down all MCP servers...');
    
    const shutdownPromises = Object.entries(runningProcesses).map(([name, process]) => {
        return new Promise((resolve) => {
            console.log(`🔚 Stopping ${name}...`);
            process.kill('SIGTERM');
            
            // Force kill after 5 seconds
            setTimeout(() => {
                if (!process.killed) {
                    console.log(`⚡ Force killing ${name}...`);
                    process.kill('SIGKILL');
                }
                resolve();
            }, 5000);
            
            process.on('close', () => resolve());
        });
    });
    
    Promise.all(shutdownPromises).then(() => {
        console.log('✅ All servers stopped successfully');
        process.exit(0);
    });
}

// Handle shutdown signals
process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

// Start the orchestrator
startAllServers().catch(error => {
    console.error('❌ Failed to start orchestrator:', error);
    process.exit(1);
});

// Health check endpoint (optional) - use dynamic port
const http = require('http');
const server = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'healthy',
            running: processStats.running,
            started: processStats.started,
            failed: processStats.failed,
            servers: Object.keys(runningProcesses)
        }));
    } else {
        res.writeHead(404);
        res.end('Not found');
    }
});

// Use dynamic port to avoid conflicts
const port = process.env.ORCHESTRATOR_PORT || 8081;
server.listen(port, () => {
    console.log(`🏥 Health check endpoint available at http://localhost:${port}/health`);
});
