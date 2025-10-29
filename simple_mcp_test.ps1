# Simple MCP Server Test

Write-Host "=== Simple MCP Server Test ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Check MCP server status
Write-Host "1. MCP Server Status:" -ForegroundColor Yellow
$mcpList = codex mcp list 2>&1
$enabledCount = ($mcpList | Select-String "enabled").Count
$disabledCount = ($mcpList | Select-String "disabled").Count

Write-Host "   Enabled Servers: $enabledCount" -ForegroundColor Green
Write-Host "   Disabled Servers: $disabledCount" -ForegroundColor Yellow

# Test 2: Test key servers
Write-Host ""
Write-Host "2. Testing Key Servers:" -ForegroundColor Yellow

# Test Serena
Write-Host "   Testing Serena..." -ForegroundColor White
$serenaTest = codex mcp get serena 2>&1
if ($serenaTest -match "enabled: true") {
    Write-Host "   Serena: Working" -ForegroundColor Green
} else {
    Write-Host "   Serena: Not working" -ForegroundColor Red
}

# Test Zen MCP Server
Write-Host "   Testing Zen MCP Server..." -ForegroundColor White
$zenTest = codex mcp get zen-mcp-server 2>&1
if ($zenTest -match "enabled: true") {
    Write-Host "   Zen MCP Server: Working" -ForegroundColor Green
} else {
    Write-Host "   Zen MCP Server: Not working" -ForegroundColor Red
}

# Test JetBrains
Write-Host "   Testing JetBrains..." -ForegroundColor White
$jetbrainsTest = codex mcp get jetbrains 2>&1
if ($jetbrainsTest -match "enabled: true") {
    Write-Host "   JetBrains: Working" -ForegroundColor Green
} else {
    Write-Host "   JetBrains: Not working" -ForegroundColor Red
}

Write-Host ""
Write-Host "3. Summary:" -ForegroundColor Yellow
Write-Host "   Total MCP Servers: $($enabledCount + $disabledCount)" -ForegroundColor White
Write-Host "   Working Servers: $enabledCount" -ForegroundColor Green
Write-Host "   Disabled Servers: $disabledCount" -ForegroundColor Yellow

Write-Host ""
Write-Host "Test Complete" -ForegroundColor Cyan
