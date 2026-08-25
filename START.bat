@echo off
chcp 65001 >nul
title EduPortal - Khoi dong he thong
set "PHP_EXE=php"
where php >nul 2>&1
if errorlevel 1 (
    if exist "C:\xampp\php\php.exe" (
        set "PHP_EXE=C:\xampp\php\php.exe"
    ) else (
        echo KHONG TIM THAY PHP.
        echo Hay cai PHP hoac them php.exe vao PATH, sau do chay lai START.bat.
        pause
        exit /b 1
    )
)

%PHP_EXE% -r "exit(PHP_VERSION_ID >= 80400 ? 0 : 1);"
if errorlevel 1 (
    echo PHIEN BAN PHP HIEN TAI KHONG TUONG THICH:
    %PHP_EXE% -v
    echo.
    echo Backend hien tai can PHP 8.4 tro len. Hay cai/nang cap PHP roi chay lai.
    pause
    exit /b 1
)

echo ============================================
echo   EduPortal - Khoi dong 3 server
echo ============================================
echo.

echo [1/3] Khoi dong Backend (Laravel - port 8000)...
start "EduPortal - Backend" cmd /k "cd /d %~dp0BE && %PHP_EXE% artisan optimize:clear && %PHP_EXE% artisan serve --host=0.0.0.0 --port=8000"

echo [2/3] Khoi dong WebSocket Reverb (port 8080)...
start "EduPortal - Reverb" cmd /k "cd /d %~dp0BE && %PHP_EXE% artisan reverb:start --host=0.0.0.0 --port=8080"

echo [3/3] Khoi dong Frontend (Vite - port 5173)...
start "EduPortal - Frontend" cmd /k "cd /d %~dp0FE && npm run dev -- --host 0.0.0.0"

echo.
echo Da mo 3 cua so server. Cho ~10 giay roi mo:
echo    Tren may tinh: http://localhost:5173
echo    Tren dien thoai/may khac cung Wi-Fi: http://192.168.1.15:5173
echo.
echo Neu thiet bi khac khong truy cap duoc, hay cho phep PHP va Node.js
echo qua Windows Firewall tren mang Private.
echo.
echo Neu lan dau chay (chua co CSDL): mo thu muc BE va chay:
echo    php artisan migrate:fresh --seed
echo.
pause
