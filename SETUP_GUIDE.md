# 🚀 Laravel Booking Core - Setup Guide

## ✅ Prerequisites (Pehle ye install karein)

1. **PHP 8.1+** - https://windows.php.net/download/
2. **Composer** - https://getcomposer.org/
3. **MySQL/MariaDB** - XAMPP ya MySQL Server
4. **Node.js** (optional) - Build assets k liye

---

## 📝 Step-by-Step Setup

### **STEP 1: Environment File Banana**

```powershell
# .env.example ko copy karke .env banana
Copy-Item .env.example .env
```

### **STEP 2: .env File Configure Karna**

`.env` file ko edit karein aur ye settings update karein:

```env
APP_NAME=BookingCore
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_core
DB_USERNAME=root
DB_PASSWORD=your_password_here

# Mail Settings (optional - testing k liye)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

### **STEP 3: Database Banana**

MySQL/phpMyAdmin mein naya database banayein:

```sql
CREATE DATABASE booking_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### **STEP 4: Dependencies Install Karna**

```powershell
# Composer packages install karein
composer install

# Application key generate karein
php artisan key:generate

# JWT secret generate karein (API authentication k liye)
php artisan jwt:secret
```

### **STEP 5: Database Migrate Karna**

```powershell
# Database tables create karein
php artisan migrate

# Demo data insert karein (optional)
php artisan db:seed
```

### **STEP 6: Storage Link Banana**

```powershell
# Public storage folder link banana
php artisan storage:link
```

### **STEP 7: Server Run Karna**

```powershell
# Development server start karein
php artisan serve
```

Server chalega: **http://localhost:8000**

---

## 🔧 Common Issues & Solutions

### ❌ "Class 'JWT' not found"
```powershell
composer require tymon/jwt-auth
php artisan jwt:secret
```

### ❌ "Storage link already exists"
```powershell
# Pehle delete karein phir dobara banayein
Remove-Item public/storage -Force -Recurse
php artisan storage:link
```

### ❌ "Permission denied" errors
```powershell
# Storage aur cache folders ko writable banana
chmod -R 775 storage bootstrap/cache
```

### ❌ Database connection error
- Check MySQL service running hai ya nahi
- .env mein DB credentials sahi hain
- Database create kiya hai ya nahi

---

## 🎯 Testing API

### Test karein k API kaam kar raha hai:

```powershell
# Browser ya Postman mein test karein:
GET http://localhost:8000/api/configs
```

Response milna chahiye:
```json
{
  "status": true,
  "data": {...}
}
```

---

## 📱 Next Steps

1. ✅ Server successfully chal raha hai
2. ✅ API endpoints test kar liye
3. ➡️ Ab Next.js frontend integrate karein (dekhen: NEXTJS_INTEGRATION.md)

