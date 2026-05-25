# PowerShell script to start Chroma with Docker
Write-Host "Starting Chroma Database with Docker..." -ForegroundColor Green

# Check if Docker is running
try {
    docker info | Out-Null
    Write-Host "Docker is running ✓" -ForegroundColor Green
} catch {
    Write-Host "Docker is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}

# Stop any existing Chroma container
Write-Host "Stopping any existing Chroma container..." -ForegroundColor Yellow
docker stop chroma-db 2>$null
docker rm chroma-db 2>$null

# Start Chroma using Docker Compose
Write-Host "Starting Chroma with Docker Compose..." -ForegroundColor Green
docker-compose -f docker-compose-chroma.yml up -d

# Wait for Chroma to be ready
Write-Host "Waiting for Chroma to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Check if Chroma is running
$containerStatus = docker ps --filter "name=chroma-db" --format "table {{.Status}}"
if ($containerStatus -like "*Up*") {
    Write-Host "Chroma is running successfully! ✓" -ForegroundColor Green
    Write-Host "Chroma API available at: http://localhost:8000" -ForegroundColor Cyan
    Write-Host "Chroma UI available at: http://localhost:8000/docs" -ForegroundColor Cyan
} else {
    Write-Host "Failed to start Chroma. Check logs with: docker logs chroma-db" -ForegroundColor Red
}
