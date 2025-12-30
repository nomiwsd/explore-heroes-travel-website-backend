# 💻 Windows Pe Laravel Backend Chalane K Liye Complete Setup Guide

## 🎯 Aapko Kya Kya Install Karna Hai

Laravel backend chalane k liye aapko ye cheezein chahiye:
1. ✅ **PHP 8.1+** (Programming language)
2. ✅ **Composer** (PHP package manager)
3. ✅ **MySQL Database** (Database server)
4. ✅ **Git** (Optional - code management k liye)

---

## 🚀 METHOD 1: XAMPP (RECOMMENDED - Sabse Easy)

**XAMPP kya hai?** 
Ek package jo PHP, MySQL, Apache sab kuch ek saath install kar deta hai. **Beginners k liye best!**

### Step 1: XAMPP Download Karein

1. **Website:** https://www.apachefriends.org/download.html
2. **Version:** XAMPP for Windows (PHP 8.1 ya 8.2)
3. **Size:** ~150 MB
4. **Download:** `xampp-windows-x64-8.2.12-0-VS16-installer.exe`

### Step 2: XAMPP Install Karein

1. Downloaded file ko run karein
2. **Components Select Karein:**
   - ✅ Apache (web server)
   - ✅ MySQL (database)
   - ✅ PHP (programming language)
   - ✅ phpMyAdmin (database management)
   - ❌ FileZilla (not needed)
   - ❌ Mercury (not needed)
   - ❌ Tomcat (not needed)

3. **Installation Folder:**
   - Default: `C:\xampp`
   - Recommended: Keep default

4. **Install** button click karein
5. Wait for installation (5-10 minutes)

### Step 3: XAMPP Start Karein

1. **XAMPP Control Panel** open karein
2. **Apache** start karein (green light hogi)
3. **MySQL** start karein (green light hogi)

![XAMPP Control Panel](https://i.imgur.com/XYZ.png)

```
Apache:  [Start] [Stop] [Config] [Logs]  ← Green light = Running
MySQL:   [Start] [Stop] [Admin] [Logs]   ← Green light = Running
```

### Step 4: Test Karein

**Browser mein kholen:**
- `http://localhost` → XAMPP welcome page dikhega
- `http://localhost/phpmyadmin` → phpMyAdmin open hoga

**Agar ye dono kaam kar rahe hain to XAMPP successfully install ho gaya!** ✅

---

## 📦 Step 5: Composer Install Karein

**Composer kya hai?** 
PHP packages install karne ka tool (jaise NPM for Node.js)

### Download:
1. **Website:** https://getcomposer.org/download/
2. **File:** `Composer-Setup.exe`
3. **Size:** ~2 MB

### Install:
1. `Composer-Setup.exe` run karein
2. **PHP Path:** 
   - Automatic detect karega: `C:\xampp\php\php.exe`
   - Agar nahi to manually select karein
3. **Developer Mode:** Unchecked rakhen
4. **Proxy Settings:** Skip karein
5. **Install** click karein

### Test:
```powershell
# PowerShell ya CMD mein:
composer --version
```

**Output:**
```
Composer version 2.6.5 2023-10-06 10:11:52
```

**Agar version dikha to Composer successfully install ho gaya!** ✅

---

## 🗄️ Step 6: Database Setup

### Option A: phpMyAdmin Use Karein (Easy - GUI)

1. **Browser mein kholen:** `http://localhost/phpmyadmin`
2. **Login:**
   - Username: `root`
   - Password: (empty - blank chhodein)
3. **Database Create:**
   - Left side pe **"New"** click karein
   - Database name: `booking_core`
   - Collation: `utf8mb4_unicode_ci`
   - **Create** click karein

### Option B: MySQL Command Line (Advanced)

```powershell
# XAMPP shell mein:
cd C:\xampp\mysql\bin
.\mysql.exe -u root

# MySQL console mein:
CREATE DATABASE booking_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

---

## 🎨 Optional: Git Install (Recommended)

**Git kya hai?** 
Code management aur version control tool

### Download:
1. **Website:** https://git-scm.com/download/win
2. **File:** `Git-2.43.0-64-bit.exe`
3. **Size:** ~50 MB

### Install:
1. Downloaded file run karein
2. **All default settings rakhen** (Next, Next, Install)
3. Install complete

### Test:
```powershell
git --version
```

**Output:**
```
git version 2.43.0.windows.1
```

---

## ✅ COMPLETE INSTALLATION CHECKLIST

### Check Karein:

```powershell
# 1. PHP Check
php --version
# Output: PHP 8.2.12 (cli)

# 2. Composer Check
composer --version
# Output: Composer version 2.6.5

# 3. MySQL Check (XAMPP Control Panel mein green light)

# 4. Database Check
# Browser: http://localhost/phpmyadmin
```

---

## 🚀 AB LARAVEL PROJECT SETUP KAREIN

### Step 1: Project Folder Mein Jayen

```powershell
cd "C:\Users\Nomi Malik\Downloads\exploreheroes.com\public_html"
```

### Step 2: Environment File Setup

```powershell
# .env file banayein
Copy-Item .env.example .env

# .env file edit karein (Notepad ya VS Code mein)
notepad .env
```

**Important settings (.env mein):**
```env
APP_NAME=BookingCore
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_core
DB_USERNAME=root
DB_PASSWORD=
```
**Note:** Password blank hai kyunki XAMPP default password nahi rakhta

### Step 3: Dependencies Install Karein

```powershell
# Composer packages install (5-10 minutes lag sakte hain)
composer install
```

**Ye command kya karega?**
- `vendor/` folder banayega
- 100+ PHP packages download karega
- Laravel aur dependencies install karega

### Step 4: Application Keys Generate

```powershell
# Laravel application key
php artisan key:generate

# JWT secret key (API authentication k liye)
php artisan jwt:secret
```

### Step 5: Database Tables Create Karein

```powershell
# Database tables banayein
php artisan migrate

# Sample data insert karein (optional)
php artisan db:seed
```

**Ye kya karega?**
- 35+ database tables create karega
- Demo data insert karega

### Step 6: Storage Link

```powershell
# Public storage folder ka link banayein
php artisan storage:link
```

### Step 7: Server Start Karein

```powershell
# Development server start
php artisan serve
```

**Output:**
```
Starting Laravel development server: http://127.0.0.1:8000
[Fri Dec  6 10:30:00 2025] PHP 8.2.12 Development Server started
```

**Server chal gaya!** ✅

---

## 🧪 TEST KAREIN

### Browser Mein Test:

1. **Homepage:**
   ```
   http://localhost:8000
   ```
   Response: API info dikhega

2. **API Test:**
   ```
   http://localhost:8000/api/configs
   ```
   Response: JSON data

3. **Database:**
   ```
   http://localhost/phpmyadmin
   ```
   Left side: `booking_core` database aur tables dikhengi

---

## ❌ COMMON PROBLEMS & SOLUTIONS

### Problem 1: "php is not recognized"

**Solution:**
```powershell
# PHP path add karein environment variables mein
# Ya directly use karein:
C:\xampp\php\php.exe --version
```

**Fix (Permanent):**
1. Windows search → "Environment Variables"
2. System Properties → Environment Variables
3. Path variable mein add: `C:\xampp\php`
4. PowerShell restart karein

### Problem 2: "composer is not recognized"

**Solution:**
Composer ko re-install karein ya path check karein

### Problem 3: "Port 8000 already in use"

**Solution:**
```powershell
# Different port use karein
php artisan serve --port=8080

# Ya running process ko stop karein
netstat -ano | findstr :8000
taskkill /PID <process_id> /F
```

### Problem 4: "Access denied for user 'root'"

**Solution:**
`.env` file check karein:
```env
DB_USERNAME=root
DB_PASSWORD=        # Blank rakhen (XAMPP default)
```

### Problem 5: "SQLSTATE[HY000] [2002] Connection refused"

**Solution:**
1. XAMPP Control Panel mein MySQL start hai?
2. Green light dikhi rahi hai?
3. Agar nahi to MySQL ko restart karein

### Problem 6: "Class 'JWT' not found"

**Solution:**
```powershell
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### Problem 7: "Storage link already exists"

**Solution:**
```powershell
Remove-Item public\storage -Force -Recurse -ErrorAction SilentlyContinue
php artisan storage:link
```

---

## 📊 INSTALLATION TIME ESTIMATE

| Task | Time |
|------|------|
| XAMPP download | 5-10 min |
| XAMPP install | 5-10 min |
| Composer install | 2-5 min |
| Database setup | 2-3 min |
| Laravel dependencies | 5-10 min |
| Database migration | 2-5 min |
| **Total** | **~30-45 minutes** |

---

## 🎯 FINAL CHECKLIST

### Before Starting Project:

- [ ] XAMPP installed
- [ ] Apache running (green light)
- [ ] MySQL running (green light)
- [ ] Composer installed
- [ ] Database created (`booking_core`)
- [ ] `.env` file configured
- [ ] `composer install` completed
- [ ] `php artisan key:generate` done
- [ ] `php artisan jwt:secret` done
- [ ] `php artisan migrate` done
- [ ] `php artisan serve` running

### Agar sab ✅ hai to:

**Backend successfully chal raha hai!** 🎉

Test karein: `http://localhost:8000/api/configs`

---

## 💡 USEFUL COMMANDS

```powershell
# Server start/stop
php artisan serve                  # Start server
Ctrl+C                            # Stop server

# Database
php artisan migrate               # Run migrations
php artisan migrate:fresh         # Fresh database
php artisan migrate:fresh --seed  # Fresh + sample data
php artisan db:seed               # Add sample data

# Cache clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# View routes
php artisan route:list

# Check Laravel version
php artisan --version

# Help
php artisan help migrate
```

---

## 📱 NEXT STEPS

1. ✅ Backend setup complete
2. ✅ Server running on `http://localhost:8000`
3. ✅ API working
4. ➡️ **Next:** Frontend (Next.js) setup
5. ➡️ **Read:** `NEXTJS_INTEGRATION.md`

---

## 🆘 NEED HELP?

### Check These:

1. **XAMPP not starting?**
   - Port 80/3306 already in use (Skype, IIS)
   - Run as Administrator
   - Check firewall

2. **Composer install failing?**
   - Check internet connection
   - Try: `composer install --ignore-platform-reqs`

3. **Migration errors?**
   - Check database exists
   - Check `.env` credentials
   - Check MySQL is running

4. **Other issues?**
   - Check `storage/logs/laravel.log`
   - Google the error message
   - Ask for help with exact error

---

## 📚 DOWNLOADS SUMMARY

| Software | Link | Size | Required |
|----------|------|------|----------|
| **XAMPP** | https://www.apachefriends.org | ~150MB | ✅ Yes |
| **Composer** | https://getcomposer.org | ~2MB | ✅ Yes |
| **Git** | https://git-scm.com | ~50MB | ⚠️ Optional |
| **VS Code** | https://code.visualstudio.com | ~80MB | ⚠️ Optional |

---

**Total Download:** ~200-280 MB  
**Total Time:** ~30-45 minutes  

---

**Good luck! Setup shuru karein aur agar koi problem aaye to batayein!** 🚀
