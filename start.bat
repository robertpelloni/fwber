@echo off
setlocal
title FWBer
cd /d "%~dp0"

echo [FWBer] Starting development environment...
echo.

where npm >nul 2>nul
if errorlevel 1 (
    echo [FWBer] npm not found. Please install it.
    pause
    exit /b 1
)

echo [FWBer] Installing backend dependencies...
cd fwber-backend-ts
call npm install
if errorlevel 1 (
    echo [FWBer] Backend install failed.
    pause
    exit /b 1
)

echo [FWBer] Starting backend (port 4000)...
start "FWBer Backend" cmd /k "npm run dev"

echo [FWBer] Installing frontend dependencies...
cd ..\fwber-frontend
call npm install
if errorlevel 1 (
    echo [FWBer] Frontend install failed.
    pause
    exit /b 1
)

echo [FWBer] Starting frontend (port 3000)...
start "FWBer Frontend" cmd /k "npm run dev"

cd ..
echo.
echo [FWBer] Both services starting:
echo   Backend:  http://localhost:4000
echo   Frontend: http://localhost:3000
echo.
echo Press any key to close this launcher (services will keep running)...
pause >nul
endlocal
