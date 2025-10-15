# Codex MCP Server Diagnostic Script

Write-Host "=== Codex MCP Server Diagnostic ===" -ForegroundColor Cyan
Write-Host ""

# Check if Codex is installed
Write-Host "1. Checking Codex CLI installation..." -ForegroundColor Yellow
try {
    $codexVersion = codex --version
    Write-Host "   ✅ Codex CLI installed: $codexVersion" -ForegroundColor Green
} catch {
    Write-Host "   ❌ Codex CLI not found" -ForegroundColor Red
    exit 1
}

# Check configuration file
Write-Host "2. Checking Codex configuration..." -ForegroundColor Yellow
$configPath = "$env:USERPROFILE\.codex\config.toml"
if (Test-Path $configPath) {
    Write-Host "   ✅ Configuration file exists: $configPath" -ForegroundColor Green
    
    # Check for timeout configurations
    $configContent = Get-Content $configPath -Raw
    if ($configContent -match "timeout") {
        Write-Host "   ⚠️  Timeout configurations found in config" -ForegroundColor Yellow
    } else {
        Write-Host "   ℹ️  No timeout configurations found" -ForegroundColor Blue
    }
} else {
    Write-Host "   ❌ Configuration file not found: $configPath" -ForegroundColor Red
}

# List MCP servers
Write-Host "3. Listing configured MCP servers..." -ForegroundColor Yellow
try {
    $mcpList = codex mcp list
    Write-Host "   ✅ MCP servers configured:" -ForegroundColor Green
    $mcpList | ForEach-Object { Write-Host "      $_" -ForegroundColor White }
} catch {
    Write-Host "   ❌ Error listing MCP servers: $($_.Exception.Message)" -ForegroundColor Red
}

# Check specific MCP servers
Write-Host "4. Testing individual MCP servers..." -ForegroundColor Yellow

# Test Zen MCP Server
Write-Host "   Testing Zen MCP Server..." -ForegroundColor Blue
try {
    $zenStatus = codex mcp get zen-mcp-server
    Write-Host "      ✅ Zen MCP Server configured" -ForegroundColor Green
} catch {
    Write-Host "      ❌ Zen MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test Everything MCP Server
Write-Host "   Testing Everything MCP Server..." -ForegroundColor Blue
try {
    $everythingStatus = codex mcp get everything
    Write-Host "      ✅ Everything MCP Server configured" -ForegroundColor Green
} catch {
    Write-Host "      ❌ Everything MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test if MCP servers can start
Write-Host "5. Testing MCP server startup..." -ForegroundColor Yellow

# Test Zen MCP Server startup
Write-Host "   Testing Zen MCP Server startup..." -ForegroundColor Blue
try {
    $zenProcess = Start-Process -FilePath "uv" -ArgumentList "run", "--directory", "C:\Users\mrgen\zen-mcp-server\", "zen-mcp-server" -PassThru -WindowStyle Hidden
    Start-Sleep -Seconds 3
    if (!$zenProcess.HasExited) {
        Write-Host "      ✅ Zen MCP Server started successfully" -ForegroundColor Green
        $zenProcess.Kill()
    } else {
        Write-Host "      ❌ Zen MCP Server exited immediately" -ForegroundColor Red
    }
} catch {
    Write-Host "      ❌ Zen MCP Server startup error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test Everything MCP Server startup
Write-Host "   Testing Everything MCP Server startup..." -ForegroundColor Blue
try {
    $everythingProcess = Start-Process -FilePath "npx" -ArgumentList "-y", "@modelcontextprotocol/server-everything", "stdio" -PassThru -WindowStyle Hidden
    Start-Sleep -Seconds 3
    if (!$everythingProcess.HasExited) {
        Write-Host "      ✅ Everything MCP Server started successfully" -ForegroundColor Green
        $everythingProcess.Kill()
    } else {
        Write-Host "      ❌ Everything MCP Server exited immediately" -ForegroundColor Red
    }
} catch {
    Write-Host "      ❌ Everything MCP Server startup error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Diagnostic Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Recommendations:" -ForegroundColor Yellow
Write-Host "1. If MCP servers are timing out, try increasing timeout values in config.toml" -ForegroundColor White
Write-Host "2. Add these settings to your config.toml:" -ForegroundColor White
Write-Host "   [mcp_servers.serena]" -ForegroundColor Gray
Write-Host "   startup_timeout_ms = 60000" -ForegroundColor Gray
Write-Host "   [mcp_servers.zen-mcp-server]" -ForegroundColor Gray
Write-Host "   startup_timeout_ms = 60000" -ForegroundColor Gray
Write-Host "3. Check if WebStorm is running for JetBrains MCP server" -ForegroundColor White
Write-Host "4. Verify all required packages are installed (npx, uv, etc.)" -ForegroundColor White
