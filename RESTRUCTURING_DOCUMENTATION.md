# Backend Restructuring Documentation

## Overview

The backend has been restructured following Laravel 12.x best practices with a focus on:
1. Modular route organization
2. Separation of public and admin routes  
3. Keeping only modules used by the frontend
4. SMS and Reports modules retained as requested
5. Comprehensive cleanup of unused files, folders, and configurations

---

## Latest Cleanup Summary (Session Update)

### Config Files Removed
The following unused config files were deleted:
- `config/boat.php` - Boat module config (module deleted)
- `config/car.php` - Car module config (module deleted)
- `config/event.php` - Event module config (module deleted)
- `config/flight.php` - Flight module config (module deleted)
- `config/hotel.php` - Hotel module config (module deleted)
- `config/space.php` - Space module config (module deleted)
- `config/booking.php` - Booking module config (module deleted)
- `config/chatify.php` - Chat module config (not used)
- `config/wallet.php` - Wallet config (not used)
- `config/paystack.php` - Paystack payment (not used)
- `config/installer.php` - Installer config (not used)
- `config/mapengine.php` - Map engine config (not used)
- `config/landing.php` - Landing page config (not used)
- `config/post_types.php` - Post types config (not used)
- `config/purifier.php` - HTML Purifier config (not used)
- `config/payment.php` - Generic payment config (not used)

### Unused Helpers Removed
- `app/Helpers/MapEngine.php` - Map engine helper
- `app/Helpers/ReCaptchaEngine.php` - Captcha helper
- `app/Helpers/ProHelper.php` - Pro module helper
- `app/Helpers/Assets.php` - Asset management (not used)
- `custom/Helpers/CustomHelper.php` - Empty file removed

### Unused Controllers Removed
- `app/Http/Controllers/InstallerController.php` - Web installer
- `app/Http/Controllers/LandingpageController.php` - Landing pages

### Unused Middleware Removed
- `RedirectToInstaller.php` - Installer redirect
- `RedirectForMultiLanguage.php` - Multi-language redirect
- `HideDebugbar.php` - Debugbar toggle
- `TranslationManager.php` - Translation middleware
- `CheckForLogPermission.php` - Log permission check
- `RequireChangePassword.php` - Password change requirement

### Unused Folders Removed
- `app/Pro/` - Pro module folder
- `app/Events/` - Pusher events (not used)
- `app/Notifications/` - Email notifications (not used)
- `resources/GeoLite2-City_20210622/` - Outdated 2021 geo database

### Unused Models Removed
- `app/Models/ChFavorite.php` - Chatify favorite
- `app/Models/ChMessage.php` - Chatify messages

### Unused Plugins Removed
- `plugins/PaymentTwoCheckout/` - TwoCheckout payment gateway

### Route Fixes Applied
- **User Routes**: Added `/users` route aliases for frontend compatibility
  - `/module/user/users` -> `/module/user` 
  - `/module/user/users/{id}` -> `/module/user/edit/{id}`
  - `/module/user/users/store` -> `/module/user/store`
- **Media Routes**: Added `POST /module/media/getLists` for frontend compatibility

---

## Route Structure

The `api.php` file has been split into smaller, organized files:

```
routes/
├── api.php                    # Main loader file
├── api/
│   ├── public.php            # Public routes (no auth required)
│   ├── auth.php              # Authentication routes
│   └── admin/
│       ├── tour.php          # Tour management
│       ├── location.php      # Destination management
│       ├── news.php          # Blog/News management
│       ├── page.php          # Page management
│       ├── review.php        # Review management
│       ├── contact.php       # Contact submissions
│       ├── media.php         # Media library
│       ├── language.php      # Language & translations
│       ├── user.php          # User management
│       ├── core.php          # Menu, Settings, SEO
│       ├── report.php        # Reports & Analytics
│       └── sms.php           # SMS notifications
```

---

## Module Status

### Modules KEPT (Used by Frontend)
| Module | Purpose | Frontend Usage |
|--------|---------|----------------|
| Tour | Tour management | Tours listing, details, categories |
| Location | Destinations | Destination pages, tour filters |
| News | Blog/News | Blog listing, articles |
| Page | Static pages | About, Contact pages |
| Review | Customer reviews | Review display, submission |
| Contact | Contact forms | Contact form submissions |
| Media | File uploads | Images, documents |
| Language | Multi-language | Translations, locale |
| User | User management | Admin users |
| Core | Core settings | Menus, Settings, SEO |
| Api | API helpers | API utilities |

### Modules KEPT (As Requested)
| Module | Purpose |
|--------|---------|
| Sms | SMS notifications |
| Report | Reports & Analytics |

### Modules TO REMOVE (Not Used by Frontend)
| Module | Reason for Removal |
|--------|-------------------|
| Boat | Not used in frontend |
| Booking | Not used in frontend |
| Car | Not used in frontend |
| Coupon | Not used in frontend |
| Event | Not used in frontend |
| Flight | Not used in frontend |
| Hotel | Not used in frontend |
| Space | Not used in frontend |
| Vendor | Not used in frontend |

---

## API Endpoints Summary

### Public Endpoints (No Authentication)
```
GET  /api/menus                          # Get all menus
GET  /api/menus/{location}               # Get menu by location
GET  /api/destinations                    # List destinations
GET  /api/destinations/featured          # Featured destinations
GET  /api/destinations/{slug}            # Destination details
GET  /api/tours                          # List tours
GET  /api/tours/featured                 # Featured tours
GET  /api/tours/{slug}                   # Tour details
GET  /api/tours/category/{slug}          # Tours by category
GET  /api/categories                     # Tour categories
GET  /api/settings                       # Site settings
GET  /api/translations/{locale}          # Translations
GET  /api/reviews                        # Public reviews
GET  /api/news                           # Blog listing
GET  /api/news/{slug}                    # Blog article
GET  /api/page/{slug}                    # Static page
POST /api/contact                        # Submit contact form
POST /api/review                         # Submit review
```

### Authentication Endpoints
```
POST /api/admin/login                    # Admin login
POST /api/admin/logout                   # Admin logout
GET  /api/admin/me                       # Current user
GET  /api/admin/dashboard/stats          # Dashboard stats
```

### Admin Endpoints (Authentication Required)
All admin endpoints follow the pattern:
```
GET    /api/module/{module}/             # List items
GET    /api/module/{module}/edit/{id}    # Get single item
POST   /api/module/{module}/store/{id?}  # Create/Update
DELETE /api/module/{module}/{id}         # Delete
POST   /api/module/{module}/bulkEdit     # Bulk operations
```

---

## Setup Instructions

### 1. Backup Current Setup
```bash
# Backup current api.php
cp routes/api.php routes/api.php.backup
```

### 2. Replace api.php
```bash
# Replace with new modular api.php
mv routes/api.php.new routes/api.php
```

### 3. Remove Unused Modules (Optional)
```bash
# Dry run first to see what will be deleted
php cleanup-modules.php --dry-run

# Actually delete unused modules
php cleanup-modules.php
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan optimize
```

### 5. Test API
```bash
# Test health endpoint
curl http://localhost:8000/api/health

# Test info endpoint
curl http://localhost:8000/api/info
```

---

## File Changes Summary

### New Files Created
- `routes/api/public.php` - Public API routes
- `routes/api/auth.php` - Authentication routes
- `routes/api/admin/tour.php` - Tour admin routes
- `routes/api/admin/location.php` - Location admin routes
- `routes/api/admin/news.php` - News admin routes
- `routes/api/admin/page.php` - Page admin routes
- `routes/api/admin/review.php` - Review admin routes
- `routes/api/admin/contact.php` - Contact admin routes
- `routes/api/admin/media.php` - Media admin routes
- `routes/api/admin/language.php` - Language admin routes
- `routes/api/admin/user.php` - User admin routes
- `routes/api/admin/core.php` - Core (menu/settings/seo) routes
- `routes/api/admin/report.php` - Report routes
- `routes/api/admin/sms.php` - SMS routes
- `routes/api.php.new` - New main API router
- `cleanup-modules.php` - Module cleanup script
- `RESTRUCTURING_DOCUMENTATION.md` - This documentation

### Files to Replace
- `routes/api.php` - Replace with `routes/api.php.new`

### Modules to Delete
- `modules/Boat/`
- `modules/Booking/`
- `modules/Car/`
- `modules/Coupon/`
- `modules/Event/`
- `modules/Flight/`
- `modules/Hotel/`
- `modules/Space/`
- `modules/Vendor/`

---

## Laravel 12.x Compliance Notes

1. **Route Organization**: Routes are now organized in a modular structure under `routes/api/`

2. **Middleware**: All admin routes use `auth:sanctum` middleware for API authentication

3. **Rate Limiting**: API rate limiting is configured in `RouteServiceProvider`

4. **Response Format**: All endpoints return consistent JSON responses

5. **Error Handling**: Proper error handling with try-catch blocks

---

## Frontend Integration

The frontend (Next.js) should continue working without changes as all existing API endpoints remain available. The routes have been reorganized but the URLs remain the same.

### Frontend Services Mapping
| Frontend Service | Backend Module |
|-----------------|----------------|
| `tour-service.ts` | Tour module |
| `destination-service.ts` | Location module |
| `news-service.ts` | News module |
| `page-service.ts` | Page module |
| `review-service.ts` | Review module |
| `contact-service.ts` | Contact module |
| `media-service.ts` | Media module |
| `settings-service.ts` | Core module |
| `translation-service.ts` | Language module |

---

## Support

For any issues or questions about the restructuring, refer to:
- Laravel Documentation: https://laravel.com/docs/12.x
- Project README: `BACKEND_README.md`
- API Documentation: `POSTMAN_API_TESTING.md`
