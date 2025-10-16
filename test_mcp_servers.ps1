# Test MCP Server Connectivity

Write-Host "=== Testing MCP Server Connectivity ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Zen MCP Server
Write-Host "1. Testing Zen MCP Server..." -ForegroundColor Yellow
try {
    $zenProcess = Start-Process -FilePath "uv" -ArgumentList "run", "--directory", "C:\Users\mrgen\zen-mcp-server\", "zen-mcp-server" -PassThru -WindowStyle Hidden -RedirectStandardOutput "zen_output.txt" -RedirectStandardError "zen_error.txt"
    Start-Sleep -Seconds 5
    
    if (!$zenProcess.HasExited) {
        Write-Host "   ✅ Zen MCP Server started successfully" -ForegroundColor Green
        $zenProcess.Kill()
        
        # Check if it's responding to MCP protocol
        $output = Get-Content "zen_output.txt" -Raw
        if ($output -match "Server ready") {
            Write-Host "   ✅ Zen MCP Server is ready for requests" -ForegroundColor Green
        } else {
            Write-Host "   ⚠️  Zen MCP Server started but may not be ready" -ForegroundColor Yellow
        }
    } else {
        Write-Host "   ❌ Zen MCP Server exited immediately" -ForegroundColor Red
        $error = Get-Content "zen_error.txt" -Raw
        Write-Host "   Error: $error" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Zen MCP Server test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Everything MCP Server
Write-Host "2. Testing Everything MCP Server..." -ForegroundColor Yellow
try {
    $everythingProcess = Start-Process -FilePath "npx" -ArgumentList "-y", "@modelcontextprotocol/server-everything", "stdio" -PassThru -WindowStyle Hidden -RedirectStandardOutput "everything_output.txt" -RedirectStandardError "everything_error.txt"
    Start-Sleep -Seconds 3
    
    if (!$everythingProcess.HasExited) {
        Write-Host "   ✅ Everything MCP Server started successfully" -ForegroundColor Green
        $everythingProcess.Kill()
    } else {
        Write-Host "   ❌ Everything MCP Server exited immediately" -ForegroundColor Red
        $error = Get-Content "everything_error.txt" -Raw
        Write-Host "   Error: $error" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Everything MCP Server test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Serena MCP Server
Write-Host "3. Testing Serena MCP Server..." -ForegroundColor Yellow
try {
    $serenaProcess = Start-Process -FilePath "uv" -ArgumentList "run", "--directory", "C:\Users\mrgen\serena\", "serena", "start-mcp-server", "--context", "codex", "--project", "C:\Users\mrgen\fwber\" -PassThru -WindowStyle Hidden -RedirectStandardOutput "serena_output.txt" -RedirectStandardError "serena_error.txt"
    Start-Sleep -Seconds 5
    
    if (!$serenaProcess.HasExited) {
        Write-Host "   ✅ Serena MCP Server started successfully" -ForegroundColor Green
        $serenaProcess.Kill()
    } else {
        Write-Host "   ❌ Serena MCP Server exited immediately" -ForegroundColor Red
        $error = Get-Content "serena_error.txt" -Raw
        Write-Host "   Error: $error" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Serena MCP Server test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Gemini MCP Tool
Write-Host "4. Testing Gemini MCP Tool..." -ForegroundColor Yellow
try {
    $geminiProcess = Start-Process -FilePath "npx" -ArgumentList "-y", "gemini-mcp-tool" -PassThru -WindowStyle Hidden -RedirectStandardOutput "gemini_output.txt" -RedirectStandardError "gemini_error.txt"
    Start-Sleep -Seconds 3
    
    if (!$geminiProcess.HasExited) {
        Write-Host "   ✅ Gemini MCP Tool started successfully" -ForegroundColor Green
        $geminiProcess.Kill()
    } else {
        Write-Host "   ❌ Gemini MCP Tool exited immediately" -ForegroundColor Red
        $error = Get-Content "gemini_error.txt" -Raw
        Write-Host "   Error: $error" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Gemini MCP Tool test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Summary:" -ForegroundColor Yellow
Write-Host "- If servers started successfully, the timeout issues may be resolved" -ForegroundColor White
Write-Host "- If servers exited immediately, there may be dependency issues" -ForegroundColor White
Write-Host "- Check the output files for detailed error messages" -ForegroundColor White

# Clean up test files
Remove-Item -Path "zen_output.txt", "zen_error.txt", "everything_output.txt", "everything_error.txt", "serena_output.txt", "serena_error.txt", "gemini_output.txt", "gemini_error.txt" -ErrorAction SilentlyContinue
