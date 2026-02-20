@echo off
echo ===================================================
echo   HVU ADMISSIONS PORTAL DEPLOYMENT SCRIPT (WIN)
echo ===================================================

SET BACKUP_DIR=D:\backups\hvu_ts
SET PROJECT_DIR=D:\xampp\htdocs\TS
SET DATE=%date:~10,4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%
SET DATE=%DATE: =0%

echo [1/4] Creating backup...
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
xcopy /E /I /Y "%PROJECT_DIR%" "%BACKUP_DIR%\backup_%DATE%"

echo [2/4] Pulling latest changes...
REM git pull origin main
echo (Skipped git pull to preserve local changes during development)

echo [3/4] Updating dependencies...
call composer install --no-dev --optimize-autoloader

echo [4/4] Clearing caches...
del /Q "%PROJECT_DIR%\storage\cache\*.php" 2>NUL

echo ===================================================
echo   DEPLOYMENT COMPLETED SUCCESSFULLY!
echo ===================================================
pause
