@echo off
echo ==========================================================
echo   SecureAuthProject: Tool Verification Check
echo ==========================================================

set "PATH=C:\Users\NATINO\AppData\Local\Programs\PHP;C:\Users\NATINO\AppData\Local\Programs\Git\cmd;C:\Users\NATINO\AppData\Local\Programs\pgsql\bin;C:\Users\NATINO\AppData\Local\Microsoft\WinGet\Packages\axllent.mailpit_Microsoft.Winget.Source_8wekyb3d8bbwe;C:\Users\NATINO\AppData\Local\Microsoft\WinGet\Packages\FiloSottile.age_Microsoft.Winget.Source_8wekyb3d8bbwe\age;C:\Users\NATINO\AppData\Local\Microsoft\WinGet\Packages\Gitleaks.Gitleaks_Microsoft.Winget.Source_8wekyb3d8bbwe;%PATH%"

echo.
echo [1/8] Checking Git...
git --version
echo.
echo [2/8] Checking age (Encryption)...
age --version
echo.
echo [3/8] Checking Gitleaks (Secret Scanner)...
gitleaks version
echo.
echo [4/8] Checking Mailpit (Email Sandbox)...
mailpit version
echo.
echo [5/8] Checking PostgreSQL (psql)...
psql --version
echo.
echo [6/8] Checking PostgreSQL Dump (pg_dump)...
pg_dump --version
echo.
echo [7/8] Checking PHP...
php -v | findstr /i "PHP 8."
echo.
echo [8/8] Checking Composer...
composer.bat --version

echo.
echo ==========================================================
echo   All tools checked successfully!
echo ==========================================================
