# Test MCP Servers for Windows (Claude Desktop)
# This script tests the MCP servers using Windows paths

Write-Host "🔧 Testing MCP Servers for Claude Desktop" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

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
        
        # Test if the command exists
        if (-not (Test-Path $Command)) {
            Write-Host "❌ $Name: COMMAND NOT FOUND" -ForegroundColor Red
            Write-Host "Path: $Command" -ForegroundColor Red
            return $false
        }
        
        Write-Host "✅ $Name: Command exists" -ForegroundColor Green
        
        # Test help command
        if ($Args) {
            $result = & $Command $Args.Split(' ') 2>&1
        } else {
            $result = & $Command --help 2>&1
        }
        
        if ($LASTEXITCODE -eq 0 -or $result -match "help|usage|options") {
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

Write-Host "`n📋 Testing MCP Server Executables" -ForegroundColor Magenta
Write-Host "=================================" -ForegroundColor Magenta

# Test Serena MCP Server
$results["Serena MCP Server"] = Test-Command -Name "Serena MCP Server" -Command "C:\Users\hyper\serena\.venv\Scripts\python.exe" -Args "-m serena --help" -WorkingDir "C:\Users\hyper\serena"

# Test Zen MCP Server
$results["Zen MCP Server"] = Test-Command -Name "Zen MCP Server" -Command "C:\Users\hyper\zen-mcp-server\.venv\Scripts\python.exe" -Args "server.py --help" -WorkingDir "C:\Users\hyper\zen-mcp-server"

# Test Chroma MCP Server
$results["Chroma MCP Server"] = Test-Command -Name "Chroma MCP Server" -Command "C:\Users\hyper\chroma-env-312\Scripts\chroma-mcp-server.exe" -Args "--help"

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
    Write-Host "🎉 All MCP servers should work with Claude Desktop!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Some MCP servers need attention. Check the errors above." -ForegroundColor Yellow
}

Write-Host "`n🔧 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Restart Claude Desktop to pick up the new configuration" -ForegroundColor White
Write-Host "2. Check Claude Desktop logs for any remaining errors" -ForegroundColor White
Write-Host "3. Test MCP server functionality within Claude Desktop" -ForegroundColor White

Write-Host "`n✨ MCP Server testing complete!" -ForegroundColor Cyan
