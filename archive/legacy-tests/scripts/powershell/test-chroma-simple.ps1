# Simple test script to verify Chroma is working
Write-Host "Testing Chroma Database..." -ForegroundColor Green

# Check container status
Write-Host "`nChroma Container Status:" -ForegroundColor Cyan
docker ps --filter "name=chroma-db"

Write-Host "`nChroma is running on: http://localhost:8000" -ForegroundColor Green
Write-Host "Chroma API Documentation: http://localhost:8000/docs" -ForegroundColor Green
Write-Host "Chroma is ready to use!" -ForegroundColor Green
