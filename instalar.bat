@echo off
title FAXEL BI - Instalador de Base de Datos
color 0A
echo.
echo  ===============================================
echo   FAXEL BI -- Instalador de Base de Datos
echo  ===============================================
echo.

set MYSQL=C:\xampp\mysql\bin\mysql.exe
set SCHEMA=d:\SISTEMA_FAXEL\database\schema.sql
set SEED=d:\SISTEMA_FAXEL\database\seed_demo.sql

echo  [1/3] Verificando conexion MySQL...
"%MYSQL%" -u root -e "SELECT 1;" 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo  [ERROR] MySQL no esta corriendo.
    echo  Por favor inicia MySQL en el panel XAMPP y vuelve a ejecutar este archivo.
    echo.
    pause
    exit /b 1
)
echo  [OK] MySQL conectado correctamente.
echo.

echo  [2/3] Importando estructura (schema.sql)...
"%MYSQL%" -u root < "%SCHEMA%"
if %ERRORLEVEL% NEQ 0 (
    echo  [ERROR] Fallo al importar schema.sql
    pause
    exit /b 1
)
echo  [OK] Tablas, vistas y procedimientos creados.
echo.

echo  [3/3] Importando datos demo (seed_demo.sql)...
"%MYSQL%" -u root < "%SEED%"
if %ERRORLEVEL% NEQ 0 (
    echo  [ERROR] Fallo al importar seed_demo.sql
    pause
    exit /b 1
)
echo  [OK] Datos de prueba cargados.
echo.

echo  [VERIFICANDO] Tablas en faxel_bi:
"%MYSQL%" -u root -e "USE faxel_bi; SHOW TABLES;"
echo.
echo  ===============================================
echo   INSTALACION COMPLETADA EXITOSAMENTE
echo  ===============================================
echo.
echo   Accede al sistema en:
echo   http://localhost/SISTEMA_FAXEL/public/login
echo.
echo   Usuario: admin@faxel.pe
echo   Clave:   password
echo.
start http://localhost/SISTEMA_FAXEL/public/login
pause
