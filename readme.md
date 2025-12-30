# 🎯 Laravel Booking Core - Backend API

**Complete REST API for Travel & Booking Management System**

---

## 📊 Quick Stats

- **Type:** API-Only Backend
- **Framework:** Laravel 10.x
- **PHP:** 8.1+
- **Modules:** 21 Active
- **Services:** 7 Booking Types
- **Database:** 35+ Tables
- **API Endpoints:** 30+
- **Status:** ✅ Production Ready

---

## 🎯 What This Is

Ye ek **complete backend API** hai jo travel aur booking services provide karta hai:

### ✅ Services Available:
1. **Tour Booking** - Tours search aur book karna
2. **Hotel Booking** - Hotels aur rooms
3. **Flight Booking** - Flight tickets
4. **Car Rental** - Car booking
5. **Boat Rental** - Boat booking
6. **Event Booking** - Event tickets
7. **Space Rental** - Space/venue booking

### ✅ Features:
- JWT Authentication
- Multi-currency
- Multi-language
- Payment Gateways (Stripe, PayPal, Razorpay, etc.)
- File Uploads
- Review System
- Coupon/Discount System
- Vendor Marketplace
- Booking Management
- User Management

---

## 📚 Documentation (6 Files)

### 🔥 **START HERE:**
1. **`COMPLETE_BACKEND_DOCUMENTATION.md`** ← **MUST READ**
   - Complete structure explained (400+ lines)
   - Every folder/file ka purpose
   - All modules detailed
   - API endpoints listed
   - Database schema explained
   - **Sabse pehle ye file padhein!**

### 📖 **Quick Reference:**
2. **`QUICK_REFERENCE.md`**
   - Quick lookup guide
   - Urdu/English explanations
   - Common commands
   - File paths reference

### 🚀 **Setup:**
3. **`SETUP_GUIDE.md`**
   - Installation steps
   - Database setup
   - Configuration
   - Running server

### 🔌 **Frontend Integration:**
4. **`NEXTJS_INTEGRATION.md`**
   - Next.js se connect karna
   - API client setup
   - Example code
   - Authentication flow

### 🗑️ **Cleanup History:**
5. **`CLEANUP_SUMMARY.md`**
   - Kya remove kiya
   - Cleanup details

6. **`BACKEND_README.md`**
   - Quick overview
   - Backend-only info

---

## 🏗️ Project Structure

```
public_html/
├── app/                       # Core application
├── modules/                   # 21 Backend modules
│   ├── Api/                  # ✅ REST API (MAIN)
│   ├── Booking/              # Booking system
│   ├── User/                 # User management
│   ├── Tour/                 # Tour services
│   ├── Hotel/                # Hotel services
│   ├── Flight/               # Flight services
│   ├── Car/                  # Car rental
│   ├── Boat/                 # Boat rental
│   ├── Event/                # Event booking
│   ├── Space/                # Space rental
│   └── ... (11 more modules)
├── database/                  # Migrations & seeders
├── routes/                    # API routes
├── config/                    # Configuration
├── storage/                   # Files & logs
├── public/                    # Entry point
└── [Documentation files]
```

---

## 🚀 Quick Start

### 1️⃣ Install Dependencies
```bash
composer install
```

### 2️⃣ Configure Environment
```bash
# Copy environment file
Copy-Item .env.example .env

# Edit .env and set:
# - Database credentials
# - APP_URL
# - Payment gateway keys (optional)
```

### 3️⃣ Generate Keys
```bash
php artisan key:generate
php artisan jwt:secret
```

### 4️⃣ Setup Database
```bash
# Create database in MySQL first, then:
php artisan migrate --seed
```

### 5️⃣ Start Server
```bash
php artisan serve
```

**Backend runs at:** `http://localhost:8000`  
**API base:** `http://localhost:8000/api`

---

## 📡 API Endpoints

### Base URL
```
http://localhost:8000/api/
```

### Quick Test
```bash
# Browser ya Postman mein:
GET http://localhost:8000/api/configs
```

### Main Endpoints

#### Authentication
```
POST /api/auth/login          - Login
POST /api/auth/register       - Register
GET  /api/auth/me            - Current user
POST /api/auth/logout        - Logout
```

#### Services (for all types)
```
GET /api/services                    - All services
GET /api/{type}/search               - Search
GET /api/{type}/detail/{id}          - Details
GET /api/{type}/availability/{id}    - Check availability
```
Types: `tour`, `hotel`, `flight`, `car`, `boat`, `event`, `space`

#### Bookings
```
POST /api/booking/addToCart      - Add to cart
POST /api/booking/doCheckout     - Checkout
GET  /api/booking/{code}         - Booking details
```

**Full API documentation:** See `COMPLETE_BACKEND_DOCUMENTATION.md`

---

## 🎯 Main Modules

| Module | Purpose | Priority |
|--------|---------|----------|
| **Api** | REST API endpoints | 🔥 HIGH |
| **Booking** | Booking management | 🔥 HIGH |
| **User** | User system | 🔥 HIGH |
| **Tour** | Tour services | ⭐ MEDIUM |
| **Hotel** | Hotel services | ⭐ MEDIUM |
| **Flight** | Flight services | ⭐ MEDIUM |
| **Car** | Car rental | ⭐ MEDIUM |
| **Boat** | Boat rental | ⭐ MEDIUM |
| **Event** | Event booking | ⭐ MEDIUM |
| **Space** | Space rental | ⭐ MEDIUM |
| **Core** | Core functions | 🔥 HIGH |
| **Media** | File uploads | ⭐ MEDIUM |
| **Review** | Review system | ⚡ LOW |
| **Coupon** | Discounts | ⚡ LOW |
| **Vendor** | Marketplace | ⚡ LOW |
| Others | Support modules | ⚡ LOW |

---

## 🗄️ Database

### Tables: 35+
- User management
- 7 Service types
- Bookings
- Reviews
- Locations
- Media files
- Payments
- And more...

**Schema details:** See `COMPLETE_BACKEND_DOCUMENTATION.md`

---

## 🔧 Configuration

### Important .env Variables
```env
APP_URL=http://localhost:8000
DB_DATABASE=booking_core
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your-secret-key

STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
```

**Full config guide:** See `SETUP_GUIDE.md`

---

## 🔌 Frontend Integration

### Connect with Next.js, React, Vue, or any frontend:

```typescript
// API Base
const API_URL = 'http://localhost:8000/api';

// Example: Login
const response = await axios.post(`${API_URL}/auth/login`, {
  email: 'user@example.com',
  password: 'password'
});

// Use token
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

**Complete integration guide:** See `NEXTJS_INTEGRATION.md`

---

## 📖 Learn More

### Documentation Priority:
1. **Start:** `COMPLETE_BACKEND_DOCUMENTATION.md` (Read this first!)
2. **Setup:** `SETUP_GUIDE.md`
3. **Quick Ref:** `QUICK_REFERENCE.md`
4. **Integration:** `NEXTJS_INTEGRATION.md`

### File Locations:
- **API Routes:** `modules/Api/Routes/api.php`
- **Controllers:** `modules/Api/Controllers/`
- **Models:** `modules/*/Models/`
- **Config:** `config/`
- **Logs:** `storage/logs/laravel.log`

---

## 🛠️ Common Commands

```bash
# Start server
php artisan serve

# Run migrations
php artisan migrate

# Fresh database
php artisan migrate:fresh --seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# List routes
php artisan route:list

# View logs
Get-Content storage/logs/laravel.log -Tail 50
```

---

## ❓ Need Help?

### Check:
1. `COMPLETE_BACKEND_DOCUMENTATION.md` - Complete guide
2. `QUICK_REFERENCE.md` - Quick lookup
3. `storage/logs/laravel.log` - Error logs

### Common Issues:
- **Route not found?** → Check `modules/Api/Routes/api.php`
- **Database error?** → Check `.env` credentials
- **Permission denied?** → `chmod -R 775 storage bootstrap/cache`
- **500 error?** → Check `storage/logs/laravel.log`

---

## ✅ What's Included

- ✅ Complete REST API
- ✅ 7 Booking Services
- ✅ JWT Authentication
- ✅ Payment Gateways
- ✅ File Uploads
- ✅ Multi-currency
- ✅ Multi-language
- ✅ Review System
- ✅ Vendor Marketplace
- ✅ 35+ Database Tables
- ✅ Complete Documentation

---

## ❌ What's NOT Included (Removed)

- ❌ Frontend UI
- ❌ Admin Panel UI
- ❌ Blade Templates
- ❌ CSS/JS Assets
- ❌ Theme System
- ❌ Build Tools

**Reason:** API-only backend for use with any frontend

---

## 📊 Summary

**Type:** REST API Backend  
**Use Case:** Travel & Booking Platform  
**Frontend:** Not included (connect your own)  
**Ready:** ✅ Yes  
**Documented:** ✅ Fully  

---

## 🎯 Next Steps

1. ✅ Read `COMPLETE_BACKEND_DOCUMENTATION.md`
2. ⚙️ Setup using `SETUP_GUIDE.md`
3. 🚀 Start server: `php artisan serve`
4. 🧪 Test: `http://localhost:8000/api`
5. 🔌 Integrate frontend using `NEXTJS_INTEGRATION.md`

---

**Version:** 3.0.0  
**Last Updated:** December 6, 2025  
**Status:** Production Ready ✅
