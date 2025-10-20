# Test MCP Server Startup Times

Write-Host "=== Testing MCP Server Startup Times ===" -ForegroundColor Cyan
Write-Host ""

function Test-MCPServerStartup {
    param(
        [string]$Name,
        [string]$Command,
        [string[]]$Args,
        [int]$MaxWaitSeconds = 30
    )
    
    Write-Host "Testing $Name startup time..." -ForegroundColor Yellow
    
    $startTime = Get-Date
    try {
        $process = Start-Process -FilePath $Command -ArgumentList $Args -PassThru -WindowStyle Hidden -RedirectStandardOutput "temp_output.txt" -RedirectStandardError "temp_error.txt"
        
        # Wait for the process to start or timeout
        $timeout = $MaxWaitSeconds * 1000
        $process.WaitForExit($timeout)
        
        $endTime = Get-Date
        $duration = ($endTime - $startTime).TotalSeconds
        
        if (!$process.HasExited) {
            Write-Host "   ✅ $Name started in $([math]::Round($duration, 2)) seconds (still running)" -ForegroundColor Green
            $process.Kill()
            return $true, $duration
        } else {
            $exitCode = $process.ExitCode
            $error = Get-Content "temp_error.txt" -Raw -ErrorAction SilentlyContinue
            $output = Get-Content "temp_output.txt" -Raw -ErrorAction SilentlyContinue
            
            if ($exitCode -eq 0 -or $output -match "Starting|Server ready|listening|running") {
                Write-Host "   ✅ $Name started in $([math]::Round($duration, 2)) seconds" -ForegroundColor Green
                return $true, $duration
            } else {
                Write-Host "   ❌ $Name failed in $([math]::Round($duration, 2)) seconds (exit code: $exitCode)" -ForegroundColor Red
                if ($error) {
                    Write-Host "   Error: $($error.Substring(0, [Math]::Min(100, $error.Length)))" -ForegroundColor Red
                }
                return $false, $duration
            }
        }
    } catch {
        $endTime = Get-Date
        $duration = ($endTime - $startTime).TotalSeconds
        Write-Host "   ❌ $Name failed in $([math]::Round($duration, 2)) seconds: $($_.Exception.Message)" -ForegroundColor Red
        return $false, $duration
    } finally {
        # Clean up temp files
        Remove-Item "temp_output.txt", "temp_error.txt" -ErrorAction SilentlyContinue
    }
}

Write-Host "1. Testing npx-based servers:" -ForegroundColor Yellow
$results = @{}

$results["memory"] = Test-MCPServerStartup "Memory" "cmd" @("/c", "npx.cmd", "@modelcontextprotocol/server-memory", "stdio") 15
$results["everything"] = Test-MCPServerStartup "Everything" "cmd" @("/c", "npx.cmd", "@modelcontextprotocol/server-everything", "stdio") 15
$results["filesystem"] = Test-MCPServerStartup "Filesystem" "cmd" @("/c", "npx.cmd", "@modelcontextprotocol/server-filesystem", "C:\Users\hyper\fwber\") 15

Write-Host ""
Write-Host "2. Testing UV-based servers:" -ForegroundColor Yellow
$results["serena"] = Test-MCPServerStartup "Serena" "C:\Users\hyper\.local\bin\uv.exe" @("run", "--directory", "C:\Users\hyper\serena\", "serena", "start-mcp-server", "--context", "codex", "--project", "C:\Users\hyper\fwber\") 30

Write-Host ""
Write-Host "3. Summary:" -ForegroundColor Yellow
$working = 0
$total = 0
$maxTime = 0

foreach ($server in $results.Keys) {
    $total++
    $success, $time = $results[$server]
    if ($success) {
        $working++
        if ($time -gt $maxTime) { $maxTime = $time }
    }
    $status = if ($success) { "✅ Working" } else { "❌ Failed" }
    Write-Host "   $server : $status ($([math]::Round($time, 2))s)" -ForegroundColor $(if ($success) { "Green" } else { "Red" })
}

Write-Host ""
Write-Host "Results:" -ForegroundColor Yellow
Write-Host "   Working servers: $working/$total" -ForegroundColor Green
Write-Host "   Maximum startup time: $([math]::Round($maxTime, 2)) seconds" -ForegroundColor White
Write-Host "   Current timeout: 120 seconds" -ForegroundColor White

if ($maxTime -gt 120) {
    Write-Host "   ⚠️  Some servers take longer than 120 seconds!" -ForegroundColor Red
    Write-Host "   Recommendation: Increase timeout to $([math]::Ceiling($maxTime + 30)) seconds" -ForegroundColor Yellow
} else {
    Write-Host "   ✅ All servers start within 120 seconds" -ForegroundColor Green
    Write-Host "   Issue might be with Codex MCP protocol or initialization" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Test complete!" -ForegroundColor Cyan
