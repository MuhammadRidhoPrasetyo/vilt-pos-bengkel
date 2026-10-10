@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"
title POS Bengkel (Herd Native) + Cloudflare Tunnel
color 0B

echo ================================================================
echo      SISTEM POS BENGKEL - HERD NATIVE + CLOUDFLARE TUNNEL        
echo ================================================================
echo.

:: 1. Memeriksa ketersediaan PHP dari Laravel Herd
echo [1/5] Memeriksa PHP dan Laravel Herd...
where php >nul 2>&1
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP tidak ditemukan di PATH sistem.
    echo         Pastikan aplikasi Laravel Herd sudah berjalan di Windows.
    echo.
    pause
    exit /b 1
)
echo [OK] PHP aktif.

if not exist "storage\app\backups" mkdir "storage\app\backups" >nul 2>&1
if not exist "storage\app\temp" mkdir "storage\app\temp" >nul 2>&1

:: 2. Memeriksa Cloudflared
echo [2/5] Memeriksa Cloudflare Tunnel (cloudflared)...
where cloudflared >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\Program Files (x86)\cloudflared\cloudflared.exe" (
        set "PATH=%PATH%;C:\Program Files (x86)\cloudflared"
    ) else if exist "C:\Program Files\cloudflared\cloudflared.exe" (
        set "PATH=%PATH%;C:\Program Files\cloudflared"
    )
)

where cloudflared >nul 2>&1
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] cloudflared.exe tidak ditemukan di sistem!
    echo         Silakan pastikan cloudflared terpasang di PC Anda.
    echo.
    pause
    exit /b 1
)
echo [OK] Cloudflared siap.

:: Menghentikan container Docker jika sebelumnya berjalan agar port tidak bentrok
docker compose --profile frankenphp --profile fpm stop >nul 2>&1

:: 3. Memeriksa Konfigurasi .env dan Database SQLite
echo [3/5] Memeriksa konfigurasi (.env dan SQLite)...
if not exist ".env" (
    echo [*] File .env belum ada. Menyalin dari .env.example...
    if exist ".env.example" (
        copy .env.example .env >nul
    ) else (
        copy .env.docker.example .env >nul
    )
)

findstr /C:"APP_KEY=base64:" .env >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] Mengenerate APP_KEY baru...
    call php artisan key:generate --force
)

:: Pastikan konfigurasi Reverb ada di .env
findstr /C:"REVERB_APP_KEY" .env >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] Menambahkan konfigurasi Laravel Reverb ke .env...
    (
        echo.
        echo BROADCAST_CONNECTION=reverb
        echo REVERB_APP_ID=751073
        echo REVERB_APP_KEY=a2kz89zdynwffpznlejr
        echo REVERB_APP_SECRET=4ozv52uqxhod7tykme8y
        echo REVERB_HOST="localhost"
        echo REVERB_PORT=8080
        echo REVERB_SCHEME=http
        echo REVERB_INTERNAL_HOST="localhost"
        echo REVERB_INTERNAL_PORT=8080
        echo VITE_REVERB_APP_KEY="a2kz89zdynwffpznlejr"
        echo VITE_REVERB_HOST="localhost"
        echo VITE_REVERB_PORT="8080"
        echo VITE_REVERB_SCHEME="http"
    ) >> .env
)

set "IS_NEW_DB=0"
if not exist "database" mkdir database
if not exist "database\database.sqlite" (
    echo [*] Membuat database SQLite baru...
    type nul > database\database.sqlite
    set "IS_NEW_DB=1"
)
for %%F in ("database\database.sqlite") do if %%~zF equ 0 set "IS_NEW_DB=1"

if "!IS_NEW_DB!"=="1" (
    echo [*] Menjalankan migrasi database baru dan seeder...
    call php artisan migrate --force --seed --seeder=DatabaseSeeder
) else (
    echo [*] Memastikan migrasi database terpasang...
    call php artisan migrate --force
)
echo [OK] Database SQLite siap.

:: 4. Memeriksa dependensi dan build frontend
echo [4/5] Memeriksa dependensi dan aset frontend...
if exist "public\hot" del /f /q "public\hot" >nul 2>&1
if not exist "node_modules" (
    echo [*] Menginstal dependensi npm...
    call npm install
)
if not exist "public\build" (
    echo [*] Mengompilasi aset frontend via npm run build...
    call npm run build
)
echo [OK] Dependensi dan aset frontend siap.
echo.

:: 5. Menjalankan Cloudflare Tunnel di Background
echo [5/5] Menghubungkan ke Cloudflare Tunnel...
set "CF_LOG=%TEMP%\pos_bengkel_tunnel.log"
del /f /q "!CF_LOG!" >nul 2>&1

:: Hentikan instance cloudflared sebelumnya jika masih ada
taskkill /F /IM cloudflared.exe >nul 2>&1

:: Jalankan cloudflared mengarah ke port 8000
start /b "" cloudflared tunnel --url http://127.0.0.1:8000 --logfile "!CF_LOG!"

echo      (Mohon tunggu 5-10 detik untuk mengambil link publik...)

for /f "delims=" %%I in ('powershell -NoProfile -Command "$log = Join-Path $env:TEMP 'pos_bengkel_tunnel.log'; $url = ''; for ($i=0; $i -lt 30; $i++) { if (Test-Path $log) { $line = (Get-Content $log -Tail 50 2>&1) -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com' | Select-Object -Last 1; if ($line -and $line -match '(https://[a-zA-Z0-9-]+\.trycloudflare\.com)') { $url = $matches[1]; break } }; Start-Sleep -Seconds 1 }; if ($url) { Write-Output $url } else { Write-Output 'TIMEOUT' }"') do (
    set "TUNNEL_URL=%%I"
)

cls
color 0A
echo ================================================================
echo           SISTEM POS BENGKEL BERHASIL DIJALANKAN!               
echo ================================================================
echo.
echo   [+] MODE RUNNER   : Native Windows (Laravel Herd + SQLite)
echo   [+] PERFORMA      : Super Cepat (di bawah 20ms, tanpa overhead Docker)
echo.
echo   [+] AKSES DARI BENGKEL CABANG LAIN (INTERNET / HP / LAPTOP):
if "!TUNNEL_URL!"=="TIMEOUT" (
    echo       [!] Link otomatis belum terbaca atau timeout.
    echo           Silakan cek log tunnel di: !CF_LOG!
) else (
    echo       LINK: !TUNNEL_URL!
    echo.
    echo       Link sudah otomatis disalin ke Clipboard!
    echo       Tinggal Paste / Ctrl+V ke WhatsApp bengkel cabang
    <nul set /p="!TUNNEL_URL!" | clip 2>nul
)
echo.
echo   [+] AKSES LOKAL (DARI LAPTOP SERVER INI):
echo       LINK 1: http://localhost:8000
echo       LINK 2: http://vilt-pos-bengkel.test
echo.
echo ================================================================
echo.
echo [*] Menjalankan server (Server, Reverb, Queue)...
echo [*] Tekan Ctrl+C untuk menghentikan server ini kapan saja.
echo.

if exist "public\hot" del /f /q "public\hot" >nul 2>&1
call php artisan dev

:: Cleanup saat server berhenti
echo.
echo Menghentikan Cloudflare Tunnel...
taskkill /F /IM cloudflared.exe >nul 2>&1
echo [OK] Server telah dihentikan.
pause
