# Debug Codex MCP Server Issues

Write-Host "=== Codex MCP Server Debug ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Testing npx.cmd directly:" -ForegroundColor Yellow
try {
    $result = & "C:\Program Files\nodejs\npx.cmd" --version 2>&1
    Write-Host "   ✅ npx.cmd works: $result" -ForegroundColor Green
} catch {
    Write-Host "   ❌ npx.cmd failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "2. Testing npx.cmd without full path:" -ForegroundColor Yellow
try {
    $result = & "npx.cmd" --version 2>&1
    Write-Host "   ✅ npx.cmd (short) works: $result" -ForegroundColor Green
} catch {
    Write-Host "   ❌ npx.cmd (short) failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "3. Testing MCP server directly:" -ForegroundColor Yellow
try {
    $process = Start-Process -FilePath "C:\Program Files\nodejs\npx.cmd" -ArgumentList "@modelcontextprotocol/server-memory", "stdio" -PassThru -WindowStyle Hidden -RedirectStandardOutput "test_output.txt" -RedirectStandardError "test_error.txt"
    Start-Sleep -Seconds 3
    
    if (!$process.HasExited) {
        Write-Host "   ✅ MCP server started successfully" -ForegroundColor Green
        $process.Kill()
        
        $output = Get-Content "test_output.txt" -Raw -ErrorAction SilentlyContinue
        if ($output -match "Knowledge Graph|Server running") {
            Write-Host "   ✅ MCP server is responding correctly" -ForegroundColor Green
        }
    } else {
        Write-Host "   ❌ MCP server exited immediately" -ForegroundColor Red
        $error = Get-Content "test_error.txt" -Raw -ErrorAction SilentlyContinue
        if ($error) {
            Write-Host "   Error: $error" -ForegroundColor Red
        }
    }
} catch {
    Write-Host "   ❌ MCP server test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "4. Environment Information:" -ForegroundColor Yellow
Write-Host "   PATH contains nodejs: $($env:PATH -like '*nodejs*')" -ForegroundColor White
Write-Host "   Current shell: PowerShell" -ForegroundColor White
Write-Host "   Codex likely uses: cmd.exe or direct process execution" -ForegroundColor White

Write-Host ""
Write-Host "5. Recommendations:" -ForegroundColor Yellow
Write-Host "   - Try using npx.cmd instead of full path" -ForegroundColor White
Write-Host "   - Check if Codex has different PATH environment" -ForegroundColor White
Write-Host "   - Consider using cmd /c wrapper for all commands" -ForegroundColor White

# Clean up
Remove-Item "test_output.txt", "test_error.txt" -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "Debug complete!" -ForegroundColor Cyan
