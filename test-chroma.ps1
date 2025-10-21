# Test script to verify Chroma is working
Write-Host "Testing Chroma Database..." -ForegroundColor Green

# Test basic connectivity
try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/version" -Method GET -ErrorAction Stop
    Write-Host "Chroma API is responding: $($response)" -ForegroundColor Green
} catch {
    Write-Host "Chroma API test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Test if we can access the docs
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/docs" -Method GET -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "Chroma documentation is accessible ✓" -ForegroundColor Green
    }
} catch {
    Write-Host "Chroma docs test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nChroma Database Status:" -ForegroundColor Cyan
docker ps --filter "name=chroma-db" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
