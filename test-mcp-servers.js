#!/usr/bin/env node

const { spawn } = require('child_process');
const fs = require('fs');

console.log('🧪 Testing Individual MCP Servers...\n');

// Test configurations for each MCP server
const mcpTests = [
    {
        name: 'Filesystem Server',
        command: 'npx',
        args: ['-y', '@modelcontextprotocol/server-filesystem', 'C:\\Users\\hyper\\fwber\\'],
        timeout: 5000
    },
    {
        name: 'Memory Server',
        command: 'npx',
        args: ['-y', '@modelcontextprotocol/server-memory'],
        timeout: 5000
    },
    {
        name: 'Sequential Thinking',
        command: 'npx',
        args: ['-y', '@modelcontextprotocol/server-sequential-thinking'],
        timeout: 5000
    },
    {
        name: 'Codex MCP Server',
        command: 'npx',
        args: ['-y', 'codex-mcp-server'],
        timeout: 5000
    },
    {
        name: 'Gemini MCP Tool',
        command: 'npx',
        args: ['-y', 'gemini-mcp-tool'],
        timeout: 5000
    },
    {
        name: 'Everything Server',
        command: 'npx',
        args: ['-y', '@modelcontextprotocol/server-everything'],
        timeout: 5000
    },
    {
        name: 'Puppeteer Server',
        command: 'npx',
        args: ['-y', 'puppeteer-mcp-server'],
        timeout: 5000
    },
    {
        name: 'Smart Crawler',
        command: 'npx',
        args: ['-y', 'mcp-smart-crawler'],
        timeout: 5000
    },
    {
        name: 'Playwright',
        command: 'npx',
        args: ['-y', '@playwright/mcp@latest'],
        timeout: 5000
    },
    {
        name: 'Chrome DevTools',
        command: 'npx',
        args: ['-y', 'chrome-devtools-mcp@latest'],
        timeout: 5000
    },
    {
        name: 'Terry',
        command: 'npx',
        args: ['-y', 'terry-mcp'],
        timeout: 5000
    },
    {
        name: 'PostgreSQL',
        command: 'npx',
        args: ['-y', 'enhanced-postgres-mcp-server', 'postgresql://user:password@localhost:5432/fwber'],
        timeout: 5000
    }
];

async function testMcpServer(test) {
    return new Promise((resolve) => {
        console.log(`🔍 Testing ${test.name}...`);
        
        const child = spawn(test.command, test.args, {
            stdio: ['pipe', 'pipe', 'pipe'],
            shell: true
        });

        let output = '';
        let errorOutput = '';
        let hasStarted = false;

        child.stdout.on('data', (data) => {
            output += data.toString();
            if (!hasStarted && (output.includes('ready') || output.includes('listening') || output.includes('started'))) {
                hasStarted = true;
                console.log(`✅ ${test.name}: Started successfully`);
                child.kill('SIGTERM');
                resolve({ name: test.name, status: 'success', output: output.substring(0, 200) });
            }
        });

        child.stderr.on('data', (data) => {
            errorOutput += data.toString();
        });

        child.on('error', (error) => {
            console.log(`❌ ${test.name}: Failed to start - ${error.message}`);
            resolve({ name: test.name, status: 'error', error: error.message });
        });

        child.on('close', (code) => {
            if (!hasStarted) {
                if (code === 0) {
                    console.log(`✅ ${test.name}: Completed successfully`);
                    resolve({ name: test.name, status: 'success', output: output.substring(0, 200) });
                } else {
                    console.log(`❌ ${test.name}: Exited with code ${code}`);
                    resolve({ name: test.name, status: 'error', error: `Exit code: ${code}`, stderr: errorOutput.substring(0, 200) });
                }
            }
        });

        // Timeout after specified time
        setTimeout(() => {
            if (!hasStarted) {
                child.kill('SIGTERM');
                console.log(`⏰ ${test.name}: Timeout after ${test.timeout}ms`);
                resolve({ name: test.name, status: 'timeout', output: output.substring(0, 200) });
            }
        }, test.timeout);
    });
}

async function runAllTests() {
    console.log('🚀 Starting MCP Server Tests...\n');
    
    const results = [];
    
    for (const test of mcpTests) {
        const result = await testMcpServer(test);
        results.push(result);
        console.log(''); // Add spacing between tests
    }
    
    // Summary
    console.log('📊 TEST RESULTS SUMMARY:');
    console.log('========================\n');
    
    const successful = results.filter(r => r.status === 'success');
    const failed = results.filter(r => r.status === 'error');
    const timeout = results.filter(r => r.status === 'timeout');
    
    console.log(`✅ Successful: ${successful.length}/${results.length}`);
    successful.forEach(r => console.log(`   - ${r.name}`));
    
    if (failed.length > 0) {
        console.log(`\n❌ Failed: ${failed.length}/${results.length}`);
        failed.forEach(r => console.log(`   - ${r.name}: ${r.error}`));
    }
    
    if (timeout.length > 0) {
        console.log(`\n⏰ Timeout: ${timeout.length}/${results.length}`);
        timeout.forEach(r => console.log(`   - ${r.name}`));
    }
    
    console.log(`\n🎯 Overall Success Rate: ${successful.length}/${results.length} (${Math.round(successful.length/results.length*100)}%)`);
    
    // Save detailed results
    fs.writeFileSync('mcp-test-results.json', JSON.stringify(results, null, 2));
    console.log('\n📄 Detailed results saved to mcp-test-results.json');
}

runAllTests().catch(console.error);
