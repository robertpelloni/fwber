# Quick Database Fix Script
# Checks MySQL and helps configure Laravel

Write-Host "=== fwber Database Connection Fix ===" -ForegroundColor Cyan
Write-Host ""

# Test if MySQL is running
Write-Host "1. Checking if MySQL is running..." -ForegroundColor Yellow
try {
    $mysqlProcess = Get-Process mysqld -ErrorAction SilentlyContinue
    if ($mysqlProcess) {
        Write-Host "   ✅ MySQL is running (PID: $($mysqlProcess.Id))" -ForegroundColor Green
    } else {
        Write-Host "   ❌ MySQL is NOT running" -ForegroundColor Red
        Write-Host "   Please start MySQL/XAMPP first" -ForegroundColor Yellow
        exit 1
    }
} catch {
    Write-Host "   ⚠️ Could not check MySQL status" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "2. Testing database connection..." -ForegroundColor Yellow

# Test connection with PHP
$testResult = php test-db-connection.php 2>&1

if ($testResult -match "Connection successful") {
    Write-Host "   ✅ Database connection works!" -ForegroundColor Green
} else {
    Write-Host "   ❌ Database connection failed" -ForegroundColor Red
    Write-Host ""
    Write-Host "Options to fix:" -ForegroundColor Yellow
    Write-Host "   A) Update fwber-backend/.env with correct MySQL password" -ForegroundColor White
    Write-Host "   B) Create MySQL user 'fwber' with password 'Temppass0!'" -ForegroundColor White
    Write-Host "   C) Use MySQL root user in .env file" -ForegroundColor White
    Write-Host ""
    Write-Host "To create MySQL user, run:" -ForegroundColor Yellow
    Write-Host "   mysql -u root -p" -ForegroundColor White
    Write-Host "   Then execute:" -ForegroundColor White
    Write-Host "   CREATE DATABASE IF NOT EXISTS fwber;" -ForegroundColor Cyan
    Write-Host "   CREATE USER IF NOT EXISTS 'fwber'@'localhost' IDENTIFIED BY 'Temppass0!';" -ForegroundColor Cyan
    Write-Host "   GRANT ALL PRIVILEGES ON fwber.* TO 'fwber'@'localhost';" -ForegroundColor Cyan
    Write-Host "   FLUSH PRIVILEGES;" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

Write-Host ""
Write-Host "3. Checking Laravel .env file..." -ForegroundColor Yellow

if (Test-Path "fwber-backend\.env") {
    Write-Host "   ✅ Laravel .env exists" -ForegroundColor Green
    
    # Check if APP_KEY is set
    $envContent = Get-Content "fwber-backend\.env" -Raw
    if ($envContent -match "APP_KEY=base64:") {
        Write-Host "   ✅ APP_KEY is generated" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️ APP_KEY not generated" -ForegroundColor Yellow
        Write-Host "   Running: php artisan key:generate" -ForegroundColor White
        cd fwber-backend
        php artisan key:generate --force
        cd ..
    }
} else {
    Write-Host "   ❌ Laravel .env missing" -ForegroundColor Red
    Write-Host "   Creating from template..." -ForegroundColor Yellow
    
    $envTemplate = @"
APP_NAME=fwber
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber
DB_USERNAME=fwber
DB_PASSWORD=Temppass0!

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

ENCRYPTION_KEY=WWX6zi4UyNdkV0nBNKyloHA0tjNz+f/91KlO4NOcDgg=
"@
    
    $envTemplate | Out-File -FilePath "fwber-backend\.env" -Encoding UTF8
    Write-Host "   ✅ Created fwber-backend\.env" -ForegroundColor Green
    Write-Host "   🔧 Please update DB_PASSWORD if needed" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "4. Ready to run migrations!" -ForegroundColor Green
Write-Host "   Run: cd fwber-backend && php artisan migrate --force" -ForegroundColor Cyan
Write-Host ""


