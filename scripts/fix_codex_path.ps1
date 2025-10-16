# Fix Codex PATH Issues
# This script adds Node.js and Python to the current session PATH

Write-Host "=== Codex PATH Fix Script ===" -ForegroundColor Cyan
Write-Host ""

# Add Node.js and Python to current session PATH
$nodePath = "C:\Program Files\nodejs"
$npmPath = "$env:APPDATA\npm"
$pythonPath = "$env:USERPROFILE\.local\bin"

Write-Host "Adding to PATH for this session:" -ForegroundColor Yellow
Write-Host "  - $nodePath" -ForegroundColor Gray
Write-Host "  - $npmPath" -ForegroundColor Gray
Write-Host "  - $pythonPath" -ForegroundColor Gray
Write-Host ""

$env:PATH = "$nodePath;$npmPath;$pythonPath;$env:PATH"

Write-Host "✓ Added Node.js and Python to PATH" -ForegroundColor Green
Write-Host ""
Write-Host "Testing tools:" -ForegroundColor Cyan

# Test commands
try {
    $nodeVersion = node --version 2>&1
    Write-Host "  ✓ node: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "  ✗ node: not found" -ForegroundColor Red
}

try {
    $npxVersion = npx --version 2>&1
    Write-Host "  ✓ npx: $npxVersion" -ForegroundColor Green
} catch {
    Write-Host "  ✗ npx: not found" -ForegroundColor Red
}

try {
    $uvVersion = uv --version 2>&1
    Write-Host "  ✓ uv: $uvVersion" -ForegroundColor Green
} catch {
    Write-Host "  ✗ uv: not found (may need to install)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Test Codex with Anthropic model (to avoid OpenAI quota):" -ForegroundColor Yellow
Write-Host "   codex -c model_provider=anthropic exec 'Hello'" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Check MCP servers:" -ForegroundColor Yellow
Write-Host "   codex mcp list" -ForegroundColor Gray
Write-Host ""
Write-Host "3. For permanent fix, add these paths to System Environment Variables" -ForegroundColor Yellow
Write-Host ""
Write-Host "Note: This PATH fix only lasts for this PowerShell session." -ForegroundColor Cyan
Write-Host "      Run this script in each new terminal, or add to System PATH permanently." -ForegroundColor Cyan
