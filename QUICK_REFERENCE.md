# 📖 QUICK REFERENCE - Backend Structure (Urdu/English)

## 📁 Har Folder Ka Kaam (What Each Folder Does)

### ✅ **MUST HAVE** Folders (Essential)

#### 1. **`app/`** - Main Application Code
**Kya hai?** Laravel ki core application logic  
**Kaam:** Models, Controllers, Helpers, Middleware  
**Touch karna?** Haan, agar customization chahiye

#### 2. **`modules/`** - Backend Services (SABSE IMPORTANT)
**Kya hai?** Saari booking services yahan hain  
**Kaam:** API, Tour, Hotel, Flight, Car, Boat, Event, Space, User, Booking, etc.  
**Touch karna?** Haan, yeh aapka main code hai

**Zaroori Modules:**
- `Api/` - REST API endpoints ← **Sabse zaroori**
- `Booking/` - Booking management
- `User/` - User system
- `Tour/` - Tour booking
- `Hotel/` - Hotel booking
- `Flight/` - Flight booking
- `Car/` - Car rental
- `Boat/` - Boat rental
- `Event/` - Event booking
- `Space/` - Space rental
- `Core/` - Core functionality
- `Media/` - File uploads
- `Location/` - Locations
- `Review/` - Reviews
- `Coupon/` - Discounts
- `Vendor/` - Marketplace
- `Payment/` - Payments (plugins mein)

#### 3. **`database/`** - Database
**Kya hai?** Database structure aur data  
**Kaam:** 
- `migrations/` - Database tables (35+ files)
- `seeders/` - Sample data
**Touch karna?** Sirf migrations add karne k liye

#### 4. **`routes/`** - API Routes
**Kya hai?** URL endpoints define karte hain  
**Kaam:**
- `api.php` - Basic routes
- `modules/Api/Routes/api.php` - **Main API routes**
**Touch karna?** Nahi, modules mein routes hain

#### 5. **`config/`** - Configuration
**Kya hai?** Settings aur configurations  
**Kaam:** Database, Mail, Payment, etc. settings  
**Touch karna?** Haan, settings change k liye

#### 6. **`storage/`** - File Storage
**Kya hai?** Files, cache, logs  
**Kaam:**
- `app/public/` - User uploads
- `logs/` - Error logs
**Touch karna?** Nahi (auto-managed)

#### 7. **`public/`** - Public Folder
**Kya hai?** Web server ki entry point  
**Kaam:**
- `index.php` - Main entry ← **Important**
- `uploads/` - Public files
**Touch karna?** Nahi

#### 8. **`bootstrap/`** - Bootstrap
**Kya hai?** Laravel initialize karta hai  
**Kaam:** App startup  
**Touch karna?** NEVER

#### 9. **`vendor/`** - PHP Packages
**Kya hai?** Composer packages (auto-generated)  
**Kaam:** Laravel aur third-party libraries  
**Touch karna?** NEVER (composer install se banta hai)

---

### ⚠️ **OPTIONAL** Folders (Kam use hote hain)

#### 10. **`custom/`** - Custom Code
**Kya hai?** Project-specific customizations  
**Kaam:** Custom modules  
**Touch karna?** Agar customization ho to haan

#### 11. **`plugins/`** - Plugins
**Kya hai?** Additional plugins  
**Kaam:** Payment gateways (2Checkout, etc.)  
**Touch karna?** Nahi, unless plugin add karna ho

#### 12. **`lang/`** - Languages
**Kya hai?** Translation files  
**Kaam:** Multi-language support  
**Touch karna?** Haan, agar translation add karna ho

#### 13. **`resources/`** - Resources (Minimal)
**Kya hai?** Minimal resources (no views)  
**Kaam:** Language files, GeoIP data  
**Touch karna?** Nahi

#### 14. **`tests/`** - Testing
**Kya hai?** Test files  
**Kaam:** Automated testing  
**Touch karna?** Optional (testing k liye)

---

## 📄 Root Files Explained

### ✅ **ESSENTIAL FILES**

1. **`.env`** ← **MOST IMPORTANT**
   - Configuration file
   - Database credentials
   - API keys
   - Payment gateway keys
   - **NEVER commit to Git**

2. **`artisan`**
   - Laravel command-line tool
   - Run: `php artisan serve`

3. **`composer.json`**
   - PHP dependencies
   - Define packages

4. **`composer.lock`**
   - Locked package versions
   - Auto-generated

### ⚠️ **OPTIONAL FILES**

5. **`.env.example`**
   - Environment template
   - Copy to create `.env`

6. **`.gitignore`**
   - Git ignore rules

7. **`.htaccess`**
   - Apache configuration

8. **`.editorconfig`**
   - Editor settings (optional)

9. **`phpunit.xml`**
   - Testing config

---

## 🔥 MOST IMPORTANT FILES/FOLDERS

### Top 10 (Priority Order):

1. **`modules/Api/Routes/api.php`** ← All API endpoints
2. **`modules/Api/Controllers/`** ← API logic
3. **`.env`** ← Configuration
4. **`database/migrations/`** ← Database structure
5. **`modules/Booking/`** ← Booking system
6. **`modules/User/`** ← User management
7. **`modules/Tour/` (Hotel, Flight, etc.)** ← Services
8. **`config/`** ← Settings
9. **`app/Models/`** ← Data models
10. **`public/index.php`** ← Entry point

---

## 🗺️ File Organization Map

```
REQUEST FLOW:
public/index.php 
  → bootstrap/app.php
  → routes/api.php
  → modules/Api/Routes/api.php
  → modules/Api/Controllers/
  → app/Models/ OR modules/*/Models/
  → database (MySQL)
  → JSON Response

CONFIGURATION:
.env → config/*.php → Application

FILE UPLOADS:
Upload → public/uploads/ OR storage/app/public/

LOGS:
Errors → storage/logs/laravel.log
```

---

## 📊 Module Structure (Template)

Har module ki structure:

```
modules/ServiceName/
├── Controllers/           ← Business logic
│   └── ServiceController.php
├── Models/               ← Database models
│   └── Service.php
├── Routes/               ← Routes (optional)
│   ├── admin.php
│   └── web.php
├── Views/                ← REMOVED (no frontend)
├── ModuleProvider.php    ← Module setup
└── config.php           ← Module config
```

---

## 🎯 Quick Commands Reference

```bash
# Start server
php artisan serve

# Run migrations
php artisan migrate

# Fresh database
php artisan migrate:fresh --seed

# Generate key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# List routes
php artisan route:list

# Create model
php artisan make:model ModelName

# Create controller
php artisan make:controller ControllerName

# Create migration
php artisan make:migration create_table_name
```

---

## 📝 Common File Paths

### Configuration
- Database: `config/database.php`
- CORS: `config/cors.php`
- JWT: `config/jwt.php`
- Mail: `config/mail.php`
- Payment: `config/payment.php`

### API Routes
- Main: `modules/Api/Routes/api.php`
- Tour: `modules/Tour/Routes/`
- Hotel: `modules/Hotel/Routes/`

### Controllers
- Auth: `modules/Api/Controllers/AuthController.php`
- Booking: `modules/Api/Controllers/BookingController.php`
- Search: `modules/Api/Controllers/SearchController.php`

### Models
- User: `app/User.php`
- Tour: `modules/Tour/Models/Tour.php`
- Hotel: `modules/Hotel/Models/Hotel.php`
- Booking: `modules/Booking/Models/Booking.php`

### Logs
- Application: `storage/logs/laravel.log`

### Uploads
- Public: `public/uploads/`
- Private: `storage/app/`

---

## 🚫 NEVER TOUCH

1. **`vendor/`** - Auto-generated
2. **`bootstrap/cache/`** - Auto-generated
3. **`storage/framework/`** - Auto-generated
4. **`public/index.php`** - Entry point
5. **`composer.lock`** - Auto-generated
6. **`.env`** in production - Use deployment tools

---

## ✅ SAFE TO EDIT

1. **`.env`** - Configuration
2. **`modules/`** - Your code
3. **`config/`** - Settings
4. **`database/migrations/`** - Database changes
5. **`routes/`** - Custom routes
6. **`app/`** - Application code

---

## 💡 Tips

### Finding Files
- **API endpoints?** → `modules/Api/Routes/api.php`
- **Service logic?** → `modules/ServiceName/Controllers/`
- **Database tables?** → `database/migrations/`
- **Settings?** → `config/` OR `.env`
- **Errors?** → `storage/logs/laravel.log`
- **Uploads?** → `public/uploads/` OR `storage/app/public/`

### Common Issues
- **Route not found?** → Check `modules/Api/Routes/api.php`
- **Database error?** → Check `.env` credentials
- **Permission denied?** → `chmod -R 775 storage bootstrap/cache`
- **Composer error?** → Run `composer install`
- **Cache issue?** → `php artisan cache:clear`

---

## 📖 Documentation Priority

1. **COMPLETE_BACKEND_DOCUMENTATION.md** ← Read this first
2. **SETUP_GUIDE.md** ← Setup steps
3. **NEXTJS_INTEGRATION.md** ← Frontend integration
4. **THIS FILE** ← Quick reference

---

**Ye file save kar lo - Quick reference k liye!** 📌
