# Test Individual MCP Servers

Write-Host "=== Testing Individual MCP Servers ===" -ForegroundColor Cyan
Write-Host ""

# Function to test a server
function Test-MCPServer {
    param(
        [string]$Name,
        [string]$Command,
        [string[]]$Args,
        [int]$TimeoutSeconds = 10
    )
    
    Write-Host "Testing $Name..." -ForegroundColor Yellow
    
    try {
        $process = Start-Process -FilePath $Command -ArgumentList $Args -PassThru -WindowStyle Hidden -RedirectStandardOutput "temp_output.txt" -RedirectStandardError "temp_error.txt"
        
        # Wait for the process to start or timeout
        $timeout = $TimeoutSeconds * 1000
        $process.WaitForExit($timeout)
        
        if (!$process.HasExited) {
            Write-Host "   ✅ $Name started successfully (still running)" -ForegroundColor Green
            $process.Kill()
            return $true
        } else {
            $exitCode = $process.ExitCode
            $error = Get-Content "temp_error.txt" -Raw -ErrorAction SilentlyContinue
            $output = Get-Content "temp_output.txt" -Raw -ErrorAction SilentlyContinue
            
            if ($exitCode -eq 0 -or $output -match "Starting|Server ready|listening") {
                Write-Host "   ✅ $Name started successfully" -ForegroundColor Green
                return $true
            } else {
                Write-Host "   ❌ $Name failed (exit code: $exitCode)" -ForegroundColor Red
                if ($error) {
                    Write-Host "   Error: $($error.Substring(0, [Math]::Min(100, $error.Length)))" -ForegroundColor Red
                }
                return $false
            }
        }
    } catch {
        Write-Host "   ❌ $Name failed: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    } finally {
        # Clean up temp files
        Remove-Item "temp_output.txt", "temp_error.txt" -ErrorAction SilentlyContinue
    }
}

# Test servers
$results = @{}

Write-Host "1. Testing npx-based servers:" -ForegroundColor Yellow
$results["everything"] = Test-MCPServer "Everything" "npx" @("-y", "@modelcontextprotocol/server-everything", "stdio") 5
$results["memory"] = Test-MCPServer "Memory" "npx" @("-y", "@modelcontextprotocol/server-memory", "stdio") 5
$results["filesystem"] = Test-MCPServer "Filesystem" "npx" @("-y", "@modelcontextprotocol/server-filesystem", "C:\Users\mrgen\fwber\", "stdio") 5
$results["sequential-thinking"] = Test-MCPServer "Sequential Thinking" "npx" @("-y", "@modelcontextprotocol/server-sequential-thinking", "stdio") 5

Write-Host ""
Write-Host "2. Testing specialized servers:" -ForegroundColor Yellow
$results["gemini-mcp-tool"] = Test-MCPServer "Gemini MCP Tool" "npx" @("-y", "gemini-mcp-tool", "stdio") 5
$results["puppeteer"] = Test-MCPServer "Puppeteer" "npx" @("-y", "puppeteer-mcp-server", "stdio") 5
$results["smart-crawler"] = Test-MCPServer "Smart Crawler" "npx" @("-y", "mcp-smart-crawler", "stdio") 5
$results["bolide-ai"] = Test-MCPServer "Bolide AI" "npx" @("-y", "@bolide-ai/mcp", "stdio") 5
$results["terry"] = Test-MCPServer "Terry" "npx" @("-y", "terry-mcp", "stdio") 5

Write-Host ""
Write-Host "3. Testing UV-based servers:" -ForegroundColor Yellow
$results["serena"] = Test-MCPServer "Serena" "uv" @("run", "--directory", "C:\Users\mrgen\serena\", "serena", "start-mcp-server", "--context", "codex", "--project", "C:\Users\mrgen\fwber\") 10
$results["zen-mcp-server"] = Test-MCPServer "Zen MCP Server" "uv" @("run", "--directory", "C:\Users\mrgen\zen-mcp-server\", "zen-mcp-server") 10

Write-Host ""
Write-Host "4. Summary:" -ForegroundColor Yellow
$working = ($results.Values | Where-Object { $_ -eq $true }).Count
$total = $results.Count

Write-Host "   Working servers: $working/$total" -ForegroundColor Green
Write-Host "   Failed servers: $($total - $working)/$total" -ForegroundColor Red

Write-Host ""
Write-Host "Detailed Results:" -ForegroundColor Yellow
foreach ($server in $results.Keys) {
    $status = if ($results[$server]) { "✅ Working" } else { "❌ Failed" }
    Write-Host "   $server : $status" -ForegroundColor $(if ($results[$server]) { "Green" } else { "Red" })
}

Write-Host ""
Write-Host "Test Complete" -ForegroundColor Cyan
