# Suggested Commands for FWBer.me Development

## Setup Commands
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (for modern stack)
cd fwber-frontend && npm install
cd fwber-backend && composer install

# Start development servers
# Legacy PHP (XAMPP)
C:\xampp\apache\bin\httpd.exe -k start
C:\xampp\mysql\bin\mysqld.exe --console

# Modern stack
cd fwber-frontend && npm run dev
cd fwber-backend && php artisan serve
```

## Database Commands
```bash
# Create database
C:\xampp\mysql\bin\mysql.exe -u root -p -e "CREATE DATABASE fwber;"

# Import schema
C:\xampp\mysql\bin\mysql.exe -u fwber -p'Temppass0!' fwber < setup-database.sql

# Test database connection
C:\xampp\php\php.exe test-profile-flow.php
```

## Testing Commands
```bash
# Test profile form functionality
# Open in browser: http://localhost/test-profile-form.php

# Test avatar generation
# Open in browser: http://localhost/test-avatar-generation.php

# Run PHP built-in server for testing
C:\xampp\php\php.exe -S localhost:8000
```

## Git Commands
```bash
# Check status
git status

# Add changes
git add .

# Commit changes
git commit -m "Description of changes"

# Push to remote
git push origin main
```

## Windows-Specific Commands
```powershell
# PowerShell commands
Get-Command -Type Application | Select-Object -ExpandProperty Path
Get-Process | Where-Object {$_.ProcessName -like "*mysql*"}
Get-Process | Where-Object {$_.ProcessName -like "*httpd*"}
```