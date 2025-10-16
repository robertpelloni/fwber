# Test All MCP Servers Across CLI Tools
# This script tests MCP server functionality across Copilot, Codex, and Gemini CLI tools

Write-Host "=== Testing MCP Servers Across All CLI Tools ===" -ForegroundColor Green
Write-Host "Date: $(Get-Date)" -ForegroundColor Gray
Write-Host ""

# Test Results Array
$testResults = @()

# Function to test MCP server availability
function Test-MCPServer {
    param(
        [string]$ServerName,
        [string]$Command,
        [string]$Args = ""
    )
    
    Write-Host "Testing $ServerName..." -ForegroundColor Yellow
    
    try {
        $result = Invoke-Expression "$Command $Args" 2>&1
        $success = $LASTEXITCODE -eq 0
        
        $testResults += @{
            Server = $ServerName
            Command = $Command
            Success = $success
            Output = $result
            Timestamp = Get-Date
        }
        
        if ($success) {
            Write-Host "  ✅ $ServerName - SUCCESS" -ForegroundColor Green
        } else {
            Write-Host "  ❌ $ServerName - FAILED" -ForegroundColor Red
            Write-Host "    Error: $result" -ForegroundColor Red
        }
    } catch {
        $testResults += @{
            Server = $ServerName
            Command = $Command
            Success = $false
            Output = $_.Exception.Message
            Timestamp = Get-Date
        }
        Write-Host "  ❌ $ServerName - ERROR" -ForegroundColor Red
        Write-Host "    Exception: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
}

# Test CLI Tool Versions
Write-Host "=== CLI Tool Versions ===" -ForegroundColor Cyan
try {
    $copilotVersion = copilot --version 2>&1
    Write-Host "Copilot CLI: $copilotVersion" -ForegroundColor White
} catch {
    Write-Host "Copilot CLI: Not available" -ForegroundColor Red
}

try {
    $codexVersion = codex --version 2>&1
    Write-Host "Codex CLI: $codexVersion" -ForegroundColor White
} catch {
    Write-Host "Codex CLI: Not available" -ForegroundColor Red
}

try {
    $geminiVersion = gemini --version 2>&1
    Write-Host "Gemini CLI: $geminiVersion" -ForegroundColor White
} catch {
    Write-Host "Gemini CLI: Not available" -ForegroundColor Red
}

Write-Host ""

# Test Prerequisites
Write-Host "=== Prerequisites Check ===" -ForegroundColor Cyan
Test-MCPServer "UV" "uv --version"
Test-MCPServer "NPX" "npx --version"
Test-MCPServer "Node.js" "node --version"

# Test Serena Directory
if (Test-Path "C:\Users\hyper\serena\") {
    Write-Host "  ✅ Serena Directory - EXISTS" -ForegroundColor Green
} else {
    Write-Host "  ❌ Serena Directory - MISSING" -ForegroundColor Red
}

# Test Zen MCP Server Directory
if (Test-Path "C:\Users\hyper\zen-mcp-server\") {
    Write-Host "  ✅ Zen MCP Server Directory - EXISTS" -ForegroundColor Green
} else {
    Write-Host "  ❌ Zen MCP Server Directory - MISSING" -ForegroundColor Red
}

Write-Host ""

# Test MCP Server Packages
Write-Host "=== MCP Server Package Tests ===" -ForegroundColor Cyan
Test-MCPServer "Sequential Thinking" "npx" "-y @modelcontextprotocol/server-sequential-thinking --help"
Test-MCPServer "Filesystem Server" "npx" "-y @modelcontextprotocol/server-filesystem --help"
Test-MCPServer "Memory Server" "npx" "-y @modelcontextprotocol/server-memory --help"
Test-MCPServer "Everything Server" "npx" "-y @modelcontextprotocol/server-everything --help"
Test-MCPServer "Puppeteer Server" "npx" "-y puppeteer-mcp-server --help"
Test-MCPServer "Smart Crawler" "npx" "-y mcp-smart-crawler --help"
Test-MCPServer "Playwright Server" "npx" "@playwright/mcp@latest --help"
Test-MCPServer "Chrome DevTools" "npx" "-y chrome-devtools-mcp@latest --help"
Test-MCPServer "Terry MCP" "npx" "-y terry-mcp --help"
Test-MCPServer "Codex MCP Server" "npx" "-y codex-mcp-server --help"
Test-MCPServer "Gemini MCP Tool" "npx" "-y gemini-mcp-tool --help"

# Test Serena Server
Write-Host "Testing Serena Server..." -ForegroundColor Yellow
try {
    $serenaTest = uv run --directory "C:\Users\hyper\serena\" serena --help 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Serena Server - AVAILABLE" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Serena Server - ERROR" -ForegroundColor Red
        Write-Host "    Error: $serenaTest" -ForegroundColor Red
    }
} catch {
    Write-Host "  ❌ Serena Server - EXCEPTION" -ForegroundColor Red
    Write-Host "    Exception: $($_.Exception.Message)" -ForegroundColor Red
}

# Test Zen MCP Server
Write-Host "Testing Zen MCP Server..." -ForegroundColor Yellow
try {
    $zenTest = uv run --directory "C:\Users\hyper\zen-mcp-server\" zen-mcp-server --help 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Zen MCP Server - AVAILABLE" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Zen MCP Server - ERROR" -ForegroundColor Red
        Write-Host "    Error: $zenTest" -ForegroundColor Red
    }
} catch {
    Write-Host "  ❌ Zen MCP Server - EXCEPTION" -ForegroundColor Red
    Write-Host "    Exception: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test CLI Tool MCP Integration
Write-Host "=== CLI Tool MCP Integration Tests ===" -ForegroundColor Cyan

# Test Copilot CLI
Write-Host "Testing Copilot CLI MCP Integration..." -ForegroundColor Yellow
try {
    $copilotTest = copilot -p "Test MCP server availability" --allow-all-tools 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Copilot CLI - MCP INTEGRATION WORKING" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Copilot CLI - MCP INTEGRATION FAILED" -ForegroundColor Red
    }
} catch {
    Write-Host "  ❌ Copilot CLI - MCP INTEGRATION ERROR" -ForegroundColor Red
}

# Test Gemini CLI
Write-Host "Testing Gemini CLI MCP Integration..." -ForegroundColor Yellow
try {
    $geminiTest = gemini "Test basic functionality" --approval-mode yolo 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Gemini CLI - MCP INTEGRATION WORKING" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Gemini CLI - MCP INTEGRATION FAILED" -ForegroundColor Red
    }
} catch {
    Write-Host "  ❌ Gemini CLI - MCP INTEGRATION ERROR" -ForegroundColor Red
}

Write-Host ""

# Summary Report
Write-Host "=== TEST SUMMARY ===" -ForegroundColor Green
$successfulTests = ($testResults | Where-Object { $_.Success -eq $true }).Count
$totalTests = $testResults.Count
$successRate = if ($totalTests -gt 0) { [math]::Round(($successfulTests / $totalTests) * 100, 2) } else { 0 }

Write-Host "Total Tests: $totalTests" -ForegroundColor White
Write-Host "Successful: $successfulTests" -ForegroundColor Green
Write-Host "Failed: $($totalTests - $successfulTests)" -ForegroundColor Red
Write-Host "Success Rate: $successRate%" -ForegroundColor $(if ($successRate -ge 80) { "Green" } elseif ($successRate -ge 60) { "Yellow" } else { "Red" })

Write-Host ""
Write-Host "=== DETAILED RESULTS ===" -ForegroundColor Cyan
$testResults | ForEach-Object {
    $status = if ($_.Success) { "✅ SUCCESS" } else { "❌ FAILED" }
    $color = if ($_.Success) { "Green" } else { "Red" }
    Write-Host "$($_.Server): $status" -ForegroundColor $color
    if (-not $_.Success) {
        Write-Host "  Command: $($_.Command) $($_.Args)" -ForegroundColor Gray
        Write-Host "  Error: $($_.Output)" -ForegroundColor Gray
    }
}

# Save results to file
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$resultsFile = "C:\Users\hyper\fwber\tools_config_files\MCP_TEST_RESULTS_$timestamp.json"
$testResults | ConvertTo-Json -Depth 3 | Out-File -FilePath $resultsFile -Encoding UTF8

Write-Host ""
Write-Host "Results saved to: $resultsFile" -ForegroundColor Gray
Write-Host "Test completed at: $(Get-Date)" -ForegroundColor Gray
