#!/usr/bin/env pwsh

Write-Host "🚀 Deploying Optimized AI Orchestrator Configuration..." -ForegroundColor Green

# Stop any running orchestrators
Write-Host "🛑 Stopping existing orchestrators..." -ForegroundColor Yellow
Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*orchestrator*" } | Stop-Process -Force -ErrorAction SilentlyContinue

# Deploy optimized orchestrator
Write-Host "📦 Deploying optimized orchestrator..." -ForegroundColor Yellow
Copy-Item "unified-ai-orchestrator-optimized.js" "unified-ai-orchestrator-secure.js" -Force

# Deploy optimized configurations
Write-Host "⚙️  Deploying optimized configurations..." -ForegroundColor Yellow

# Cursor MCP configuration
$cursorMcpPath = "C:\Users\hyper\.cursor\mcp.json"
if (Test-Path $cursorMcpPath) {
    Copy-Item "optimized-cline-config.json" $cursorMcpPath -Force
    Write-Host "✅ Updated Cursor MCP configuration" -ForegroundColor Green
}

# Codex configuration
$codexConfigPath = "C:\Users\hyper\.codex\config.toml"
if (Test-Path $codexConfigPath) {
    Copy-Item "optimized-codex-config.toml" $codexConfigPath -Force
    Write-Host "✅ Updated Codex configuration" -ForegroundColor Green
}

# Cline configuration
$clineConfigPath = "C:\Users\hyper\AppData\Roaming\Cursor\User\globalStorage\saoudrizwan.claude-dev\settings\cline_mcp_settings.json"
if (Test-Path $clineConfigPath) {
    Copy-Item "optimized-cline-config.json" $clineConfigPath -Force
    Write-Host "✅ Updated Cline configuration" -ForegroundColor Green
}

# Start optimized orchestrator
Write-Host "🚀 Starting optimized orchestrator..." -ForegroundColor Yellow
Start-Process -FilePath "node" -ArgumentList "unified-ai-orchestrator-optimized.js" -WindowStyle Hidden

# Wait for startup
Write-Host "⏳ Waiting for orchestrator to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Test health endpoint
Write-Host "🏥 Testing orchestrator health..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "http://localhost:8081/health" -TimeoutSec 10
    Write-Host "✅ Orchestrator is healthy!" -ForegroundColor Green
    Write-Host "📊 Running servers: $($response.running)" -ForegroundColor Cyan
    Write-Host "📋 Server list: $($response.servers -join ', ')" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Orchestrator health check failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n🎉 Optimized configuration deployed!" -ForegroundColor Green
Write-Host "📝 Removed servers: puppeteer, chrome-devtools, smart-crawler, terry, postgres" -ForegroundColor Yellow
Write-Host "✅ Kept servers: serena, zen-mcp-server, filesystem, memory, sequential-thinking, codex-mcp-server, gemini-mcp-tool, everything, playwright" -ForegroundColor Green
Write-Host "`n💡 You can now use your CLI tools with the optimized MCP server configuration!" -ForegroundColor Cyan
