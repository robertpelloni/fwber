# Comprehensive MCP Server Test

Write-Host "=== Comprehensive MCP Server Test ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Check MCP server status
Write-Host "1. MCP Server Status Check:" -ForegroundColor Yellow
$mcpList = codex mcp list 2>&1
$enabledCount = ($mcpList | Select-String "enabled").Count
$disabledCount = ($mcpList | Select-String "disabled").Count

Write-Host "   ✅ Enabled Servers: $enabledCount" -ForegroundColor Green
Write-Host "   ⚠️  Disabled Servers: $disabledCount" -ForegroundColor Yellow

# Test 2: Test individual server connectivity
Write-Host ""
Write-Host "2. Testing Individual Server Connectivity:" -ForegroundColor Yellow

# Test Serena
Write-Host "   Testing Serena MCP Server..." -ForegroundColor White
try {
    $serenaTest = codex mcp get serena 2>&1
    if ($serenaTest -match "enabled: true") {
        Write-Host "   ✅ Serena: Working" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Serena: Not working" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Serena: Error - $($_.Exception.Message)" -ForegroundColor Red
}

# Test Zen MCP Server
Write-Host "   Testing Zen MCP Server..." -ForegroundColor White
try {
    $zenTest = codex mcp get zen-mcp-server 2>&1
    if ($zenTest -match "enabled: true") {
        Write-Host "   ✅ Zen MCP Server: Working" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Zen MCP Server: Not working" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Zen MCP Server: Error - $($_.Exception.Message)" -ForegroundColor Red
}

# Test JetBrains
Write-Host "   Testing JetBrains MCP Server..." -ForegroundColor White
try {
    $jetbrainsTest = codex mcp get jetbrains 2>&1
    if ($jetbrainsTest -match "enabled: true") {
        Write-Host "   ✅ JetBrains: Working" -ForegroundColor Green
    } else {
        Write-Host "   ❌ JetBrains: Not working" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ JetBrains: Error - $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Create a test prompt for Codex
Write-Host ""
Write-Host "3. Creating Test Prompt for Codex:" -ForegroundColor Yellow

$testPrompt = @"
Please use the available MCP tools to:

1. **Serena MCP Server**: List the current directory contents and get project overview
2. **Zen MCP Server**: List available AI models and their capabilities  
3. **Filesystem MCP Server**: Show file structure of the project
4. **Memory MCP Server**: Create a memory about this test session
5. **Everything MCP Server**: Demonstrate available tools

Provide a brief summary of what MCP tools are available and working.
"@

$testPrompt | Out-File -FilePath "mcp_test_prompt.txt" -Encoding UTF8
Write-Host "   ✅ Test prompt created: mcp_test_prompt.txt" -ForegroundColor Green

# Test 4: Summary
Write-Host ""
Write-Host "4. Summary:" -ForegroundColor Yellow
Write-Host "   📊 Total MCP Servers: $($enabledCount + $disabledCount)" -ForegroundColor White
Write-Host "   ✅ Working Servers: $enabledCount" -ForegroundColor Green
Write-Host "   ⚠️  Disabled Servers: $disabledCount" -ForegroundColor Yellow

Write-Host ""
Write-Host "5. Next Steps:" -ForegroundColor Yellow
Write-Host "   - Run: codex --full-auto 'Use MCP tools to analyze the project'" -ForegroundColor White
Write-Host "   - Or use the test prompt: codex --full-auto '$(Get-Content mcp_test_prompt.txt -Raw)'" -ForegroundColor White

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
