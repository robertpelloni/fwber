# Final MCP Test

Write-Host "=== Final MCP Server Test ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Testing Codex with MCP servers..." -ForegroundColor Yellow
Write-Host "This will test if Codex can actually connect to and use the MCP servers." -ForegroundColor White
Write-Host ""

# Create a simple test prompt
$testPrompt = "Use the available MCP tools to list the current directory contents and provide a brief project overview."

Write-Host "Test prompt: $testPrompt" -ForegroundColor White
Write-Host ""

Write-Host "Running Codex test..." -ForegroundColor Yellow
Write-Host "Note: This may take a while as Codex initializes MCP servers..." -ForegroundColor White
Write-Host ""

# Save the prompt to a file
$testPrompt | Out-File -FilePath "final_test_prompt.txt" -Encoding UTF8

Write-Host "Test prompt saved to: final_test_prompt.txt" -ForegroundColor Green
Write-Host ""

Write-Host "To run the test manually:" -ForegroundColor Yellow
Write-Host "codex --full-auto 'Use the available MCP tools to list the current directory contents and provide a brief project overview.'" -ForegroundColor White
Write-Host ""

Write-Host "Expected behavior:" -ForegroundColor Yellow
Write-Host "- Codex should start without timeout errors" -ForegroundColor White
Write-Host "- MCP servers should initialize successfully" -ForegroundColor White
Write-Host "- Codex should be able to use MCP tools" -ForegroundColor White
Write-Host ""

Write-Host "If you see timeout errors, the MCP servers are still not working properly." -ForegroundColor Red
Write-Host "If you see successful MCP tool usage, the configuration is working!" -ForegroundColor Green

Write-Host ""
Write-Host "Test setup complete!" -ForegroundColor Cyan
