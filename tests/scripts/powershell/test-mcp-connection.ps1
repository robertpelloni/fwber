# Test MCP Server Connections
# This script verifies all MCP servers can start

Write-Host "=== Testing MCP Server Connections ===" -ForegroundColor Cyan
Write-Host ""

$results = @()

# Test 1: Check if uv.exe exists (for Serena and Zen)
Write-Host "1. Checking uv.exe..." -ForegroundColor Yellow
if (Test-Path "C:\Users\hyper\.local\bin\uv.exe") {
    Write-Host "   ✅ uv.exe found" -ForegroundColor Green
    $uvVersion = & "C:\Users\hyper\.local\bin\uv.exe" --version 2>&1
    Write-Host "   Version: $uvVersion" -ForegroundColor Gray
    $results += @{Name="uv.exe"; Status="✅"}
} else {
    Write-Host "   ❌ uv.exe not found" -ForegroundColor Red
    $results += @{Name="uv.exe"; Status="❌"}
}

Write-Host ""

# Test 2: Check if Serena exists
Write-Host "2. Checking Serena MCP..." -ForegroundColor Yellow
if (Test-Path "C:\Users\hyper\serena") {
    Write-Host "   ✅ Serena directory found" -ForegroundColor Green
    $results += @{Name="Serena"; Status="✅"}
} else {
    Write-Host "   ❌ Serena directory not found" -ForegroundColor Red
    $results += @{Name="Serena"; Status="❌"}
}

Write-Host ""

# Test 3: Check if Zen MCP exists
Write-Host "3. Checking Zen MCP Server..." -ForegroundColor Yellow
if (Test-Path "C:\Users\hyper\zen-mcp-server") {
    Write-Host "   ✅ Zen MCP directory found" -ForegroundColor Green
    $results += @{Name="Zen MCP"; Status="✅"}
} else {
    Write-Host "   ❌ Zen MCP directory not found" -ForegroundColor Red
    $results += @{Name="Zen MCP"; Status="❌"}
}

Write-Host ""

# Test 4: Check npm global packages
Write-Host "4. Checking npm global packages..." -ForegroundColor Yellow
$npmPackages = @("mcp-server-memory", "mcp-server-filesystem", "mcp-server-sequential-thinking")

foreach ($package in $npmPackages) {
    $cmd = "C:\Users\hyper\AppData\Roaming\npm\$package.cmd"
    if (Test-Path $cmd) {
        Write-Host "   ✅ $package installed" -ForegroundColor Green
        $results += @{Name=$package; Status="✅"}
    } else {
        Write-Host "   ❌ $package not found" -ForegroundColor Red
        $results += @{Name=$package; Status="❌"}
    }
}

Write-Host ""
Write-Host "=== Summary ===" -ForegroundColor Cyan
$successCount = ($results | Where-Object { $_.Status -eq "✅" }).Count
$totalCount = $results.Count
Write-Host "$successCount / $totalCount MCP servers ready" -ForegroundColor $(if ($successCount -eq $totalCount) { "Green" } else { "Yellow" })

Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Restart Cursor IDE to load updated MCP config" -ForegroundColor White
Write-Host "2. Check Cursor's MCP panel to see connected servers" -ForegroundColor White
Write-Host "3. Look for tools starting with 'mcp_zen-mcp-server_' and 'mcp_serena_'" -ForegroundColor White
Write-Host ""

