@echo off
title OmniShop Local Server
color 0A

echo.
echo  ============================================
echo   OmniShop Local Testing Server
echo   OmniSpace 3D Events Ltd
echo  ============================================
echo.

:: ── Check if XAMPP is installed ─────────────────────────────────────
set XAMPP_PATH=C:\xampp
if not exist "%XAMPP_PATH%\xampp-control.exe" (
    echo  XAMPP is not installed yet.
    echo.
    echo  Please install XAMPP first:
    echo  1. Go to: https://www.apachefriends.org/download.html
    echo  2. Download the Windows version
    echo  3. Run the installer (accept all defaults)
    echo  4. Come back and double-click this file again
    echo.
    echo  Opening the XAMPP download page now...
    start "" "https://www.apachefriends.org/download.html"
    pause
    exit /b
)

:: ── Check if OmniShop files are in the right place ───────────────────
set OMNI_PATH=%XAMPP_PATH%\htdocs\omnishop
if not exist "%OMNI_PATH%\index.php" (
    echo  OmniShop files not found at:
    echo  %OMNI_PATH%
    echo.
    echo  Please copy the "OmniShop PHP" folder contents into:
    echo  C:\xampp\htdocs\omnishop\
    echo.
    echo  (So that index.php is at C:\xampp\htdocs\omnishop\index.php)
    echo.
    pause
    exit /b
)

:: ── Start Apache ─────────────────────────────────────────────────────
echo  Starting Apache web server...
net start Apache2.4 >nul 2>&1
if errorlevel 1 (
    "%XAMPP_PATH%\apache\bin\httpd.exe" -k start >nul 2>&1
)
timeout /t 2 /nobreak >nul

:: ── Start MySQL ───────────────────────────────────────────────────────
echo  Starting MySQL database...
net start MySQL >nul 2>&1
if errorlevel 1 (
    "%XAMPP_PATH%\mysql\bin\mysqld.exe" --standalone >nul 2>&1
)
timeout /t 2 /nobreak >nul

:: ── Open browser ──────────────────────────────────────────────────────
echo.
echo  Server started! Opening OmniShop in your browser...
echo.
echo  URLs:
echo    Solar and Storage:  http://localhost/omnishop/solarandstorage
echo    GITEX Kenya:        http://localhost/omnishop/gitex
echo    Admin Panel:        http://localhost/omnishop/admin
echo.
echo  Leave this window open while using OmniShop.
echo  Close it when you are done.
echo.

start "" "http://localhost/omnishop/admin"

pause
