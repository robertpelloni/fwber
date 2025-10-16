# Kill AI Processes Script
Write-Host "Killing unnecessary AI processes..." -ForegroundColor Red

# Kill Node.js processes
$nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
if ($nodeProcesses) {
    Write-Host "Killing $($nodeProcesses.Count) Node.js processes..." -ForegroundColor Yellow
    $nodeProcesses | ForEach-Object { 
        try { $_.Kill(); Write-Host "Killed Node PID $($_.Id)" -ForegroundColor Green }
        catch { Write-Host "Failed to kill Node PID $($_.Id)" -ForegroundColor Red }
    }
}

# Kill cmd.exe processes
$cmdProcesses = Get-Process -Name "cmd" -ErrorAction SilentlyContinue
if ($cmdProcesses) {
    Write-Host "Killing $($cmdProcesses.Count) cmd.exe processes..." -ForegroundColor Yellow
    $cmdProcesses | ForEach-Object { 
        try { $_.Kill(); Write-Host "Killed cmd PID $($_.Id)" -ForegroundColor Green }
        catch { Write-Host "Failed to kill cmd PID $($_.Id)" -ForegroundColor Red }
    }
}

# Kill conhost.exe processes
$conhostProcesses = Get-Process -Name "conhost" -ErrorAction SilentlyContinue
if ($conhostProcesses) {
    Write-Host "Killing $($conhostProcesses.Count) conhost.exe processes..." -ForegroundColor Yellow
    $conhostProcesses | ForEach-Object { 
        try { $_.Kill(); Write-Host "Killed conhost PID $($_.Id)" -ForegroundColor Green }
        catch { Write-Host "Failed to kill conhost PID $($_.Id)" -ForegroundColor Red }
    }
}

# Kill Python processes
$pythonProcesses = Get-Process -Name "python*" -ErrorAction SilentlyContinue
if ($pythonProcesses) {
    Write-Host "Killing $($pythonProcesses.Count) Python processes..." -ForegroundColor Yellow
    $pythonProcesses | ForEach-Object { 
        try { $_.Kill(); Write-Host "Killed Python PID $($_.Id)" -ForegroundColor Green }
        catch { Write-Host "Failed to kill Python PID $($_.Id)" -ForegroundColor Red }
    }
}

Write-Host "Process cleanup completed!" -ForegroundColor Green
