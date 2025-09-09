# FWBer Development Environment Test Script
# Run this in PowerShell to check your XAMPP setup

Write-Host "=== FWBer Environment Check ===" -ForegroundColor Green

# Check if XAMPP processes are running
Write-Host "`nChecking XAMPP Services..." -ForegroundColor Yellow
$apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
$mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue

if ($apacheProcess) {
    Write-Host "✓ Apache is running (PID: $($apacheProcess.Id))" -ForegroundColor Green
} else {
    Write-Host "✗ Apache is not running" -ForegroundColor Red
    Write-Host "  Start XAMPP Control Panel and start Apache" -ForegroundColor Yellow
}

if ($mysqlProcess) {
    Write-Host "✓ MySQL is running (PID: $($mysqlProcess.Id))" -ForegroundColor Green
} else {
    Write-Host "✗ MySQL is not running" -ForegroundColor Red
    Write-Host "  Start XAMPP Control Panel and start MySQL" -ForegroundColor Yellow
}

# Check if ports are listening
Write-Host "`nChecking Ports..." -ForegroundColor Yellow
$port80 = Get-NetTCPConnection -LocalPort 80 -ErrorAction SilentlyContinue
$port3306 = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue

if ($port80) {
    Write-Host "✓ Port 80 (HTTP) is listening" -ForegroundColor Green
} else {
    Write-Host "✗ Port 80 (HTTP) is not listening" -ForegroundColor Red
}

if ($port3306) {
    Write-Host "✓ Port 3306 (MySQL) is listening" -ForegroundColor Green
} else {
    Write-Host "✗ Port 3306 (MySQL) is not listening" -ForegroundColor Red
}

# Test PHP
Write-Host "`nTesting PHP..." -ForegroundColor Yellow
try {
    $phpVersion = php --version
    if ($phpVersion) {
        $version = ($phpVersion -split "`n")[0]
        Write-Host "✓ $version" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ PHP not found in PATH" -ForegroundColor Red
    Write-Host "  Add C:\xampp\php to your PATH environment variable" -ForegroundColor Yellow
}

# Test database connection
Write-Host "`nTesting Database Connection..." -ForegroundColor Yellow
$testScript = @"
<?php
include '_secrets.php';
try {
    `$link = new mysqli(`$dburl, `$dbuser, `$dbpass, `$dbname);
    if (`$link->connect_error) {
        echo "Connection failed: " . `$link->connect_error . "\n";
        exit(1);
    }
    echo "Database connection successful!\n";
    echo "Server info: " . `$link->server_info . "\n";
    `$link->close();
} catch (Exception `$e) {
    echo "Error: " . `$e->getMessage() . "\n";
    exit(1);
}
?>
"@

$testScript | Out-File -FilePath "test-db.php" -Encoding UTF8
$dbResult = php test-db.php 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ $dbResult" -ForegroundColor Green
} else {
    Write-Host "✗ Database connection failed: $dbResult" -ForegroundColor Red
}
Remove-Item "test-db.php" -ErrorAction SilentlyContinue

# Check file permissions
Write-Host "`nChecking File Permissions..." -ForegroundColor Yellow
$avatarsDir = ".\avatars"
$jsDir = ".\js"

if (Test-Path $avatarsDir) {
    Write-Host "✓ Avatars directory exists" -ForegroundColor Green
} else {
    Write-Host "! Creating avatars directory..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $avatarsDir -Force | Out-Null
    Write-Host "✓ Avatars directory created" -ForegroundColor Green
}

if (Test-Path $jsDir) {
    Write-Host "✓ JavaScript directory exists" -ForegroundColor Green
} else {
    Write-Host "! Creating js directory..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $jsDir -Force | Out-Null
    Write-Host "✓ JavaScript directory created" -ForegroundColor Green
}

# Test URL access
Write-Host "`nTesting Web Access..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/index.php" -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ Website accessible at http://localhost" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ Cannot access http://localhost" -ForegroundColor Red
    Write-Host "  Make sure Apache is running and check firewall settings" -ForegroundColor Yellow
}

Write-Host "`n=== Environment Check Complete ===" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. If any services are not running, start them in XAMPP Control Panel" -ForegroundColor White
Write-Host "2. Run the database setup: Import setup-database.sql into phpMyAdmin" -ForegroundColor White
Write-Host "3. Configure API keys in _secrets.php" -ForegroundColor White
Write-Host "4. Test the landing page at http://localhost/index.php" -ForegroundColor White

Read-Host "`nPress Enter to continue..."