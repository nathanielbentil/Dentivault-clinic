# Automated Environment Setup Script
# Run this in an Administrator or normal PowerShell window:
# powershell -ExecutionPolicy Bypass -File .\setup\install-tools.ps1

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  SecureAuthProject: Installing Developer & Security Tools" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Git
Write-Host "`n[1/6] Installing Git..." -ForegroundColor Yellow
winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements

# 2. age (Encryption tool)
Write-Host "`n[2/6] Installing age (Modern Encryption Tool)..." -ForegroundColor Yellow
winget install --id FiloSottile.age -e --source winget --accept-package-agreements --accept-source-agreements

# 3. gitleaks (Secret leak scanner)
Write-Host "`n[3/6] Installing Gitleaks (Secret Scanner)..." -ForegroundColor Yellow
winget install --id Gitleaks.Gitleaks -e --source winget --accept-package-agreements --accept-source-agreements

# 4. Mailpit (Email testing)
Write-Host "`n[4/6] Installing Mailpit (Local Email Testing Sandbox)..." -ForegroundColor Yellow
winget install --id Axllent.Mailpit -e --source winget --accept-package-agreements --accept-source-agreements

# 5. PostgreSQL 17 (Local DB & pg_dump tools)
Write-Host "`n[5/6] Installing PostgreSQL 17..." -ForegroundColor Yellow
winget install --id PostgreSQL.PostgreSQL.17 -e --source winget --accept-package-agreements --accept-source-agreements

# 6. Laravel Herd
Write-Host "`n[6/6] Installing Laravel Herd (PHP 8.3, Composer, nginx, SSL)..." -ForegroundColor Yellow
winget install --id BeyondCode.Herd -e --source winget --accept-package-agreements --accept-source-agreements

Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host "  Installation commands dispatched! Run check-tools.ps1 next." -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
