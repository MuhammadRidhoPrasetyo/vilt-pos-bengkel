@echo off
setlocal enabledelayedexpansion
title POS Bengkel Server - Cloudflare Tunnel
color 0B

echo ================================================================
echo           SISTEM POS BENGKEL - SERVER LOCAL & TUNNEL            
echo ================================================================
echo.

:: 1. Cek apakah Docker Desktop sedang berjalan
echo [1/5] Memeriksa koneksi Docker...
docker info >nul 2>&1
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] Docker Desktop belum berjalan atau belum siap!
    echo         Silakan buka aplikasi Docker Desktop di Windows Anda,
    echo         tunggu hingga icon Docker berwarna hijau/running,
    echo         lalu klik dua kali file start.bat ini kembali.
    echo.
    pause
    exit /b 1
)
echo [OK] Docker Desktop aktif.
echo.

:: 2. Cek file .env dan database sqlite
echo [2/5] Memeriksa konfigurasi awal (.env & database)...
if not exist ".env" (
    echo [*] File .env tidak ditemukan. Membuat dari .env.docker.example...
    copy .env.docker.example .env >nul
)

if not exist "database" mkdir database
if not exist "database\database.sqlite" (
    echo [*] Membuat file database SQLite baru...
    type nul > database\database.sqlite
)
echo [OK] Konfigurasi siap.
echo.

:: 3. Menjalankan Docker Compose
echo [3/5] Menyalakan container (PHP, Nginx, Queue, Reverb, Tunnel)...
docker compose up -d

if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] Gagal menjalankan docker compose.
    pause
    exit /b 1
)
echo [OK] Container berhasil dinyalakan.
echo.

:: 4. Cek apakah perlu inisialisasi awal (vendor / app key / migration)
echo [4/5] Memeriksa dependensi aplikasi...
if not exist "vendor" (
    echo [*] Menjalankan composer install di dalam container (pertama kali saja)...
    docker compose exec -T app composer install --optimize-autoloader
)

findstr /C:"APP_KEY=base64:" .env >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] Mengenerate APP_KEY baru...
    docker compose exec -T app php artisan key:generate --force
)

echo [*] Memastikan migrasi database terpasang...
docker compose exec -T app php artisan migrate --force

if not exist "public\build" (
    echo [*] Mengompilasi aset frontend (npm run build)...
    docker compose run --rm node npm install
    docker compose run --rm node npm run build
)
echo [OK] Aplikasi siap digunakan.
echo.

:: 5. Mengambil Link Cloudflare Tunnel
echo [5/5] Menghubungkan ke Cloudflare Tunnel...
echo      (Mohon tunggu 5-10 detik untuk mendapatkan link publik...)

for /f "delims=" %%I in ('powershell -NoProfile -Command "$url = ''; for ($i=0; $i -lt 30; $i++) { $logs = docker compose logs tunnel 2>&1; if ($logs -match '(https://[a-zA-Z0-9-]+\.trycloudflare\.com)') { $url = $matches[1]; break }; Start-Sleep -Seconds 1 }; if ($url) { Write-Output $url } else { Write-Output 'TIMEOUT' }"') do (
    set "TUNNEL_URL=%%I"
)

cls
color 0A
echo ================================================================
echo           SISTEM POS BENGKEL BERHASIL DIJALANKAN!               
echo ================================================================
echo.
echo   [+] AKSES DARI BENGKEL CABANG LAIN (INTERNET / HP / LAPTOP):
if "!TUNNEL_URL!"=="TIMEOUT" (
    echo       [!] Link otomatis belum terbaca atau menggunakan domain token custom.
    echo           Silakan cek log dengan perintah: docker compose logs tunnel
) else (
    echo       LINK: !TUNNEL_URL!
    echo.
    echo       (Link sudah otomatis disalin ke Clipboard!
    echo        Tinggal Paste / Ctrl+V ke WhatsApp bengkel cabang)
    echo !TUNNEL_URL! | clip
)
echo.
echo   [+] AKSES LOKAL (DARI LAPTOP SERVER INI):
echo       LINK: http://localhost:8000
echo.
echo ================================================================
echo.
echo  PILIHAN MENU:
echo    [1] Buka aplikasi di Browser sekarang
echo    [2] Lihat log Cloudflare Tunnel
echo    [3] Hentikan Server (Stop Docker)
echo    [4] Minimize / Tutup jendela ini (server tetap berjalan)
echo.
:MENU
set /p MENU_CHOICE="Pilih opsi [1/2/3/4]: "

if "%MENU_CHOICE%"=="1" (
    if not "!TUNNEL_URL!"=="TIMEOUT" (
        start !TUNNEL_URL!
    ) else (
        start http://localhost:8000
    )
    goto MENU
)
if "%MENU_CHOICE%"=="2" (
    docker compose logs -f tunnel
    goto MENU
)
if "%MENU_CHOICE%"=="3" (
    echo Mematikan server...
    docker compose down
    echo Server berhasil dimatikan.
    pause
    exit /b 0
)
if "%MENU_CHOICE%"=="4" (
    exit /b 0
)

echo Pilihan tidak valid.
goto MENU
