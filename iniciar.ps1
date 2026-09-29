$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar la base de datos.' }
Write-Host 'Abre http://127.0.0.1:8000. Para detener, presiona Ctrl+C.'
Push-Location public
try {
    php -d upload_max_filesize=10M -d post_max_size=12M -S 127.0.0.1:8000 -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
} finally {
    Pop-Location
}
