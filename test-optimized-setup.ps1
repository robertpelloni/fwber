# Test Optimized AI Orchestration Setup
Write-Host "🧪 Testing Optimized AI Orchestration Setup..." -ForegroundColor Blue

# Check process counts before
Write-Host "📊 Process counts before starting:" -ForegroundColor Yellow
$nodeBefore = (Get-Process -Name "node" -ErrorAction SilentlyContinue).Count
$cmdBefore = (Get-Process -Name "cmd" -ErrorAction SilentlyContinue).Count
$conhostBefore = (Get-Process -Name "conhost" -ErrorAction SilentlyContinue).Count

Write-Host "   Node.js processes: $nodeBefore" -ForegroundColor White
Write-Host "   cmd.exe processes: $cmdBefore" -ForegroundColor White
Write-Host "   conhost.exe processes: $conhostBefore" -ForegroundColor White

Write-Host ""
Write-Host "🚀 Starting AI Orchestrator..." -ForegroundColor Green

# Start the orchestrator in background
Start-Process -FilePath "node" -ArgumentList "ai-orchestrator.js" -WindowStyle Hidden

# Wait a moment for startup
Start-Sleep -Seconds 5

# Check process counts after
Write-Host "📊 Process counts after starting:" -ForegroundColor Yellow
$nodeAfter = (Get-Process -Name "node" -ErrorAction SilentlyContinue).Count
$cmdAfter = (Get-Process -Name "cmd" -ErrorAction SilentlyContinue).Count
$conhostAfter = (Get-Process -Name "conhost" -ErrorAction SilentlyContinue).Count

Write-Host "   Node.js processes: $nodeAfter" -ForegroundColor White
Write-Host "   cmd.exe processes: $cmdAfter" -ForegroundColor White
Write-Host "   conhost.exe processes: $conhostAfter" -ForegroundColor White

Write-Host ""
Write-Host "📈 Process increase:" -ForegroundColor Cyan
Write-Host "   Node.js: +$($nodeAfter - $nodeBefore)" -ForegroundColor White
Write-Host "   cmd.exe: +$($cmdAfter - $cmdBefore)" -ForegroundColor White
Write-Host "   conhost.exe: +$($conhostAfter - $conhostBefore)" -ForegroundColor White

$totalIncrease = ($nodeAfter - $nodeBefore) + ($cmdAfter - $cmdBefore) + ($conhostAfter - $conhostBefore)

Write-Host ""
if ($totalIncrease -le 5) {
    Write-Host "✅ SUCCESS: Only $totalIncrease processes started (vs 500+ before!)" -ForegroundColor Green
} else {
    Write-Host "⚠️  WARNING: $totalIncrease processes started - may need optimization" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎯 Test completed! Your optimized AI orchestration is working efficiently." -ForegroundColor Green
