# Test MCP Protocol Communication

Write-Host "=== Testing MCP Protocol Communication ===" -ForegroundColor Cyan
Write-Host ""

function Test-MCPProtocol {
    param(
        [string]$ServerName,
        [string]$Command,
        [string[]]$Args
    )
    
    Write-Host "Testing $ServerName MCP protocol..." -ForegroundColor Yellow
    
    try {
        # Start the MCP server
        $process = Start-Process -FilePath $Command -ArgumentList $Args -PassThru -WindowStyle Hidden -RedirectStandardInput -RedirectStandardOutput -RedirectStandardError
        
        Start-Sleep -Seconds 2
        
        if ($process.HasExited) {
            Write-Host "   ❌ $ServerName process exited immediately" -ForegroundColor Red
            return $false
        }
        
        # Send MCP initialize request
        $initRequest = @{
            jsonrpc = "2.0"
            id = 1
            method = "initialize"
            params = @{
                protocolVersion = "2024-11-05"
                capabilities = @{
                    roots = @{
                        listChanged = $true
                    }
                    sampling = @{}
                }
                clientInfo = @{
                    name = "test-client"
                    version = "1.0.0"
                }
            }
        } | ConvertTo-Json -Depth 10
        
        # Send the request
        $process.StandardInput.WriteLine($initRequest)
        $process.StandardInput.Flush()
        
        # Wait for response
        Start-Sleep -Seconds 3
        
        # Check if we got a response
        $output = ""
        $error = ""
        
        if (!$process.StandardOutput.EndOfStream) {
            $output = $process.StandardOutput.ReadToEnd()
        }
        
        if (!$process.StandardError.EndOfStream) {
            $error = $process.StandardError.ReadToEnd()
        }
        
        $process.Kill()
        
        if ($output -match "jsonrpc" -and $output -match "result") {
            Write-Host "   ✅ $ServerName responded to MCP initialize" -ForegroundColor Green
            Write-Host "   Response: $($output.Substring(0, [Math]::Min(100, $output.Length)))..." -ForegroundColor Gray
            return $true
        } else {
            Write-Host "   ❌ $ServerName did not respond to MCP initialize" -ForegroundColor Red
            if ($output) {
                Write-Host "   Output: $($output.Substring(0, [Math]::Min(100, $output.Length)))..." -ForegroundColor Gray
            }
            if ($error) {
                Write-Host "   Error: $($error.Substring(0, [Math]::Min(100, $error.Length)))..." -ForegroundColor Gray
            }
            return $false
        }
        
    } catch {
        Write-Host "   ❌ $ServerName test failed: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

Write-Host "1. Testing Memory MCP Server:" -ForegroundColor Yellow
$memoryResult = Test-MCPProtocol "Memory" "cmd" @("/c", "npx.cmd", "@modelcontextprotocol/server-memory", "stdio")

Write-Host ""
Write-Host "2. Testing Everything MCP Server:" -ForegroundColor Yellow
$everythingResult = Test-MCPProtocol "Everything" "cmd" @("/c", "npx.cmd", "@modelcontextprotocol/server-everything", "stdio")

Write-Host ""
Write-Host "3. Testing Serena MCP Server:" -ForegroundColor Yellow
$serenaResult = Test-MCPProtocol "Serena" "C:\Users\mrgen\.local\bin\uv.exe" @("run", "--directory", "C:\Users\mrgen\serena\", "serena", "start-mcp-server", "--context", "codex", "--project", "C:\Users\mrgen\fwber\")

Write-Host ""
Write-Host "4. Summary:" -ForegroundColor Yellow
$working = 0
$total = 0

if ($memoryResult) { $working++ }
$total++

if ($everythingResult) { $working++ }
$total++

if ($serenaResult) { $working++ }
$total++

Write-Host "   MCP Protocol Working: $working/$total" -ForegroundColor $(if ($working -eq $total) { "Green" } else { "Yellow" })

if ($working -eq $total) {
    Write-Host "   ✅ All servers respond to MCP protocol" -ForegroundColor Green
    Write-Host "   Issue might be with Codex's MCP client implementation" -ForegroundColor Yellow
} else {
    Write-Host "   ⚠️  Some servers don't respond to MCP protocol" -ForegroundColor Red
    Write-Host "   This could explain the timeout issues" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Test complete!" -ForegroundColor Cyan
