# AI Development Environment Process Manager
# This script helps manage and clean up processes related to AI CLI tools and MCP servers

param(
    [switch]$KillAll,
    [switch]$List,
    [switch]$Cleanup,
    [switch]$Monitor
)

function Get-AIProcesses {
    $processes = @()
    
    # Node.js processes (MCP servers, CLI tools)
    $processes += Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -match "(mcp|claude|codex|gemini|grok|serena|zen)" -or
        $_.ProcessName -eq "node"
    }
    
    # UV processes (Python package manager)
    $processes += Get-Process -Name "uv" -ErrorAction SilentlyContinue
    
    # Conhost processes (Windows console hosts)
    $processes += Get-Process -Name "conhost" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -match "(mcp|claude|codex|gemini|grok|serena|zen)"
    }
    
    # Python processes (MCP servers)
    $processes += Get-Process -Name "python" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -match "(mcp|serena|zen)"
    }
    
    # Java processes (JetBrains MCP server)
    $processes += Get-Process -Name "java" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -match "mcpserver"
    }
    
    return $processes | Sort-Object ProcessName, Id
}

function Show-ProcessList {
    Write-Host "=== AI Development Environment Processes ===" -ForegroundColor Cyan
    $processes = Get-AIProcesses
    
    if ($processes.Count -eq 0) {
        Write-Host "No AI-related processes found." -ForegroundColor Green
        return
    }
    
    $processes | ForEach-Object {
        $age = (Get-Date) - $_.StartTime
        $ageStr = if ($age.TotalHours -ge 1) {
            "{0:F1}h" -f $age.TotalHours
        } elseif ($age.TotalMinutes -ge 1) {
            "{0:F1}m" -f $age.TotalMinutes
        } else {
            "{0:F1}s" -f $age.TotalSeconds
        }
        
        Write-Host ("{0,-8} {1,-6} {2,-8} {3}" -f $_.ProcessName, $_.Id, $ageStr, $_.CommandLine) -ForegroundColor White
    }
    
    Write-Host "`nTotal processes: $($processes.Count)" -ForegroundColor Yellow
}

function Stop-AIProcesses {
    Write-Host "Stopping AI-related processes..." -ForegroundColor Yellow
    
    $processes = Get-AIProcesses
    $killed = 0
    
    foreach ($process in $processes) {
        try {
            Write-Host "Stopping $($process.ProcessName) (PID: $($process.Id))..." -ForegroundColor Red
            Stop-Process -Id $process.Id -Force -ErrorAction Stop
            $killed++
        }
        catch {
            Write-Host "Failed to stop $($process.ProcessName) (PID: $($process.Id)): $($_.Exception.Message)" -ForegroundColor Red
        }
    }
    
    Write-Host "Stopped $killed processes." -ForegroundColor Green
}

function Start-ProcessMonitor {
    Write-Host "Starting process monitor (Press Ctrl+C to stop)..." -ForegroundColor Cyan
    
    while ($true) {
        Clear-Host
        Show-ProcessList
        
        $processes = Get-AIProcesses
        $oldProcesses = $processes | Where-Object { (Get-Date) - $_.StartTime -gt [TimeSpan]::FromHours(1) }
        
        if ($oldProcesses.Count -gt 0) {
            Write-Host "`nWARNING: $($oldProcesses.Count) processes running for over 1 hour!" -ForegroundColor Red
            Write-Host "Consider running: .\process-manager.ps1 -Cleanup" -ForegroundColor Yellow
        }
        
        Start-Sleep -Seconds 10
    }
}

function Invoke-Cleanup {
    Write-Host "Cleaning up old processes..." -ForegroundColor Yellow
    
    $processes = Get-AIProcesses
    $oldProcesses = $processes | Where-Object { (Get-Date) - $_.StartTime -gt [TimeSpan]::FromHours(2) }
    
    if ($oldProcesses.Count -eq 0) {
        Write-Host "No old processes found." -ForegroundColor Green
        return
    }
    
    Write-Host "Found $($oldProcesses.Count) processes running for over 2 hours:" -ForegroundColor Yellow
    $oldProcesses | ForEach-Object {
        $age = (Get-Date) - $_.StartTime
        Write-Host "  $($_.ProcessName) (PID: $($_.Id)) - Running for {0:F1} hours" -f $age.TotalHours
    }
    
    $response = Read-Host "Kill these processes? (y/N)"
    if ($response -eq 'y' -or $response -eq 'Y') {
        foreach ($process in $oldProcesses) {
            try {
                Stop-Process -Id $process.Id -Force -ErrorAction Stop
                Write-Host "Killed $($process.ProcessName) (PID: $($process.Id))" -ForegroundColor Green
            }
            catch {
                Write-Host "Failed to kill $($process.ProcessName) (PID: $($process.Id)): $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
}

# Main execution
if ($List) {
    Show-ProcessList
}
elseif ($KillAll) {
    Stop-AIProcesses
}
elseif ($Cleanup) {
    Invoke-Cleanup
}
elseif ($Monitor) {
    Start-ProcessMonitor
}
else {
    Write-Host "AI Development Environment Process Manager" -ForegroundColor Cyan
    Write-Host "Usage:" -ForegroundColor White
    Write-Host "  .\process-manager.ps1 -List      # List all AI-related processes" -ForegroundColor Gray
    Write-Host "  .\process-manager.ps1 -KillAll   # Kill all AI-related processes" -ForegroundColor Gray
    Write-Host "  .\process-manager.ps1 -Cleanup   # Clean up old processes (>2 hours)" -ForegroundColor Gray
    Write-Host "  .\process-manager.ps1 -Monitor   # Monitor processes in real-time" -ForegroundColor Gray
    Write-Host ""
    Show-ProcessList
}
