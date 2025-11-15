# Test Chroma MCP Server Connectivity
# This script tests the Chroma MCP server configuration and connectivity

Write-Host "🔍 Testing Chroma MCP Server Configuration..." -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

# Test 1: Check if Python is available
Write-Host "`n1. Testing Python availability..." -ForegroundColor Yellow
try {
    $pythonVersion = python --version 2>&1
    Write-Host "✅ Python found: $pythonVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Python not found in PATH" -ForegroundColor Red
    Write-Host "Please install Python or add it to your PATH" -ForegroundColor Red
    exit 1
}

# Test 2: Check if chroma_mcp_server module is available
Write-Host "`n2. Testing chroma_mcp_server module..." -ForegroundColor Yellow
try {
    $moduleTest = python -c "import chroma_mcp_server; print('chroma_mcp_server module found')" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ $moduleTest" -ForegroundColor Green
    } else {
        Write-Host "❌ chroma_mcp_server module not found" -ForegroundColor Red
        Write-Host "Install with: pip install chroma-mcp-server" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error testing chroma_mcp_server module: $_" -ForegroundColor Red
}

# Test 3: Test Chroma server connectivity (if running)
Write-Host "`n3. Testing Chroma server connectivity..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/heartbeat" -TimeoutSec 5 -ErrorAction SilentlyContinue
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Chroma server is running on localhost:8000" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠️  Chroma server not running on localhost:8000" -ForegroundColor Yellow
    Write-Host "Start Chroma server with: chroma run --host localhost --port 8000" -ForegroundColor Yellow
}

# Test 4: Test MCP server startup
Write-Host "`n4. Testing MCP server startup..." -ForegroundColor Yellow
try {
    $env:CHROMA_CLIENT_TYPE = "http"
    $env:CHROMA_HOST = "localhost"
    $env:CHROMA_PORT = "8000"
    
    Write-Host "Starting Chroma MCP server with environment variables..." -ForegroundColor Gray
    $process = Start-Process -FilePath "python" -ArgumentList @("-m", "chroma_mcp_server") -NoNewWindow -PassThru -RedirectStandardOutput "chroma_test_output.txt" -RedirectStandardError "chroma_test_error.txt"
    
    Start-Sleep -Seconds 3
    
    if (-not $process.HasExited) {
        Write-Host "✅ Chroma MCP server started successfully" -ForegroundColor Green
        $process.Kill()
        Write-Host "Process terminated for testing" -ForegroundColor Gray
    } else {
        Write-Host "❌ Chroma MCP server failed to start" -ForegroundColor Red
        if (Test-Path "chroma_test_error.txt") {
            $errorContent = Get-Content "chroma_test_error.txt" -Raw
            Write-Host "Error output: $errorContent" -ForegroundColor Red
        }
    }
} catch {
    Write-Host "❌ Error testing MCP server startup: $_" -ForegroundColor Red
}

# Cleanup test files
if (Test-Path "chroma_test_output.txt") { Remove-Item "chroma_test_output.txt" }
if (Test-Path "chroma_test_error.txt") { Remove-Item "chroma_test_error.txt" }

Write-Host "`n🎯 Chroma MCP Server Test Complete!" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

# Summary
Write-Host "`n📋 Configuration Summary:" -ForegroundColor Cyan
Write-Host "- Server: chroma-knowledge" -ForegroundColor White
Write-Host "- Command: python -m chroma_mcp_server" -ForegroundColor White
Write-Host "- Environment: CHROMA_CLIENT_TYPE=http, CHROMA_HOST=localhost, CHROMA_PORT=8000" -ForegroundColor White
Write-Host "- Priority: 3 (AI Model Integration)" -ForegroundColor White
Write-Host "- Capabilities: vector-search, knowledge-management, semantic-search, document-storage" -ForegroundColor White
