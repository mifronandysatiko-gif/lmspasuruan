@echo off
title LMS BKPSDM Pasuruan - Worker Antrean Email
color 0A
echo =======================================================================
echo          PORTAL LMS BKPSDM PEMERINTAH KABUPATEN PASURUAN
echo               WORKER ANTREAN PENGIRIMAN EMAIL (GMAIL SMTP)
echo =======================================================================
echo.
echo [STATUS] Worker aktif memantau antrean tabel 'jobs' pada database MySQL.
echo [INFO]   Email akan diproses secara berurutan (FIFO).
echo [INFO]   Jika kuota Google habis (limit harian / burst), sistem otomatis
echo          menunda dan melanjutkan pengiriman begitu kuota siap kembali.
echo.
echo Tekan [Ctrl + C] untuk menghentikan proses antrean ini sewaktu-waktu.
echo =======================================================================
echo.

:loop
php artisan queue:work database --tries=25 --delay=60 --sleep=3 --timeout=60
echo.
echo [INFO] Worker terhenti sejenak, memulai ulang dalam 5 detik...
timeout /t 5 >nul
goto loop
