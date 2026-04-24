@echo off
setlocal
title FWBer
cd /d "%~dp0"

echo [FWBer] Starting...
where npm >nul 2>nul
if errorlevel 1 (
    echo [FWBer] npm not found. Please install it.
    pause
    exit /b 1
)

npm install && npm start

if errorlevel 1 (
    echo [FWBer] Exited with error code %errorlevel%.
    pause
)
endlocal
