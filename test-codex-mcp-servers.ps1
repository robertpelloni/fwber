# Test Codex MCP Servers
# Verify that all MCP servers can start properly

Write-Host "Testing Codex MCP Server Configurations..." -ForegroundColor Green
Write-Host ""

# Test 1: Filesystem MCP Server
Write-Host "1. Testing Filesystem MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "powershell.exe" -ArgumentList @("-ExecutionPolicy", "Bypass", "-File", "mcp-server-filesystem.ps1", "C:\Users\hyper\fwber\") -PassThru -NoNewWindow
    Start-Sleep -Seconds 2
    if (!$process.HasExited) {
        Write-Host "   ✅ Filesystem MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Filesystem MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Filesystem MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Sequential Thinking MCP Server
Write-Host "2. Testing Sequential Thinking MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "powershell.exe" -ArgumentList @("-ExecutionPolicy", "Bypass", "-File", "mcp-server-sequential-thinking.ps1", "stdio") -PassThru -NoNewWindow
    Start-Sleep -Seconds 2
    if (!$process.HasExited) {
        Write-Host "   ✅ Sequential Thinking MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Sequential Thinking MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Sequential Thinking MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Everything MCP Server
Write-Host "3. Testing Everything MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "powershell.exe" -ArgumentList @("-ExecutionPolicy", "Bypass", "-File", "mcp-server-everything.ps1", "stdio") -PassThru -NoNewWindow
    Start-Sleep -Seconds 2
    if (!$process.HasExited) {
        Write-Host "   ✅ Everything MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Everything MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Everything MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Gemini MCP Server
Write-Host "4. Testing Gemini MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "powershell.exe" -ArgumentList @("-ExecutionPolicy", "Bypass", "-File", "gemini-mcp.ps1", "--api-key", "test", "stdio") -PassThru -NoNewWindow
    Start-Sleep -Seconds 2
    if (!$process.HasExited) {
        Write-Host "   ✅ Gemini MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Gemini MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Gemini MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 5: Serena MCP Server
Write-Host "5. Testing Serena MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "C:\Users\hyper\.local\bin\uv.exe" -ArgumentList @("run", "--directory", "C:\Users\hyper\serena\", "serena", "start-mcp-server", "--context", "codex", "--project", "C:\Users\hyper\fwber\") -PassThru -NoNewWindow
    Start-Sleep -Seconds 3
    if (!$process.HasExited) {
        Write-Host "   ✅ Serena MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Serena MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Serena MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 6: Zen MCP Server
Write-Host "6. Testing Zen MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "C:\Users\hyper\zen-mcp-server\.venv\Scripts\zen-mcp-server.exe" -PassThru -NoNewWindow
    Start-Sleep -Seconds 3
    if (!$process.HasExited) {
        Write-Host "   ✅ Zen MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Zen MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Zen MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 7: Chroma MCP Server
Write-Host "7. Testing Chroma MCP Server..." -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "C:\Users\hyper\chroma-env-312\Scripts\chroma-mcp-server.exe" -ArgumentList @("--mode", "stdio", "--client-type", "http", "--host", "localhost", "--port", "8000", "--ssl", "false") -PassThru -NoNewWindow
    Start-Sleep -Seconds 3
    if (!$process.HasExited) {
        Write-Host "   ✅ Chroma MCP Server started successfully" -ForegroundColor Green
        $process.Kill()
    } else {
        Write-Host "   ❌ Chroma MCP Server failed to start" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Chroma MCP Server error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "MCP Server testing complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Restart Codex CLI to load the updated configuration" -ForegroundColor White
Write-Host "2. Test Codex with: codex --help" -ForegroundColor White
Write-Host "3. Check MCP server status in Codex" -ForegroundColor White
