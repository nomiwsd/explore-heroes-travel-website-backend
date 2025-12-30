# 🗑️ Frontend Cleanup Script

# FOLDERS TO REMOVE (Frontend-related)
$foldersToRemove = @(
    "public\css",
    "public\js",
    "public\sass",
    "public\fonts",
    "public\images",
    "public\themes",
    "public\sounds",
    "public\dist",
    "public\libs",
    "public\module",
    "public\plugins",
    "public\custom",
    "public\installer",
    "resources\views",
    "resources\sass",
    "resources\css",
    "resources\ckeditor",
    "resources\ckeditor4",
    "resources\admin",
    "themes",
    "node_modules"
)

# FILES TO REMOVE (Frontend-related)
$filesToRemove = @(
    "package.json",
    "package-lock.json",
    "webpack.mix.js",
    "gulpfile.js",
    "vite.config.js",
    "public\mix-manifest.json",
    "public\favicon.ico"
)

Write-Host "🚀 Starting Frontend Cleanup..." -ForegroundColor Yellow
Write-Host ""

# Remove folders
foreach ($folder in $foldersToRemove) {
    $fullPath = Join-Path $PSScriptRoot $folder
    if (Test-Path $fullPath) {
        Write-Host "❌ Removing folder: $folder" -ForegroundColor Red
        Remove-Item -Path $fullPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Remove files
foreach ($file in $filesToRemove) {
    $fullPath = Join-Path $PSScriptRoot $file
    if (Test-Path $fullPath) {
        Write-Host "❌ Removing file: $file" -ForegroundColor Red
        Remove-Item -Path $fullPath -Force -ErrorAction SilentlyContinue
    }
}

Write-Host ""
Write-Host "Frontend cleanup completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Remaining structure (Backend only):" -ForegroundColor Cyan
Write-Host "  - app/ - Application logic"
Write-Host "  - modules/ - Modular backend"
Write-Host "  - database/ - Migrations and seeders"
Write-Host "  - routes/ - API and web routes"
Write-Host "  - config/ - Configuration"
Write-Host "  - storage/ - File storage"
Write-Host "  - bootstrap/ - App bootstrap"
Write-Host "  - public/index.php - Entry point"
Write-Host ""
