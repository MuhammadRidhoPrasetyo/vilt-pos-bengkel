@echo off
title Hentikan Server POS Bengkel
color 0E

echo ================================================================
echo           MENGHENTIKAN SERVER POS BENGKEL                       
echo ================================================================
echo.
echo Sedang mematikan seluruh container Docker...
docker compose down
echo.
echo [OK] Seluruh container (PHP, Nginx, Queue, Tunnel) telah dihentikan.
echo Data transaksi aman tersimpan di database/database.sqlite.
echo.
pause
