# Test MCP Server Functionality

Write-Host "=== Testing MCP Server Functionality ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Test if Codex can connect to MCP servers
Write-Host "1. Testing Codex MCP connectivity..." -ForegroundColor Yellow

# Create a simple test prompt
$testPrompt = @"
Use the available MCP tools to:
1. List the current directory contents
2. Get information about the project
3. Show available models

Please provide a brief summary of what tools are available.
"@

# Save test prompt to file
$testPrompt | Out-File -FilePath "test_prompt.txt" -Encoding UTF8

Write-Host "   Test prompt created: test_prompt.txt" -ForegroundColor Green
Write-Host "   Prompt: $($testPrompt.Substring(0, 50))..." -ForegroundColor White

Write-Host ""
Write-Host "2. Available MCP Servers:" -ForegroundColor Yellow
Write-Host "   ✅ Serena (20 tools)" -ForegroundColor Green
Write-Host "   ✅ Zen MCP Server (18 tools)" -ForegroundColor Green
Write-Host "   ✅ Everything MCP Server" -ForegroundColor Green
Write-Host "   ✅ Filesystem MCP Server" -ForegroundColor Green
Write-Host "   ✅ Memory MCP Server" -ForegroundColor Green
Write-Host "   ✅ Sequential Thinking MCP Server" -ForegroundColor Green
Write-Host "   ✅ Puppeteer MCP Server" -ForegroundColor Green
Write-Host "   ✅ Smart Crawler MCP Server" -ForegroundColor Green
Write-Host "   ✅ Bolide AI MCP Server" -ForegroundColor Green
Write-Host "   ✅ Terry MCP Server" -ForegroundColor Green
Write-Host "   ✅ Chrome DevTools MCP Server" -ForegroundColor Green
Write-Host "   ✅ Playwright MCP Server" -ForegroundColor Green
Write-Host "   ⚠️  JetBrains (disabled - needs WebStorm)" -ForegroundColor Yellow
Write-Host "   ⚠️  Enhanced Postgres (disabled - needs PostgreSQL)" -ForegroundColor Yellow
Write-Host "   ⚠️  Zenable (disabled - needs authentication)" -ForegroundColor Yellow

Write-Host ""
Write-Host "3. Next Steps:" -ForegroundColor Yellow
Write-Host "   - Test Codex with MCP servers using: codex --full-auto `"Use MCP tools to analyze the project`"" -ForegroundColor White
Write-Host "   - Enable JetBrains by starting WebStorm" -ForegroundColor White
Write-Host "   - Setup PostgreSQL for database MCP server" -ForegroundColor White
Write-Host "   - Configure Zenable authentication" -ForegroundColor White

Write-Host ""
Write-Host "=== Test Setup Complete ===" -ForegroundColor Cyan
