# Test All MCP Servers Script
# This script tests all MCP servers to ensure they're working correctly

Write-Host "🔧 Testing All MCP Servers" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan

# Function to test a command
function Test-Command {
    param(
        [string]$Name,
        [string]$Command,
        [string]$Args = "",
        [string]$WorkingDir = ""
    )
    
    Write-Host "`n🧪 Testing $Name..." -ForegroundColor Yellow
    
    try {
        if ($WorkingDir) {
            Push-Location $WorkingDir
        }
        
        if ($Args) {
            $result = & $Command $Args.Split(' ') 2>&1
        } else {
            $result = & $Command 2>&1
        }
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ $Name: SUCCESS" -ForegroundColor Green
            return $true
        } else {
            Write-Host "❌ $Name: FAILED" -ForegroundColor Red
            Write-Host "Error: $result" -ForegroundColor Red
            return $false
        }
    }
    catch {
        Write-Host "❌ $Name: ERROR" -ForegroundColor Red
        Write-Host "Exception: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    finally {
        if ($WorkingDir) {
            Pop-Location
        }
    }
}

# Test results
$results = @{}

Write-Host "`n📋 Prerequisites Check" -ForegroundColor Magenta
Write-Host "=====================" -ForegroundColor Magenta

# Check if Chroma server is running
Write-Host "`n🌐 Checking Chroma Server..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/v1/heartbeat" -TimeoutSec 5
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Chroma Server: RUNNING" -ForegroundColor Green
        $results["Chroma Server"] = $true
    } else {
        Write-Host "❌ Chroma Server: NOT RESPONDING" -ForegroundColor Red
        $results["Chroma Server"] = $false
    }
}
catch {
    Write-Host "❌ Chroma Server: NOT RUNNING" -ForegroundColor Red
    Write-Host "Please start Chroma server with: chroma run --host localhost --port 8000" -ForegroundColor Yellow
    $results["Chroma Server"] = $false
}

# Check Python environment
Write-Host "`n🐍 Checking Python Environment..." -ForegroundColor Yellow
$pythonPath = "C:\Users\hyper\chroma-env-312\Scripts\python.exe"
if (Test-Path $pythonPath) {
    Write-Host "✅ Python Environment: FOUND" -ForegroundColor Green
    $results["Python Environment"] = $true
} else {
    Write-Host "❌ Python Environment: NOT FOUND" -ForegroundColor Red
    $results["Python Environment"] = $false
}

# Check UV installations
Write-Host "`n📦 Checking UV Installations..." -ForegroundColor Yellow
$serenaPath = "C:\Users\hyper\serena"
$zenPath = "C:\Users\hyper\zen-mcp-server"

if (Test-Path $serenaPath) {
    Write-Host "✅ Serena: FOUND" -ForegroundColor Green
    $results["Serena Installation"] = $true
} else {
    Write-Host "❌ Serena: NOT FOUND" -ForegroundColor Red
    $results["Serena Installation"] = $false
}

if (Test-Path $zenPath) {
    Write-Host "✅ Zen: FOUND" -ForegroundColor Green
    $results["Zen Installation"] = $true
} else {
    Write-Host "❌ Zen: NOT FOUND" -ForegroundColor Red
    $results["Zen Installation"] = $false
}

Write-Host "`n🔧 MCP Server Tests" -ForegroundColor Magenta
Write-Host "===================" -ForegroundColor Magenta

# Test Chroma MCP Server
$results["Chroma MCP Server"] = Test-Command -Name "Chroma MCP Server" -Command "C:\Users\hyper\chroma-env-312\Scripts\chroma-mcp-server.exe" -Args "--help"

# Test Serena MCP Server
$results["Serena MCP Server"] = Test-Command -Name "Serena MCP Server" -Command "uv" -Args "run serena --help" -WorkingDir "C:\Users\hyper\serena"

# Test Zen MCP Server
$results["Zen MCP Server"] = Test-Command -Name "Zen MCP Server" -Command "uv" -Args "run zen-mcp-server --help" -WorkingDir "C:\Users\hyper\zen-mcp-server"

# Test Sequential Thinking
$results["Sequential Thinking"] = Test-Command -Name "Sequential Thinking" -Command "npx" -Args "-y @modelcontextprotocol/server-sequential-thinking --help"

# Test Filesystem
$results["Filesystem"] = Test-Command -Name "Filesystem" -Command "npx" -Args "-y @modelcontextprotocol/server-filesystem --help"

# Test Memory
$results["Memory"] = Test-Command -Name "Memory" -Command "npx" -Args "-y @modelcontextprotocol/server-memory --help"

# Test Everything
$results["Everything"] = Test-Command -Name "Everything" -Command "npx" -Args "-y @modelcontextprotocol/server-everything --help"

Write-Host "`n📊 Test Results Summary" -ForegroundColor Magenta
Write-Host "=======================" -ForegroundColor Magenta

$successCount = 0
$totalCount = $results.Count

foreach ($key in $results.Keys) {
    if ($results[$key]) {
        Write-Host "✅ $key" -ForegroundColor Green
        $successCount++
    } else {
        Write-Host "❌ $key" -ForegroundColor Red
    }
}

Write-Host "`n📈 Overall Results: $successCount/$totalCount tests passed" -ForegroundColor Cyan

if ($successCount -eq $totalCount) {
    Write-Host "🎉 All MCP servers are working correctly!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Some MCP servers need attention. Check the errors above." -ForegroundColor Yellow
}

Write-Host "`n🔧 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Fix any failed tests above" -ForegroundColor White
Write-Host "2. Start Chroma server if not running: chroma run --host localhost --port 8000" -ForegroundColor White
Write-Host "3. Test MCP servers with AI tools" -ForegroundColor White
Write-Host "4. Check configuration files for any issues" -ForegroundColor White

Write-Host "`n✨ MCP Server testing complete!" -ForegroundColor Cyan
