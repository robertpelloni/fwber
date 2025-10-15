# Test MCP Handshake

Write-Host "=== Testing MCP Handshake ===" -ForegroundColor Cyan
Write-Host ""

# Test Memory MCP Server
Write-Host "Testing Memory MCP Server handshake..." -ForegroundColor Yellow

try {
    # Start the server
    $process = Start-Process -FilePath "cmd" -ArgumentList @("/c", "npx.cmd", "@modelcontextprotocol/server-memory", "stdio") -PassThru -WindowStyle Hidden -RedirectStandardInput -RedirectStandardOutput -RedirectStandardError -NoNewWindow
    
    Start-Sleep -Seconds 3
    
    if ($process.HasExited) {
        Write-Host "   ❌ Process exited immediately" -ForegroundColor Red
        $error = $process.StandardError.ReadToEnd()
        if ($error) {
            Write-Host "   Error: $error" -ForegroundColor Red
        }
    } else {
        Write-Host "   ✅ Process is running" -ForegroundColor Green
        
        # Send MCP initialize request
        $initRequest = '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{"roots":{"listChanged":true},"sampling":{}},"clientInfo":{"name":"test-client","version":"1.0.0"}}}'
        
        Write-Host "   Sending initialize request..." -ForegroundColor Yellow
        $process.StandardInput.WriteLine($initRequest)
        $process.StandardInput.Flush()
        
        # Wait for response
        Start-Sleep -Seconds 5
        
        # Read response
        $output = ""
        $error = ""
        
        if (!$process.StandardOutput.EndOfStream) {
            $output = $process.StandardOutput.ReadToEnd()
        }
        
        if (!$process.StandardError.EndOfStream) {
            $error = $process.StandardError.ReadToEnd()
        }
        
        Write-Host "   Output: $($output.Substring(0, [Math]::Min(200, $output.Length)))" -ForegroundColor Gray
        if ($error) {
            Write-Host "   Error: $($error.Substring(0, [Math]::Min(200, $error.Length)))" -ForegroundColor Red
        }
        
        if ($output -match "jsonrpc" -and $output -match "result") {
            Write-Host "   ✅ MCP handshake successful!" -ForegroundColor Green
        } else {
            Write-Host "   ❌ MCP handshake failed" -ForegroundColor Red
        }
        
        $process.Kill()
    }
} catch {
    Write-Host "   ❌ Test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Test complete!" -ForegroundColor Cyan
