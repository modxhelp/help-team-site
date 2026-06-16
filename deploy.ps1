$ErrorActionPreference = "Stop"

$SshHost = "wm48mav4_helptm@wm48mav4.beget.tech"

$RemoteSiteRoot = "/home/w/wm48mav4/wm48mav4.beget.tech"
$RemoteAppRoot = "$RemoteSiteRoot/help-team-site"
$RemoteDocRoot = "$RemoteSiteRoot/public_html"

Write-Host "== Help Team deploy ==" -ForegroundColor Cyan

Write-Host "Creating remote directories..." -ForegroundColor Yellow
ssh $SshHost "mkdir -p '$RemoteAppRoot' '$RemoteDocRoot' '$RemoteDocRoot/assets' '$RemoteDocRoot/uploads/dogs' '$RemoteAppRoot/storage/logs' '$RemoteAppRoot/storage/cache'"

Write-Host "Uploading public files..." -ForegroundColor Yellow

scp -r `
  ".\public_html\index.php" `
  ".\public_html\.htaccess" `
  ".\public_html\assets" `
  "$SshHost`:$RemoteDocRoot/"

Write-Host "Uploading app files..." -ForegroundColor Yellow

scp -r `
  ".\app" `
  ".\bootstrap" `
  ".\config" `
  ".\database" `
  ".\resources" `
  ".\storage" `
  ".\composer.json" `
  "$SshHost`:$RemoteAppRoot/"

if (Test-Path ".\composer.lock") {
    scp ".\composer.lock" "$SshHost`:$RemoteAppRoot/"
}

if (Test-Path ".\.env.example") {
    scp ".\.env.example" "$SshHost`:$RemoteAppRoot/"
}

Write-Host "Installing Composer dependencies on remote..." -ForegroundColor Yellow
ssh $SshHost "cd '$RemoteAppRoot' && /usr/local/bin/php8.5 /home/w/wm48mav4/.local/bin/composer install --no-dev --optimize-autoloader"

Write-Host "Checking PHP syntax..." -ForegroundColor Yellow
ssh $SshHost "find '$RemoteAppRoot' '$RemoteDocRoot' -name '*.php' -print0 | xargs -0 -n1 /usr/local/bin/php8.5 -l"

Write-Host "Deploy finished." -ForegroundColor Green