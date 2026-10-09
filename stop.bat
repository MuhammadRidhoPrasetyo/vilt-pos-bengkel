@echo off
title Hentikan Server POS Bengkel
color 0E

echo ================================================================
echo           MENGHENTIKAN SERVER POS BENGKEL                       
echo ================================================================
echo.
echo Sedang mematikan seluruh container Docker...
docker compose --profile frankenphp --profile fpm down
echo.
echo [OK] Seluruh container (FrankenPHP, PHP-FPM, Nginx, Queue, Tunnel) telah dihentikan.
echo Data transaksi aman tersimpan di database/database.sqlite.
echo.
pause
