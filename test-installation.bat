@echo off

if "%INSTALL_DIR%"=="" set INSTALL_DIR=f:\reapertest

set FAILED=0

if "%1"=="skipinstall" goto verify

rem Clean up previous installation
if exist "%INSTALL_DIR%" (
	rmdir /S /Q "%INSTALL_DIR%"
)

rem ------------------------------
rem      Install Reaper Toolbox
rem ------------------------------

rem Find installer
for /f "delims=" %%f in ('dir /b /o-d "%~dp0\release\Reaper-Toolbox-*-x64.exe"') do (
    set INSTALLER=%%f
    goto :found
)
:found

echo Installing "%INSTALLER%"

"%~dp0release\%INSTALLER%" /SILENT /VERYSILENT /SUPPRESSMSGBOXES /SP- /DIR=%INSTALL_DIR%

:verify
rem ------------------------------
rem     Verify installation
rem ------------------------------

rem TEST install directory exists
if exist "%INSTALL_DIR%" (
    echo [OK] Install directory exists
) else (
    echo [FAIL] Install directory does not exist
    set FAILED=1
)

rem TEST for "Reaper"
if exist "%INSTALL_DIR%\reaper.exe" (
    echo [OK] Install successful: reaper.exe found
) else (
    echo [FAIL] Install failed: reaper.exe not found
    set FAILED=1
)

rem TEST for "Reaper User Guide (en)"
if exist "%INSTALL_DIR%\Docs\Reaper_User_Guide.pdf" (
	echo [OK] Install successful: Reaper_User_Guide.pdf found
) else (
	echo [FAIL] Install failed: Reaper_User_Guide.pdf not found
    set FAILED=1
)

rem TEST for "Extension: SWS"
if exist "%INSTALL_DIR%\UserPlugins\reaper_sws-x64.dll" (
    echo [OK] Install successful: SWS extension found
) else (
    echo [FAIL] Install failed: SWS extension not found
    set FAILED=1
)

rem TEST for "Extension: SWS User Guide (en)"
if exist "%INSTALL_DIR%\Docs\Reaper_SWS_User_Guide.pdf" (
	echo [OK] Install successful: Reaper_SWS_User_Guide.pdf found
) else (
	echo [FAIL] Install failed: Reaper_SWS_User_Guide.pdf not found
    set FAILED=1
)

rem TEST "Extension: Reapack" (reaper_reapack-x64.dll)
if exist "%INSTALL_DIR%\UserPlugins\reaper_reapack-x64.dll" (
	echo [OK] Install successful: Reapack extension found
) else (
	echo [FAIL] Install failed: Reapack extension not found
    set FAILED=1
)

rem TEST for "reaper_toolbox_version.txt"
if exist "%INSTALL_DIR%\reaper_toolbox_versions.txt" (
	echo [OK] Install successful: reaper_toolbox_versions.txt file found
) else (
	echo [FAIL] Install failed: reaper_toolbox_versions.txt file not found
    set FAILED=1
)

if %FAILED%==1 (
    echo.
    echo Installation completed with errors. Please review the messages above.
) else (
    echo.
    echo Installation completed successfully.
)

exit /b %FAILED%