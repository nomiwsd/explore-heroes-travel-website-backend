# 🗑️ Advanced Backend Cleanup Script

Write-Host "`n🔍 ANALYZING BACKEND STRUCTURE..." -ForegroundColor Cyan
Write-Host ""

# Files to remove (unnecessary for API-only backend)
$unnecessaryFiles = @(
    ".babelrc",              # Frontend build config
    ".editorconfig",         # Editor config (optional)
    "aaa.zip",              # Random zip file
    "yarn.lock",            # NPM/Yarn lock (no frontend)
    "test.php",             # Test file
    "server.php",           # PHP built-in server (use artisan serve)
    "readme-pro.md",        # Not needed
    "readme.md",            # Not needed (we have BACKEND_README.md)
    "changelog.md"          # Not critical
)

# Modules to remove (Frontend/Admin UI related)
$frontendModules = @(
    "modules\Layout",        # Frontend layouts
    "modules\Theme",         # Theme system
    "modules\Page",          # Static pages (frontend)
    "modules\Popup",         # Popup system (frontend)
    "modules\Template",      # Template system (frontend)
    "modules\Dashboard",     # Admin dashboard UI
    "modules\Email"          # Email templates (keep logic in Core)
)

# Routes to keep check
$routesToCheck = @(
    "routes\admin.php",      # Admin routes (may need cleanup)
    "routes\language.php",   # Language routes (may not need)
    "routes\console.php",    # Console routes (keep)
    "routes\channels.php"    # Broadcast channels (keep if using)
)

Write-Host "❌ REMOVING UNNECESSARY FILES..." -ForegroundColor Red
foreach ($file in $unnecessaryFiles) {
    $fullPath = Join-Path $PSScriptRoot $file
    if (Test-Path $fullPath) {
        Write-Host "  - Removing: $file" -ForegroundColor Yellow
        Remove-Item -Path $fullPath -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`n❌ REMOVING FRONTEND MODULES..." -ForegroundColor Red
foreach ($module in $frontendModules) {
    $fullPath = Join-Path $PSScriptRoot $module
    if (Test-Path $fullPath) {
        Write-Host "  - Removing module: $module" -ForegroundColor Yellow
        Remove-Item -Path $fullPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`n✅ CLEANUP COMPLETED!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 BACKEND-ONLY STRUCTURE:" -ForegroundColor Cyan
Write-Host ""
Write-Host "ESSENTIAL MODULES (Kept):" -ForegroundColor Green
Write-Host "  + Api - REST API endpoints"
Write-Host "  + Booking - Booking management"
Write-Host "  + User - User management"
Write-Host "  + Core - Core functionality"
Write-Host "  + Tour - Tour services"
Write-Host "  + Hotel - Hotel services"
Write-Host "  + Flight - Flight services"
Write-Host "  + Car - Car rental"
Write-Host "  + Boat - Boat rental"
Write-Host "  + Event - Event booking"
Write-Host "  + Space - Space rental"
Write-Host "  + Location - Location management"
Write-Host "  + Media - File uploads"
Write-Host "  + Review - Review system"
Write-Host "  + Coupon - Coupon system"
Write-Host "  + Vendor - Vendor/marketplace"
Write-Host "  + Language - Multi-language"
Write-Host "  + Contact - Contact forms"
Write-Host "  + News - News/blog API"
Write-Host "  + Sms - SMS notifications"
Write-Host "  + Report - Reporting"
Write-Host ""
Write-Host "REMOVED MODULES:" -ForegroundColor Red
Write-Host "  - Layout - Frontend layouts"
Write-Host "  - Theme - Theme system"
Write-Host "  - Page - Static pages"
Write-Host "  - Popup - Popups"
Write-Host "  - Template - Templates"
Write-Host "  - Dashboard - Admin UI"
Write-Host "  - Email - Email templates"
Write-Host ""
