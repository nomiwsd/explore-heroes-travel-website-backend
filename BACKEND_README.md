# 🎯 BACKEND-ONLY LARAVEL API

## Overview
Ye ek **Backend API-only** Laravel project hai. Saari frontend functionality remove kar di gayi hai.

## 📁 Project Structure (Backend Only)

```
public_html/
├── app/                    # Core application code
│   ├── Models/            # Database models
│   ├── Http/Controllers/  # Controllers
│   ├── Helpers/           # Helper functions
│   └── ...
├── modules/               # Modular backend
│   ├── Api/              # ✅ API endpoints
│   ├── Booking/          # ✅ Booking logic
│   ├── User/             # ✅ User management
│   ├── Tour/             # ✅ Tour services
│   ├── Hotel/            # ✅ Hotel services
│   ├── Flight/           # ✅ Flight services
│   ├── Car/              # ✅ Car rental
│   ├── Boat/             # ✅ Boat rental
│   ├── Event/            # ✅ Event booking
│   └── ...
├── database/             # Database
│   ├── migrations/       # Database schema
│   └── seeders/          # Sample data
├── routes/               # Route definitions
│   ├── api.php          # ✅ Main API routes
│   └── web.php          # Minimal (API info only)
├── config/               # Configuration files
├── storage/              # File storage
├── bootstrap/            # App initialization
└── public/              # Public folder (minimal)
    └── index.php        # Entry point
```

## 🗑️ Removed (Frontend Components)

### Deleted Folders:
- ❌ `public/css/` - Frontend stylesheets
- ❌ `public/js/` - Frontend JavaScript
- ❌ `public/sass/` - SCSS files
- ❌ `public/themes/` - Theme files
- ❌ `public/images/` - Frontend images
- ❌ `public/fonts/` - Web fonts
- ❌ `public/libs/` - Frontend libraries
- ❌ `resources/views/` - Blade templates
- ❌ `resources/sass/` - SASS files
- ❌ `resources/admin/` - Admin panel views
- ❌ `themes/` - Theme system
- ❌ `node_modules/` - NPM packages

### Deleted Files:
- ❌ `package.json` - NPM dependencies
- ❌ `webpack.mix.js` - Laravel Mix config
- ❌ `gulpfile.js` - Gulp config
- ❌ `vite.config.js` - Vite config

## ✅ What's Included (Backend Only)

### Core Backend:
- ✅ **REST API** (`routes/api.php` + `modules/Api/`)
- ✅ **Database** (migrations, models, seeders)
- ✅ **Authentication** (JWT, Sanctum)
- ✅ **Business Logic** (modules/)
- ✅ **Services** (Tour, Hotel, Flight, Car, Boat, Event, Space)
- ✅ **Payment Gateways** (Stripe, PayPal, Razorpay, etc.)
- ✅ **File Storage** (Local, S3, Google Cloud)

## 🚀 Setup (Backend Only)

### 1. Install Dependencies
```powershell
composer install
```

### 2. Configure Environment
```powershell
Copy-Item .env.example .env
# Edit .env - set database credentials
```

### 3. Generate Keys
```powershell
php artisan key:generate
php artisan jwt:secret
```

### 4. Setup Database
```powershell
# Create database in MySQL first
php artisan migrate --seed
```

### 5. Start Server
```powershell
php artisan serve
```

API runs at: **http://localhost:8000/api**

## 📡 API Endpoints

### Health Check:
```
GET http://localhost:8000/
```

### Main API Base:
```
http://localhost:8000/api/
```

### Key Endpoints:

#### Authentication:
- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register
- `GET /api/auth/me` - Current user
- `POST /api/auth/logout` - Logout

#### Services:
- `GET /api/services` - All services
- `GET /api/{type}/search` - Search (tour/hotel/flight/car/boat/event/space)
- `GET /api/{type}/detail/{id}` - Service details
- `GET /api/{type}/availability/{id}` - Check availability

#### Bookings:
- `POST /api/booking/addToCart` - Add to cart
- `POST /api/booking/doCheckout` - Checkout
- `GET /api/booking/{code}` - Booking details

#### User:
- `GET /api/user/booking-history` - User bookings
- `POST /api/user/wishlist` - Manage wishlist

#### Configuration:
- `GET /api/configs` - System config
- `GET /api/home-page` - Homepage layout

## 🔧 Configuration

### Database (`.env`):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_core
DB_USERNAME=root
DB_PASSWORD=
```

### API Settings:
```env
APP_URL=http://localhost:8000
API_PREFIX=api
```

## 📦 Backend Dependencies

### Core:
- Laravel 10
- PHP 8.1+
- MySQL/MariaDB

### Key Packages:
- JWT Authentication (`tymon/jwt-auth`)
- Laravel Sanctum (API tokens)
- Payment Gateways (Stripe, PayPal, Razorpay)
- Image Processing (`intervention/image`)
- Excel Export (`maatwebsite/excel`)
- Cloud Storage (S3, Google Cloud)

## 🎯 Frontend Integration

### Connect with Next.js:
```typescript
// API Client (axios)
const API_URL = 'http://localhost:8000/api';

// Example: Fetch tours
const tours = await axios.get(`${API_URL}/tour/search`);
```

See `NEXTJS_INTEGRATION.md` for detailed guide.

## 🔐 Authentication

### JWT Tokens:
```javascript
// Login
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

// Response
{
  "access_token": "eyJ0eXAiOiJKV1...",
  "token_type": "bearer",
  "expires_in": 3600
}

// Use token in headers
Authorization: Bearer eyJ0eXAiOiJKV1...
```

## 📊 Database

### Main Tables:
- `users` - User accounts
- `bravo_tours` - Tours
- `bravo_hotels` - Hotels
- `bravo_flights` - Flights
- `bravo_cars` - Car rentals
- `bravo_boats` - Boat rentals
- `bravo_events` - Events
- `bravo_spaces` - Space rentals
- `bravo_bookings` - Bookings
- `bravo_reviews` - Reviews
- `core_locations` - Locations
- `media_files` - File uploads

## 🧪 Testing API

### Using cURL:
```powershell
# Get configs
curl http://localhost:8000/api/configs

# Search tours
curl http://localhost:8000/api/tour/search?location=paris

# Login
curl -X POST http://localhost:8000/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'
```

### Using Postman:
1. Import base URL: `http://localhost:8000/api`
2. Test endpoints from `modules/Api/Routes/api.php`

## ⚠️ Important Notes

1. **No Frontend**: Is project mein ab koi frontend nahi hai
2. **API Only**: Saare routes `/api/` prefix ke saath hain
3. **CORS**: Frontend connect karne k liye `config/cors.php` configure karein
4. **Storage**: Uploaded files `storage/app/public/` mein save hote hain
5. **Logs**: Errors check karne k liye `storage/logs/` dekhen

## 🔄 Cleanup Script

Agar dobara frontend files remove karni hain:
```powershell
.\cleanup_frontend.ps1
```

## 📚 Documentation

- Laravel: https://laravel.com/docs/10.x
- API Integration: See `NEXTJS_INTEGRATION.md`
- Setup Guide: See `SETUP_GUIDE.md`

## ✅ Backend-Only Checklist

- [x] Frontend views removed
- [x] Frontend assets removed (CSS, JS, themes)
- [x] NPM dependencies removed
- [x] Build tools removed (Mix, Vite, Gulp)
- [x] API routes accessible
- [x] Database migrations ready
- [x] JWT authentication working
- [x] Services modules active
- [x] Payment gateways configured
- [x] File storage working

---

**Project Type:** Backend API Only  
**Version:** 3.0.0  
**Laravel:** 10.x  
**PHP:** 8.1+  
**Purpose:** Travel Booking System Backend
