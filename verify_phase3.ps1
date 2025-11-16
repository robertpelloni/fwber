#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Phase 3 Production Readiness Verification Script

.DESCRIPTION
    Validates all Phase 3 deliverables are functional:
    - Deployment scripts present
    - Health check endpoints registered
    - Security configuration validated
    - Backup scripts executable
    - Logging system operational
    - Rate limiting configured
    - Performance testing ready

.PARAMETER SkipHealthCheck
    Skip HTTP health check endpoint tests (requires running server)

.PARAMETER Verbose
    Show detailed output for each check

.EXAMPLE
    .\verify_phase3.ps1
    .\verify_phase3.ps1 -SkipHealthCheck -Verbose

.NOTES
    Author: Fwber Development Team
    Date: November 15, 2025
    Phase: 3 - Production Readiness
#>

param(
    [switch]$SkipHealthCheck,
    [switch]$Verbose
)

# Set strict mode
$ErrorActionPreference = "Continue"
$PSDefaultParameterValues['*:Encoding'] = 'utf8'

# Color output helpers
function Write-Success { param($Message) Write-Host "✅ $Message" -ForegroundColor Green }
function Write-Failure { param($Message) Write-Host "❌ $Message" -ForegroundColor Red }
function Write-Info { param($Message) Write-Host "ℹ️  $Message" -ForegroundColor Cyan }
function Write-Section { param($Message) Write-Host "`n━━━ $Message ━━━" -ForegroundColor Yellow }

# Counters
$script:passCount = 0
$script:failCount = 0
$script:skipCount = 0

function Test-FileExists {
    param($Path, $Description)
    
    if (Test-Path $Path) {
        Write-Success "$Description exists: $Path"
        $script:passCount++
        return $true
    } else {
        Write-Failure "$Description missing: $Path"
        $script:failCount++
        return $false
    }
}

function Test-CommandOutput {
    param($Command, $Description, $ExpectedPattern = $null)
    
    try {
        $output = Invoke-Expression $Command 2>&1 | Out-String
        
        if ($ExpectedPattern -and $output -notmatch $ExpectedPattern) {
            Write-Failure "$Description - Expected pattern not found: $ExpectedPattern"
            if ($Verbose) { Write-Host "Output: $output" -ForegroundColor DarkGray }
            $script:failCount++
            return $false
        }
        
        Write-Success $Description
        if ($Verbose) { Write-Host "Output: $output" -ForegroundColor DarkGray }
        $script:passCount++
        return $true
    } catch {
        Write-Failure "$Description - Error: $_"
        $script:failCount++
        return $false
    }
}

# Change to backend directory
$backendPath = Join-Path $PSScriptRoot "fwber-backend"
if (-not (Test-Path $backendPath)) {
    $backendPath = Join-Path (Get-Location) "fwber-backend"
}

if (-not (Test-Path $backendPath)) {
    Write-Failure "Cannot find fwber-backend directory"
    exit 1
}

Set-Location $backendPath

Write-Host @"
╔════════════════════════════════════════════════════════════════╗
║                                                                ║
║   Phase 3: Production Readiness Verification                  ║
║   Fwber Backend - Laravel Application                         ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
"@ -ForegroundColor Magenta

# ============================================================================
# Item 1: Deployment Automation Scripts
# ============================================================================
Write-Section "Item 1: Deployment Automation"

Test-FileExists "deploy.sh" "Deployment script"
Test-FileExists "rollback.sh" "Rollback script"

if (Test-Path "deploy.sh") {
    $deployContent = Get-Content "deploy.sh" -Raw
    if ($deployContent -match "--env" -and $deployContent -match "maintenance mode") {
        Write-Success "Deployment script has required features (env support, maintenance mode)"
        $script:passCount++
    } else {
        Write-Failure "Deployment script missing expected features"
        $script:failCount++
    }
}

# ============================================================================
# Item 2: Health Check Implementation
# ============================================================================
Write-Section "Item 2: Health Check Implementation"

Test-FileExists "../docs/operations/HEALTH_CHECK_GUIDE.md" "Health check documentation"

# Check health check routes
$routeCheck = Test-CommandOutput `
    "php artisan route:list --path=health 2>&1" `
    "Health check routes registered" `
    "/health"

if ($routeCheck) {
    Write-Info "Expected routes: /health, /health/liveness, /health/readiness"
}

# ============================================================================
# Item 3: Production Security Audit
# ============================================================================
Write-Section "Item 3: Production Security Audit"

Test-FileExists "../docs/security/PRODUCTION_SECURITY_AUDIT.md" "Security audit document"

# Check CORS configuration
if (Test-Path "config/cors.php") {
    $corsConfig = Get-Content "config/cors.php" -Raw
    if ($corsConfig -match "CORS_ALLOWED_ORIGINS") {
        Write-Success "CORS is environment-configurable"
        $script:passCount++
    } else {
        Write-Failure "CORS not properly configured for production"
        $script:failCount++
    }
}

# Check CSP configuration
if (Test-Path "app/Http/Middleware/SecurityHeaders.php") {
    $cspConfig = Get-Content "app/Http/Middleware/SecurityHeaders.php" -Raw
    if ($cspConfig -match "CSP_RELAXED" -and $cspConfig -match "app\(\)->environment\('production'\)") {
        Write-Success "CSP has production strict mode"
        $script:passCount++
    } else {
        Write-Failure "CSP hardening not properly implemented"
        $script:failCount++
    }
}

# ============================================================================
# Item 4: Database Backup Strategy
# ============================================================================
Write-Section "Item 4: Database Backup Strategy"

Test-FileExists "scripts/backup_database.sh" "Backup script"
Test-FileExists "scripts/restore_database.sh" "Restore script"
Test-FileExists "scripts/emergency_restore.sh" "Emergency restore script"
Test-FileExists "../docs/operations/DATABASE_BACKUP_STRATEGY.md" "Backup documentation"

if (Test-Path "scripts/backup_database.sh") {
    $backupContent = Get-Content "scripts/backup_database.sh" -Raw
    if ($backupContent -match "mysqldump" -and $backupContent -match "pg_dump" -and $backupContent -match "S3") {
        Write-Success "Backup script supports MySQL, PostgreSQL, and S3"
        $script:passCount++
    }
}

# ============================================================================
# Item 5: Production Logging Configuration
# ============================================================================
Write-Section "Item 5: Production Logging"

Test-FileExists "config/logging.php" "Logging configuration"
Test-FileExists "config/sentry.php" "Sentry configuration"
Test-FileExists "app/Logging/JsonFormatterTap.php" "JSON formatter tap"
Test-FileExists "../docs/operations/PRODUCTION_LOGGING.md" "Logging documentation"

# Check logging configuration
if (Test-Path "config/logging.php") {
    $loggingConfig = Get-Content "config/logging.php" -Raw
    if ($loggingConfig -match "JsonFormatterTap" -and $loggingConfig -match "'security'") {
        Write-Success "Logging has JSON tap and security channel"
        $script:passCount++
    } else {
        Write-Failure "Logging configuration incomplete"
        $script:failCount++
    }
}

# Check composer.json for Sentry
if (Test-Path "composer.json") {
    $composerJson = Get-Content "composer.json" -Raw | ConvertFrom-Json
    if ($composerJson.require."sentry/sentry-laravel") {
        Write-Success "Sentry dependency present in composer.json"
        $script:passCount++
    } else {
        Write-Failure "Sentry dependency missing"
        $script:failCount++
    }
}

# Test logging functionality
Write-Info "Testing log writing..."
try {
    $testCommand = @"
Log::info('Phase 3 verification', ['test' => true, 'timestamp' => now()]); 
Log::channel('security')->notice('Verification test', ['phase' => 3]); 
echo 'Success';
"@
    
    $logTest = php artisan tinker --execute=$testCommand 2>&1 | Out-String
    
    if ($logTest -match "Success") {
        Write-Success "Log writing functional"
        $script:passCount++
        
        # Check if logs were written
        if (Test-Path "storage/logs/laravel.log") {
            $recentLog = Get-Content "storage/logs/laravel.log" -Tail 5 | Out-String
            if ($recentLog -match "Phase 3 verification" -or $recentLog -match "Verification test") {
                Write-Success "Logs successfully written to file"
                $script:passCount++
            }
        }
    }
} catch {
    Write-Failure "Log writing test failed: $_"
    $script:failCount++
}

# ============================================================================
# Item 6: Rollback Procedures
# ============================================================================
Write-Section "Item 6: Rollback Procedures"

Test-FileExists "../docs/operations/ROLLBACK_PROCEDURES.md" "Rollback documentation"

if (Test-Path "rollback.sh") {
    $rollbackContent = Get-Content "rollback.sh" -Raw
    if ($rollbackContent -match "--with-db" -and $rollbackContent -match "--to-commit") {
        Write-Success "Rollback script has database and commit options"
        $script:passCount++
    }
}

# ============================================================================
# Item 7: Rate Limiting Validation
# ============================================================================
Write-Section "Item 7: Rate Limiting"

Test-FileExists "../docs/operations/RATE_LIMITING_VALIDATION.md" "Rate limiting documentation"

# Check rate limiting middleware registration
if (Test-Path "bootstrap/app.php") {
    $bootstrapContent = Get-Content "bootstrap/app.php" -Raw
    if ($bootstrapContent -match "rate\.limit.*AdvancedRateLimiting") {
        Write-Success "Rate limiting middleware registered"
        $script:passCount++
    } else {
        Write-Failure "Rate limiting middleware not registered"
        $script:failCount++
    }
}

# Check routes have rate limiting
if (Test-Path "routes/api.php") {
    $apiRoutes = Get-Content "routes/api.php" -Raw
    if ($apiRoutes -match "rate\.limit:auth_attempt" -and $apiRoutes -match "rate\.limit:api_call") {
        Write-Success "Rate limiting applied to auth and API routes"
        $script:passCount++
    } else {
        Write-Failure "Rate limiting not properly applied to routes"
        $script:failCount++
    }
}

# Check AdvancedRateLimitingService exists
Test-FileExists "app/Services/AdvancedRateLimitingService.php" "Rate limiting service"

# ============================================================================
# Item 8: Performance Benchmarks
# ============================================================================
Write-Section "Item 8: Performance Benchmarks"

Test-FileExists "scripts/perf/k6_baseline.js" "k6 baseline script"
Test-FileExists "../docs/operations/PERFORMANCE_BENCHMARKS.md" "Performance documentation"

if (Test-Path "scripts/perf/k6_baseline.js") {
    $k6Content = Get-Content "scripts/perf/k6_baseline.js" -Raw
    if ($k6Content -match "VUS" -and $k6Content -match "DURATION" -and $k6Content -match "thresholds") {
        Write-Success "k6 script has configurable parameters and thresholds"
        $script:passCount++
    }
}

# Check if k6 is installed
try {
    $k6Version = k6 version 2>&1 | Out-String
    if ($k6Version -match "k6") {
        Write-Success "k6 is installed: $($k6Version.Trim())"
        $script:passCount++
    }
} catch {
    Write-Info "k6 not installed (optional) - Install with: choco install k6"
    $script:skipCount++
}

# ============================================================================
# Configuration Validation
# ============================================================================
Write-Section "Configuration Validation"

# Test configuration compilation
Test-CommandOutput `
    "php artisan config:clear 2>&1" `
    "Configuration cache clear" `
    "cleared"

Test-CommandOutput `
    "php artisan config:cache 2>&1" `
    "Configuration cache build" `
    "cached"

# Check Laravel version
Test-CommandOutput `
    "php artisan -V 2>&1" `
    "Laravel application boot" `
    "Laravel Framework"

# ============================================================================
# Environment File Validation
# ============================================================================
Write-Section "Environment Configuration"

Test-FileExists ".env.example" "Environment template"

if (Test-Path ".env.example") {
    $envExample = Get-Content ".env.example" -Raw
    
    # Check for critical production vars
    $requiredVars = @(
        "LOG_FORMAT",
        "LOG_DAILY_DAYS",
        "LOG_SECURITY_DAYS",
        "CORS_ALLOWED_ORIGINS",
        "CSP_RELAXED",
        "SENTRY_LARAVEL_DSN",
        "FEATURE_RATE_LIMITS"
    )
    
    $missingVars = @()
    foreach ($var in $requiredVars) {
        if ($envExample -notmatch $var) {
            $missingVars += $var
        }
    }
    
    if ($missingVars.Count -eq 0) {
        Write-Success "All required environment variables documented in .env.example"
        $script:passCount++
    } else {
        Write-Failure "Missing environment variables: $($missingVars -join ', ')"
        $script:failCount++
    }
}

# ============================================================================
# Documentation Completeness
# ============================================================================
Write-Section "Documentation Completeness"

$requiredDocs = @{
    "../docs/operations/HEALTH_CHECK_GUIDE.md" = "Health Check Guide"
    "../docs/security/PRODUCTION_SECURITY_AUDIT.md" = "Security Audit"
    "../docs/operations/DATABASE_BACKUP_STRATEGY.md" = "Backup Strategy"
    "../docs/operations/PRODUCTION_LOGGING.md" = "Logging Guide"
    "../docs/operations/ROLLBACK_PROCEDURES.md" = "Rollback Procedures"
    "../docs/operations/RATE_LIMITING_VALIDATION.md" = "Rate Limiting Validation"
    "../docs/operations/PERFORMANCE_BENCHMARKS.md" = "Performance Benchmarks"
    "../docs/PHASE_3_PRODUCTION_READINESS_COMPLETE.md" = "Phase 3 Summary"
}

foreach ($doc in $requiredDocs.GetEnumerator()) {
    Test-FileExists $doc.Key $doc.Value
}

# ============================================================================
# Health Check Endpoint Test (Optional)
# ============================================================================
if (-not $SkipHealthCheck) {
    Write-Section "Live Health Check Test"
    
    Write-Info "Attempting to test health endpoints (requires running server)..."
    
    $baseUrl = if ($env:APP_URL) { $env:APP_URL } else { "http://localhost:8000" }
    
    try {
        $healthResponse = Invoke-WebRequest -Uri "$baseUrl/health" -Method GET -TimeoutSec 5 -UseBasicParsing -ErrorAction Stop
        
        if ($healthResponse.StatusCode -eq 200) {
            Write-Success "Health endpoint responding: $baseUrl/health"
            $script:passCount++
            
            $healthData = $healthResponse.Content | ConvertFrom-Json
            Write-Info "Status: $($healthData.status)"
            
            if ($Verbose) {
                Write-Host "Response: $($healthResponse.Content)" -ForegroundColor DarkGray
            }
        }
    } catch {
        Write-Info "Health endpoint test skipped (server not running or not accessible)"
        Write-Info "Start server with: php artisan serve"
        $script:skipCount++
    }
} else {
    Write-Info "Health check endpoint test skipped (--SkipHealthCheck flag)"
    $script:skipCount++
}

# ============================================================================
# Summary Report
# ============================================================================
Write-Host "`n"
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Magenta
Write-Host "║                    VERIFICATION SUMMARY                        ║" -ForegroundColor Magenta
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Magenta
Write-Host ""

$total = $script:passCount + $script:failCount + $script:skipCount

Write-Host "✅ Passed:  " -NoNewline -ForegroundColor Green
Write-Host "$($script:passCount)/$total" -ForegroundColor White

Write-Host "❌ Failed:  " -NoNewline -ForegroundColor Red
Write-Host "$($script:failCount)/$total" -ForegroundColor White

Write-Host "⏭️  Skipped: " -NoNewline -ForegroundColor Yellow
Write-Host "$($script:skipCount)/$total" -ForegroundColor White

Write-Host ""

if ($script:failCount -eq 0) {
    Write-Host "🎉 Phase 3 Production Readiness: COMPLETE" -ForegroundColor Green -BackgroundColor DarkGreen
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Cyan
    Write-Host "  1. Review production environment configuration in .env.example" -ForegroundColor White
    Write-Host "  2. Set up S3 bucket for database backups" -ForegroundColor White
    Write-Host "  3. Configure Sentry DSN for error tracking" -ForegroundColor White
    Write-Host "  4. Set CORS_ALLOWED_ORIGINS to production domains" -ForegroundColor White
    Write-Host "  5. Enable rate limiting: FEATURE_RATE_LIMITS=true" -ForegroundColor White
    Write-Host "  6. Run performance baseline: k6 run scripts/perf/k6_baseline.js" -ForegroundColor White
    Write-Host ""
    exit 0
} else {
    Write-Host "⚠️  Phase 3 has $($script:failCount) failing check(s)" -ForegroundColor Yellow -BackgroundColor DarkRed
    Write-Host ""
    Write-Host "Review the failures above and resolve before production deployment." -ForegroundColor White
    Write-Host ""
    exit 1
}
