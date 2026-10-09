@echo off
title Hentikan Server POS Bengkel (Herd)
color 0E

echo ================================================================
echo           MENGHENTIKAN SERVER POS BENGKEL (HERD)               
echo ================================================================
echo.
echo Menghentikan Cloudflare Tunnel dan proses dev...
taskkill /F /IM cloudflared.exe >nul 2>&1
echo.
echo [OK] Cloudflare Tunnel telah dimatikan.
echo Data transaksi aman tersimpan di database/database.sqlite.
echo.
pause
