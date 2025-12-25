# Fix Local Environment Configuration
# This script aligns the local .env files with the docker-compose.dev.yml configuration
# to ensure Mercure real-time features work correctly.

Write-Host "🔧 Fixing Local Environment Configuration..." -ForegroundColor Cyan

# 1. Fix Backend .env
$backendEnvPath = "fwber-backend\.env"
if (Test-Path $backendEnvPath) {
    Write-Host "Updating Backend .env..." -ForegroundColor Yellow
    $content = Get-Content $backendEnvPath -Raw
    
    # Replace Keys
    $content = $content -replace "MERCURE_PUBLISHER_JWT_KEY=.*", "MERCURE_PUBLISHER_JWT_KEY=uWnEOi51TibZqRn3YbRMvu0XbZwWp42X6z6s0aZMcAw="
    $content = $content -replace "MERCURE_SUBSCRIBER_JWT_KEY=.*", "MERCURE_SUBSCRIBER_JWT_KEY=0DyZctGxb2WUcwL3XH0HFq+5XWgnEI9ojHn5Y2cY3ic="
    
    # Replace URLs
    $content = $content -replace "MERCURE_PUBLIC_URL=.*", "MERCURE_PUBLIC_URL=http://localhost:3001/.well-known/mercure"
    $content = $content -replace "MERCURE_INTERNAL_URL=.*", "MERCURE_INTERNAL_URL=http://localhost:3001/.well-known/mercure"
    $content = $content -replace "MERCURE_COOKIE_DOMAIN=.*", "MERCURE_COOKIE_DOMAIN=localhost"
    
    Set-Content $backendEnvPath $content
    Write-Host "✅ Backend .env updated." -ForegroundColor Green
} else {
    Write-Host "⚠️ Backend .env not found. Copying from .env.example..." -ForegroundColor Yellow
    Copy-Item "fwber-backend\.env.example" $backendEnvPath
    Write-Host "✅ Backend .env created." -ForegroundColor Green
}

# 2. Fix Frontend .env.local
$frontendEnvPath = "fwber-frontend\.env.local"
if (Test-Path $frontendEnvPath) {
    Write-Host "Updating Frontend .env.local..." -ForegroundColor Yellow
    $content = Get-Content $frontendEnvPath -Raw
    
    # Replace Mercure URL
    if ($content -match "NEXT_PUBLIC_MERCURE_URL") {
        $content = $content -replace "NEXT_PUBLIC_MERCURE_URL=.*", "NEXT_PUBLIC_MERCURE_URL=http://localhost:3001/.well-known/mercure"
    } else {
        $content += "`nNEXT_PUBLIC_MERCURE_URL=http://localhost:3001/.well-known/mercure"
    }
    
    Set-Content $frontendEnvPath $content
    Write-Host "✅ Frontend .env.local updated." -ForegroundColor Green
} else {
    Write-Host "⚠️ Frontend .env.local not found. Creating..." -ForegroundColor Yellow
    Copy-Item "fwber-frontend\.env.local.example" $frontendEnvPath
    Write-Host "✅ Frontend .env.local created." -ForegroundColor Green
}

Write-Host "`n🎉 Configuration Fixed!" -ForegroundColor Cyan
Write-Host "Please restart your backend (php artisan serve) and frontend (npm run dev) servers." -ForegroundColor White
