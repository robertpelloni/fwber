# Simple AI Orchestration Deployment
Write-Host "🚀 Deploying AI Orchestration System..." -ForegroundColor Green

# Kill existing processes
Write-Host "🛑 Stopping existing processes..." -ForegroundColor Red
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

# Deploy single-process system
Write-Host "📦 Deploying single-process configuration..." -ForegroundColor Blue

# Codex config
$codexDir = "C:\Users\hyper\.codex"
if (!(Test-Path $codexDir)) {
    New-Item -ItemType Directory -Path $codexDir -Force | Out-Null
}
Copy-Item ".\single-process-codex-config.toml" "$codexDir\config.toml"
Write-Host "✅ Codex config deployed" -ForegroundColor Green

# Cursor config
$cursorDir = "C:\Users\hyper\.cursor"
if (!(Test-Path $cursorDir)) {
    New-Item -ItemType Directory -Path $cursorDir -Force | Out-Null
}
Copy-Item ".\cursor\mcp-single-process.json" "$cursorDir\mcp.json"
Write-Host "✅ Cursor config deployed" -ForegroundColor Green

# Test consolidated server
Write-Host "🧪 Testing consolidated server..." -ForegroundColor Blue
Start-Process -FilePath "node" -ArgumentList "consolidated-mcp-server.js" -WindowStyle Hidden
Start-Sleep -Seconds 5

$nodeCount = (Get-Process -Name "node" -ErrorAction SilentlyContinue).Count
Write-Host "📊 Node.js processes: $nodeCount" -ForegroundColor Cyan

if ($nodeCount -eq 1) {
    Write-Host "🎉 PERFECT! Only 1 Node.js process!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Multiple processes detected" -ForegroundColor Yellow
}

Write-Host "🎉 Deployment completed!" -ForegroundColor Green
Write-Host "💡 Restart your IDEs to use the new configuration" -ForegroundColor Yellow
