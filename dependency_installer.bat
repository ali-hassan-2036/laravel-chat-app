@echo off
setlocal

:: Ensure script runs from the script directory
pushd "%~dp0"

echo Installing Laravel project dependencies...

:: Copy .env.example to .env if .env doesn't exist
if not exist ".env" (
    if exist ".env.example" (
        echo Copying .env.example to .env...
        copy /Y ".env.example" ".env" >nul
        if errorlevel 1 (
            echo Failed to copy .env.example
            popd
            exit /b 1
        )
    ) else (
        echo .env.example not found.
        popd
        exit /b 1
    )
) else (
    echo .env already exists. Skipping copy.
)

:: Composer install
echo Running composer install --no-interaction...
where composer >nul 2>&1
if errorlevel 1 (
    echo Composer not found in PATH.
    popd
    exit /b 1
)
call composer install --no-interaction
set "COMPOSER_EXIT=%ERRORLEVEL%"
echo Composer exit code: %COMPOSER_EXIT%
if %COMPOSER_EXIT% neq 0 (
    echo Composer install failed.
    popd
    exit /b %COMPOSER_EXIT%
)

:: NPM install
echo Running npm i...
where npm >nul 2>&1
if errorlevel 1 (
    echo npm not found in PATH.
    popd
    exit /b 1
)
call npm i -f
set "NPM_EXIT=%ERRORLEVEL%"
echo npm exit code: %NPM_EXIT%
if %NPM_EXIT% neq 0 (
    echo npm install failed.
    popd
    exit /b %NPM_EXIT%
)

echo All done.
popd
exit /b 0