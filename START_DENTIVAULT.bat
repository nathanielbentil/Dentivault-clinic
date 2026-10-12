@echo off
title DentiVault Clinical Security Suite
set "PATH=C:\Users\NATINO\AppData\Local\Programs\PHP;%PATH%"

echo ========================================================
echo   DentiVault - Enterprise Security & DR System
echo ========================================================
echo.

:: Check if port 8000 is already active
netstat -ano | findstr :8000 | findstr LISTENING >nul
if %errorlevel% == 0 (
    echo [OK] Live Server is already running on port 8000!
) else (
    echo [*] Starting local PHP server on http://127.0.0.1:8000...
    start /b php -S 127.0.0.1:8000 -t "%~dp0backend\public" >nul 2>&1
    timeout /t 2 >nul
)

echo [*] Opening live portal in your browser...
start "" "http://127.0.0.1:8000"

echo.
echo Application launched successfully!
timeout /t 3 >nul
exit
