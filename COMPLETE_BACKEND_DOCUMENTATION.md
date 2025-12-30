# 📚 COMPLETE BACKEND DOCUMENTATION - Laravel Booking Core

## 🎯 Project Overview

**Project Name:** Booking Core API (Backend Only)  
**Type:** Travel & Booking Management System  
**Framework:** Laravel 10.x  
**PHP Version:** 8.1+  
**Purpose:** Complete REST API for multi-service booking platform  
**Architecture:** Modular Backend with API-first design

---

## 📁 COMPLETE PROJECT STRUCTURE EXPLAINED

### 🏗️ ROOT DIRECTORY FILES

```
public_html/
├── .editorconfig          # Editor formatting rules (optional)
├── .env                   # ✅ IMPORTANT: Environment configuration
├── .env.example           # ✅ Environment template
├── .gitattributes         # Git attributes
├── .gitignore             # Git ignore rules
├── .htaccess              # ✅ Apache configuration
├── artisan                # ✅ Laravel command-line tool
├── composer.json          # ✅ PHP dependencies definition
├── composer.lock          # ✅ PHP dependencies lock
├── phpunit.xml            # Testing configuration
├── BACKEND_README.md      # Backend guide
├── SETUP_GUIDE.md         # Setup instructions
├── NEXTJS_INTEGRATION.md  # Frontend integration guide
└── CLEANUP_SUMMARY.md     # Cleanup history
```

#### 📝 Important Files Explained:

1. **`.env`** - Main configuration file
   - Database credentials
   - App settings
   - API keys
   - Mail settings
   - Payment gateway keys

2. **`artisan`** - Laravel CLI
   ```bash
   php artisan serve          # Start server
   php artisan migrate        # Run migrations
   php artisan make:model     # Create model
   ```

3. **`composer.json`** - PHP packages
   - Laravel framework
   - Payment gateways (Stripe, PayPal, etc.)
   - Image processing
   - JWT authentication
   - Database tools

---

### 📂 CORE DIRECTORIES

#### 1. **`app/`** - Core Application Code

```
app/
├── BaseModel.php              # Base model for all models
├── Currency.php               # Currency model
├── User.php                   # ✅ User model (authentication)
├── UserMeta.php               # User metadata
├── Http/
│   ├── Controllers/           # ✅ Main controllers
│   │   ├── Controller.php    # Base controller
│   │   └── ...               # Other controllers
│   ├── Middleware/            # ✅ Request middleware
│   │   ├── Authenticate.php  # Auth middleware
│   │   └── ...               # Custom middleware
│   └── Requests/              # Form validation requests
├── Models/                    # ✅ Application models
├── Helpers/                   # ✅ Helper functions
│   ├── AppHelper.php         # Main helper
│   └── ProHelper.php         # Pro version helpers
├── Providers/                 # ✅ Service providers
│   ├── AppServiceProvider.php
│   └── RouteServiceProvider.php
├── Console/                   # Console commands
├── Exceptions/                # Exception handlers
├── Events/                    # Event classes
├── Notifications/             # Notification classes
├── Rules/                     # Validation rules
├── Scope/                     # Query scopes
└── Traits/                    # Reusable traits
```

**Purpose:**
- Core business logic
- Base models and controllers
- Authentication and authorization
- Helper functions
- Service providers

---

#### 2. **`modules/`** - Modular Backend Services ✅ MOST IMPORTANT

```
modules/
├── AdminController.php         # Base admin controller
├── FrontendController.php      # Base frontend controller
├── ModuleServiceProvider.php   # Module service provider
├── ServiceProvider.php         # Module loader
├── Api/                       # ✅✅✅ REST API MODULE (MAIN)
│   ├── Controllers/           # API controllers
│   │   ├── AuthController.php       # Login/Register/Logout
│   │   ├── BookingController.php    # Booking API
│   │   ├── SearchController.php     # Search services
│   │   ├── ReviewController.php     # Reviews
│   │   ├── UserController.php       # User management
│   │   ├── LocationController.php   # Locations
│   │   ├── NewsController.php       # News/Blog
│   │   └── MediaController.php      # File uploads
│   ├── Routes/
│   │   └── api.php           # ✅ ALL API ROUTES
│   └── ModuleProvider.php
│
├── Booking/                   # ✅ Booking Management
│   ├── Models/
│   │   ├── Booking.php       # Booking model
│   │   ├── BookingMeta.php   # Booking metadata
│   │   └── Payment.php       # Payment records
│   ├── Controllers/           # Booking controllers
│   └── Routes/                # Booking routes
│
├── User/                      # ✅ User Management
│   ├── Models/
│   │   ├── User.php          # User model
│   │   ├── Role.php          # User roles
│   │   └── Permission.php    # Permissions
│   ├── Controllers/           # User controllers
│   └── Routes/                # User routes
│
├── Core/                      # ✅ Core System
│   ├── Models/
│   │   ├── Settings.php      # System settings
│   │   ├── Translation.php   # Translations
│   │   └── Menu.php          # Menu system
│   ├── Controllers/           # Core controllers
│   └── Helpers/               # Core helpers
│
├── Tour/                      # ✅ Tour Booking Service
│   ├── Models/
│   │   ├── Tour.php          # Tour model
│   │   ├── TourCategory.php  # Categories
│   │   └── TourDate.php      # Tour dates
│   ├── Controllers/           # Tour controllers
│   └── Routes/                # Tour routes
│
├── Hotel/                     # ✅ Hotel Booking Service
│   ├── Models/
│   │   ├── Hotel.php         # Hotel model
│   │   ├── HotelRoom.php     # Rooms
│   │   └── HotelDate.php     # Availability
│   ├── Controllers/           # Hotel controllers
│   └── Routes/                # Hotel routes
│
├── Flight/                    # ✅ Flight Booking Service
│   ├── Models/
│   │   ├── Flight.php        # Flight model
│   │   ├── FlightSeat.php    # Seats
│   │   └── Airport.php       # Airports
│   ├── Controllers/           # Flight controllers
│   └── Routes/                # Flight routes
│
├── Car/                       # ✅ Car Rental Service
│   ├── Models/
│   │   ├── Car.php           # Car model
│   │   └── CarDate.php       # Availability
│   ├── Controllers/           # Car controllers
│   └── Routes/                # Car routes
│
├── Boat/                      # ✅ Boat Rental Service
│   ├── Models/
│   │   ├── Boat.php          # Boat model
│   │   └── BoatDate.php      # Availability
│   ├── Controllers/           # Boat controllers
│   └── Routes/                # Boat routes
│
├── Event/                     # ✅ Event Booking Service
│   ├── Models/
│   │   ├── Event.php         # Event model
│   │   └── EventDate.php     # Event dates
│   ├── Controllers/           # Event controllers
│   └── Routes/                # Event routes
│
├── Space/                     # ✅ Space Rental Service
│   ├── Models/
│   │   ├── Space.php         # Space model
│   │   └── SpaceDate.php     # Availability
│   ├── Controllers/           # Space controllers
│   └── Routes/                # Space routes
│
├── Location/                  # ✅ Location Management
│   ├── Models/
│   │   └── Location.php      # Location model
│   ├── Controllers/           # Location controllers
│   └── Routes/                # Location routes
│
├── Media/                     # ✅ File Upload/Management
│   ├── Models/
│   │   └── MediaFile.php     # Media model
│   ├── Controllers/           # Media controllers
│   └── Helpers/               # Upload helpers
│
├── Review/                    # ✅ Review System
│   ├── Models/
│   │   ├── Review.php        # Review model
│   │   └── ReviewMeta.php    # Review metadata
│   └── Controllers/           # Review controllers
│
├── Coupon/                    # ✅ Coupon/Discount System
│   ├── Models/
│   │   └── Coupon.php        # Coupon model
│   └── Controllers/           # Coupon controllers
│
├── Vendor/                    # ✅ Vendor/Marketplace
│   ├── Models/
│   │   ├── VendorPlan.php    # Vendor plans
│   │   └── VendorPayout.php  # Payouts
│   └── Controllers/           # Vendor controllers
│
├── Language/                  # ✅ Multi-language Support
│   ├── Models/
│   │   ├── Language.php      # Language model
│   │   └── Translation.php   # Translations
│   └── Controllers/           # Language controllers
│
├── Contact/                   # ✅ Contact Forms
│   ├── Models/
│   │   └── Contact.php       # Contact model
│   └── Controllers/           # Contact controllers
│
├── News/                      # ✅ News/Blog API
│   ├── Models/
│   │   ├── News.php          # News model
│   │   └── NewsCategory.php  # Categories
│   └── Controllers/           # News controllers
│
├── Sms/                       # ✅ SMS Notifications
│   └── Controllers/           # SMS controllers
│
└── Report/                    # ✅ Reporting System
    └── Controllers/           # Report controllers
```

**Module Purpose Summary:**

| Module | Purpose | API Endpoints |
|--------|---------|---------------|
| **Api** | REST API endpoints | ✅ Main entry point |
| **Booking** | Booking management | Cart, Checkout, Payment |
| **User** | User management | Profile, Auth |
| **Tour** | Tour services | Search, Detail, Book |
| **Hotel** | Hotel services | Search, Rooms, Book |
| **Flight** | Flight services | Search, Seats, Book |
| **Car** | Car rental | Search, Book |
| **Boat** | Boat rental | Search, Book |
| **Event** | Event booking | Search, Book |
| **Space** | Space rental | Search, Book |
| **Location** | Locations | Search, Detail |
| **Media** | File uploads | Upload, Manage |
| **Review** | Reviews | Write, Read |
| **Coupon** | Discounts | Apply, Validate |
| **Vendor** | Marketplace | Vendor management |
| **Language** | i18n | Translations |
| **Contact** | Contact forms | Submit |
| **News** | Blog/News | List, Detail |
| **Sms** | SMS | Send notifications |
| **Report** | Analytics | Reports |

---

#### 3. **`database/`** - Database Structure

```
database/
├── migrations/                 # ✅ Database schema (35+ files)
│   ├── 2014_10_12_000000_create_users_table.php
│   ├── 2019_05_17_113042_create_tour_attrs_table.php
│   ├── 2021_03_19_102157_update_core_190.php
│   └── ... (35+ migration files)
├── seeders/                   # ✅ Sample data
│   └── DatabaseSeeder.php
└── factories/                 # Model factories
    └── UserFactory.php
```

**Purpose:**
- Database schema definitions
- Sample data for testing
- Database versioning

**Important Tables:**
- `users` - User accounts
- `bravo_tours` - Tours
- `bravo_hotels` - Hotels
- `bravo_flights` - Flights
- `bravo_cars` - Cars
- `bravo_boats` - Boats
- `bravo_events` - Events
- `bravo_spaces` - Spaces
- `bravo_bookings` - Bookings
- `bravo_reviews` - Reviews
- `core_locations` - Locations
- `media_files` - Uploaded files

---

#### 4. **`routes/`** - Route Definitions

```
routes/
├── api.php                    # ✅ Main API routes (minimal)
├── web.php                    # ✅ Web routes (API info only)
├── admin.php                  # Admin routes
├── channels.php               # Broadcast channels
├── console.php                # Console routes
└── language.php               # Language routes
```

**Purpose:**
- Define all HTTP routes
- API endpoint mapping
- Route middleware

**Important:**
- Main API routes are in `modules/Api/Routes/api.php`
- `web.php` only shows API info (no frontend)
- Module routes are in respective module folders

---

#### 5. **`config/`** - Configuration Files (40+ files)

```
config/
├── app.php                    # ✅ Main app config
├── auth.php                   # ✅ Authentication config
├── database.php               # ✅ Database config
├── cors.php                   # ✅ CORS settings
├── mail.php                   # Email config
├── filesystems.php            # Storage config
├── services.php               # Third-party services
├── jwt.php                    # JWT authentication
├── payment.php                # Payment gateways
├── booking.php                # Booking settings
├── tour.php                   # Tour config
├── hotel.php                  # Hotel config
├── flight.php                 # Flight config
├── car.php                    # Car config
├── boat.php                   # Boat config
├── event.php                  # Event config
├── space.php                  # Space config
└── ... (40+ config files)
```

**Purpose:**
- Application settings
- Service configuration
- Module-specific settings

---

#### 6. **`storage/`** - File Storage

```
storage/
├── app/                       # ✅ Application files
│   ├── public/               # Public uploads
│   └── private/              # Private files
├── framework/                 # ✅ Framework cache
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/                      # ✅ Application logs
    └── laravel.log           # Main log file
```

**Purpose:**
- User uploaded files
- Cache storage
- Session data
- Application logs

---

#### 7. **`public/`** - Public Directory (Entry Point)

```
public/
├── .htaccess                  # ✅ Apache rewrite rules
├── index.php                  # ✅ Application entry point
├── robots.txt                 # SEO robots file
├── web.config                 # IIS configuration
├── adminer/                   # Database admin tool
├── icon/                      # System icons
└── uploads/                   # ✅ Public uploaded files
```

**Purpose:**
- HTTP entry point
- Publicly accessible files
- User uploads

---

#### 8. **`bootstrap/`** - Application Bootstrap

```
bootstrap/
├── app.php                    # ✅ App initialization
└── cache/                     # Bootstrap cache
```

**Purpose:**
- Initialize Laravel application
- Bootstrap cache

---

#### 9. **`vendor/`** - Composer Dependencies

```
vendor/                        # ✅ PHP packages (auto-generated)
├── laravel/
├── stripe/
├── paypal/
└── ... (100+ packages)
```

**Purpose:**
- Third-party PHP libraries
- Framework core
- Installed via `composer install`

---

#### 10. **`lang/`** - Language Files

```
lang/
└── en/                        # English translations
    ├── auth.php
    ├── validation.php
    └── ...
```

**Purpose:**
- Multi-language support
- Translation strings

---

#### 11. **`resources/`** - Resources (Minimal for API)

```
resources/
├── lang/                      # Translations
└── GeoLite2-City_20210622/   # GeoIP database
```

**Purpose:**
- Language files
- GeoIP data for location detection

---

#### 12. **`plugins/`** - Plugin System

```
plugins/
├── ModuleServiceProvider.php
├── ServiceProvider.php
└── PaymentTwoCheckout/        # 2Checkout payment plugin
```

**Purpose:**
- Extend system functionality
- Payment gateway plugins
- Custom integrations

---

#### 13. **`custom/`** - Custom Code

```
custom/
├── Helpers/                   # Custom helpers
├── ModuleServiceProvider.php
└── ServiceProvider.php
```

**Purpose:**
- Project-specific customizations
- Custom modules

---

#### 14. **`tests/`** - Test Files

```
tests/
├── Feature/                   # Feature tests
├── Unit/                      # Unit tests
├── TestCase.php
└── CreatesApplication.php
```

**Purpose:**
- Automated testing
- Quality assurance

---

## 🔌 API ARCHITECTURE

### Base URL
```
http://localhost:8000/api/
```

### Authentication
- **Type:** JWT (JSON Web Tokens)
- **Header:** `Authorization: Bearer {token}`

### Main API Endpoints (modules/Api/Routes/api.php)

#### 🔐 Authentication
```
POST   /api/auth/login              - Login
POST   /api/auth/register           - Register
POST   /api/auth/logout             - Logout
GET    /api/auth/me                 - Get current user
POST   /api/auth/change-password    - Change password
POST   /api/forgot-password         - Forgot password
POST   /api/reset-password          - Reset password
```

#### 🏨 Services (Generic for all types)
```
GET    /api/services                      - All services
GET    /api/{type}/search                 - Search (tour|hotel|flight|car|boat|event|space)
GET    /api/{type}/detail/{id}            - Service detail
GET    /api/{type}/availability/{id}      - Check availability
GET    /api/{type}/filters                - Get filters
GET    /api/{type}/form-search            - Search form data
POST   /api/{type}/write-review/{id}      - Write review
```

#### 🛒 Bookings
```
POST   /api/booking/addToCart           - Add to cart
POST   /api/booking/addEnquiry          - Add enquiry
POST   /api/booking/doCheckout          - Checkout
GET    /api/booking/{code}              - Booking details
GET    /api/booking/{code}/thankyou     - Thank you page
GET    /api/booking/confirm/{gateway}   - Payment confirmation
GET    /api/booking/cancel/{gateway}    - Payment cancellation
```

#### 👤 User
```
GET    /api/user/booking-history        - Booking history
POST   /api/user/wishlist               - Add to wishlist
GET    /api/user/wishlist               - Get wishlist
POST   /api/user/permanently_delete     - Delete account
```

#### 📍 Locations
```
GET    /api/locations                   - Search locations
GET    /api/location/{id}               - Location detail
```

#### 📰 News
```
GET    /api/news                        - Search news
GET    /api/news/category               - News categories
GET    /api/news/{id}                   - News detail
```

#### ⚙️ Configuration
```
GET    /api/configs                     - System config
GET    /api/home-page                   - Homepage layout
GET    /api/gateways                    - Payment gateways
```

#### 📁 Media
```
POST   /api/media/store                 - Upload file (requires auth)
```

---

## 🗄️ DATABASE STRUCTURE

### Main Tables (35+ migrations)

#### Core Tables
- `users` - User accounts
- `user_meta` - User metadata
- `password_resets` - Password reset tokens
- `personal_access_tokens` - API tokens

#### Service Tables
- `bravo_tours` - Tours
- `bravo_hotels` - Hotels
- `bravo_flights` - Flights
- `bravo_cars` - Cars
- `bravo_boats` - Boats
- `bravo_events` - Events
- `bravo_spaces` - Spaces

#### Supporting Tables
- `core_locations` - Locations
- `bravo_bookings` - Bookings
- `bravo_reviews` - Reviews
- `media_files` - File uploads
- `core_settings` - System settings
- `core_languages` - Languages
- `core_translations` - Translations
- `core_news` - News/Blog
- `bravo_coupons` - Coupons
- `vendor_plans` - Vendor plans
- `wallet_transactions` - Wallet transactions

---

## 🔑 KEY FEATURES

### ✅ What This Backend Provides

1. **Multi-Service Booking**
   - Tours
   - Hotels
   - Flights
   - Car Rentals
   - Boat Rentals
   - Event Bookings
   - Space Rentals

2. **Authentication & Authorization**
   - JWT tokens
   - User roles
   - Permissions
   - Social login (backend support)

3. **Payment Processing**
   - Stripe
   - PayPal
   - Razorpay
   - Flutterwave
   - 2Checkout
   - Multiple gateways

4. **File Management**
   - Image uploads
   - Multiple storage (Local, S3, Google Cloud)
   - Image optimization

5. **Multi-Currency**
   - Currency conversion
   - Price management

6. **Multi-Language**
   - Translation system
   - i18n support

7. **Review System**
   - User reviews
   - Ratings
   - Review metadata

8. **Location Management**
   - GeoIP detection
   - Location search
   - Nested locations

9. **Vendor/Marketplace**
   - Multi-vendor support
   - Vendor plans
   - Payout management

10. **Coupon System**
    - Discount codes
    - Coupon validation

---

## 🚀 HOW IT WORKS

### Request Flow

```
1. HTTP Request
   ↓
2. public/index.php (Entry point)
   ↓
3. bootstrap/app.php (Initialize Laravel)
   ↓
4. routes/api.php OR modules/Api/Routes/api.php
   ↓
5. Middleware (Auth, CORS, etc.)
   ↓
6. Controller (modules/Api/Controllers/)
   ↓
7. Model (app/Models/ OR modules/*/Models/)
   ↓
8. Database (MySQL)
   ↓
9. Response (JSON)
```

### Example: Search Tours

```
Request:
GET /api/tour/search?location=paris

Flow:
1. routes/api.php -> modules/Api/Routes/api.php
2. SearchController@search
3. Tour model
4. Database query
5. JSON response with tour list
```

---

## 📦 DEPENDENCIES

### Core PHP Packages (composer.json)

```json
{
  "laravel/framework": "^10.0",        // Framework
  "laravel/sanctum": "^3.2",           // API auth
  "tymon/jwt-auth": "*",               // JWT
  "stripe/stripe-php": "^7.113",       // Stripe
  "omnipay/paypal": "^3.0",            // PayPal
  "intervention/image": "^2.4",        // Image processing
  "maatwebsite/excel": "^3.1",         // Excel export
  "guzzlehttp/guzzle": "^7.2",         // HTTP client
  "spatie/laravel-google-cloud-storage": "^2.0.3"  // Google Cloud
}
```

---

## ⚙️ CONFIGURATION

### Important .env Variables

```env
# App
APP_NAME=BookingCore
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_core
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=your-secret-key

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525

# Payment
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
PAYPAL_CLIENT_ID=xxx
PAYPAL_SECRET=xxx

# Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

---

## 🎯 SUMMARY

### What You Have:
- ✅ Complete REST API
- ✅ 7 Booking Service Types
- ✅ JWT Authentication
- ✅ Payment Gateways (Multiple)
- ✅ File Upload System
- ✅ Review System
- ✅ Multi-currency
- ✅ Multi-language
- ✅ Vendor/Marketplace
- ✅ 35+ Database Tables
- ✅ Modular Architecture

### What You DON'T Have (Removed):
- ❌ Frontend UI
- ❌ Admin Panel UI
- ❌ Blade Templates
- ❌ CSS/JS Assets
- ❌ Theme System

### Usage:
- Backend API only
- Connect any frontend (Next.js, React, Vue, Mobile App)
- Use `/api/*` endpoints
- JWT authentication required for protected routes

---

**Documentation Version:** 1.0  
**Date:** December 6, 2025  
**Project Type:** API-Only Backend  
**Ready for:** Production Use
