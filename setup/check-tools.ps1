# Environment Verification Script
# powershell -ExecutionPolicy Bypass -File .\setup\check-tools.ps1

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  SecureAuthProject: Tool Verification Check" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# Ensure local custom program directories are in session PATH
$extraPaths = @(
    "$env:LOCALAPPDATA\Programs\PHP",
    "$env:LOCALAPPDATA\Programs\Git\cmd",
    "$env:LOCALAPPDATA\Programs\pgsql\bin",
    "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\axllent.mailpit_Microsoft.Winget.Source_8wekyb3d8bbwe",
    "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\FiloSottile.age_Microsoft.Winget.Source_8wekyb3d8bbwe\age",
    "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Gitleaks.Gitleaks_Microsoft.Winget.Source_8wekyb3d8bbwe",
    "$env:LOCALAPPDATA\Microsoft\WinGet\Links"
)

foreach ($p in $extraPaths) {
    if ((Test-Path $p) -and ($env:Path -notlike "*$p*")) {
        $env:Path = "$p;$env:Path"
    }
}

# Ensure persistent User PATH has all required directories
$userPath = [System.Environment]::GetEnvironmentVariable("Path", "User")
$userList = $userPath -split ";" | Where-Object { $_ -ne "" }
$changed = $false
foreach ($p in $extraPaths) {
    if ((Test-Path $p) -and ($userList -notcontains $p)) {
        $userList += $p
        $changed = $true
    }
}
if ($changed) {
    $newUserPath = ($userList | Select-Object -Unique) -join ";"
    [System.Environment]::SetEnvironmentVariable("Path", $newUserPath, "User")
}

$tools = @(
    @{ Name = "Git"; Cmd = "git --version" },
    @{ Name = "age (Encryption)"; Cmd = "age --version" },
    @{ Name = "Gitleaks (Secret Scanner)"; Cmd = "gitleaks version" },
    @{ Name = "Mailpit (Email Sandbox)"; Cmd = "mailpit version" },
    @{ Name = "PostgreSQL (psql)"; Cmd = "psql --version" },
    @{ Name = "PostgreSQL Dump (pg_dump)"; Cmd = "pg_dump --version" },
    @{ Name = "PHP"; Cmd = "php -v" },
    @{ Name = "Composer"; Cmd = "composer --version" }
)

$allPassed = $true

foreach ($t in $tools) {
    Write-Host "`nChecking $($t.Name)..." -ForegroundColor Yellow
    try {
        $out = Invoke-Expression $t.Cmd 2>&1
        if ($out -and ($out[0] -match "(version|PHP|psql|pg_dump|mailpit|v1\.)")) {
            Write-Host "  [OK] $($out[0])" -ForegroundColor Green
        } else {
            Write-Host "  [MISSING / NOT FOUND]" -ForegroundColor Red
            $allPassed = $false
        }
    } catch {
        Write-Host "  [ERROR: $($_.Exception.Message)]" -ForegroundColor Red
        $allPassed = $false
    }
}

Write-Host "`n==========================================================" -ForegroundColor Cyan
if ($allPassed) {
    Write-Host "  [SUCCESS] All 8 core development tools verified!" -ForegroundColor Green
} else {
    Write-Host "  [WARNING] Some tools need attention." -ForegroundColor Yellow
}
Write-Host "==========================================================" -ForegroundColor Cyan
