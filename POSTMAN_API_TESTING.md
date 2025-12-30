# 🚀 Postman API Testing Guide

## Server Running: ✅
**URL:** http://localhost:8000

---

## 📋 Test API Endpoints

### 1. Health Check (Simple Test)
```
Method: GET
URL: http://localhost:8000/api/test/health
```

**Response:**
```json
{
    "success": true,
    "message": "API is working!",
    "timestamp": "2025-12-06 15:30:00",
    "database": "u202859753_exploreheroes",
    "environment": "local"
}
```

---

### 2. Database Information (All Tables)
```
Method: GET
URL: http://localhost:8000/api/test/database
```

**Response:**
```json
{
    "success": true,
    "message": "Database connection successful!",
    "database": "u202859753_exploreheroes",
    "host": "127.0.0.1",
    "username": "u202859753_exploreuser",
    "total_tables": 50,
    "tables": [
        "users",
        "bravo_bookings",
        "bravo_tours",
        "bravo_hotels",
        "..."
    ],
    "table_row_counts": {
        "users": 150,
        "bravo_bookings": 320,
        "bravo_tours": 45,
        "..."
    }
}
```

**What it shows:**
- Database name
- Total tables count
- List of all tables
- Row count for each table

---

### 3. Users Data
```
Method: GET
URL: http://localhost:8000/api/test/users
```

**Optional Query Parameters:**
- `limit` - Number of records (default: 10)
  - Example: `http://localhost:8000/api/test/users?limit=20`

**Response:**
```json
{
    "success": true,
    "message": "Users fetched successfully",
    "total_users": 150,
    "showing": 10,
    "limit": 10,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2024-01-15 10:30:00",
            "updated_at": "2024-01-15 10:30:00"
        }
    ]
}
```

---

### 4. Bookings Data
```
Method: GET
URL: http://localhost:8000/api/test/bookings
```

**Optional Query Parameters:**
- `limit` - Number of records (default: 10)
  - Example: `http://localhost:8000/api/test/bookings?limit=5`

**Response:**
```json
{
    "success": true,
    "message": "Bookings fetched successfully",
    "total_bookings": 320,
    "showing": 10,
    "limit": 10,
    "data": [
        {
            "id": 1,
            "code": "BK001",
            "status": "completed",
            "total": 250.00,
            "...": "..."
        }
    ]
}
```

---

### 5. Any Table Data (Dynamic)
```
Method: GET
URL: http://localhost:8000/api/test/table/{TABLE_NAME}
```

**Examples:**
```
http://localhost:8000/api/test/table/bravo_tours
http://localhost:8000/api/test/table/bravo_hotels
http://localhost:8000/api/test/table/bravo_flights
http://localhost:8000/api/test/table/bravo_cars
http://localhost:8000/api/test/table/bravo_boats
http://localhost:8000/api/test/table/bravo_events
http://localhost:8000/api/test/table/bravo_spaces
```

**Optional Query Parameters:**
- `limit` - Number of records (default: 10)

**Response:**
```json
{
    "success": true,
    "table": "bravo_tours",
    "total_rows": 45,
    "showing": 10,
    "limit": 10,
    "columns": [
        {
            "Field": "id",
            "Type": "bigint(20)",
            "Null": "NO",
            "Key": "PRI"
        }
    ],
    "data": [
        {
            "id": 1,
            "title": "Amazing Tour",
            "price": 150.00,
            "...": "..."
        }
    ]
}
```

---

## 🎯 Common Tables to Test

### Booking System Tables
```
bravo_bookings          - All bookings
bravo_booking_meta      - Booking metadata
bravo_booking_payments  - Payment records
```

### Service Tables
```
bravo_tours            - Tour packages
bravo_hotels           - Hotels
bravo_flights          - Flights
bravo_cars             - Car rentals
bravo_boats            - Boat tours
bravo_events           - Events
bravo_spaces           - Space rentals
```

### User & Vendor Tables
```
users                  - All users
bravo_vendors          - Vendor profiles
user_meta              - User metadata
```

### Location & Media
```
bravo_locations        - Locations
media_files            - Uploaded files
```

### Reviews & Coupons
```
bravo_review           - Reviews
bravo_coupons          - Discount coupons
```

---

## 📱 Postman Testing Steps

### Step 1: Create New Request
1. Open Postman
2. Click "New" → "Request"
3. Name: "Test Health Check"
4. Save to collection

### Step 2: Configure Request
1. Method: **GET**
2. URL: `http://localhost:8000/api/test/health`
3. Click **Send**

### Step 3: View Response
- Status: `200 OK`
- Body: JSON response with success message

### Step 4: Test Other Endpoints
Repeat for:
- Database info
- Users data
- Bookings data
- Specific tables

---

## 🔍 Testing Examples

### Example 1: Get First 5 Users
```
GET http://localhost:8000/api/test/users?limit=5
```

### Example 2: Get All Tours
```
GET http://localhost:8000/api/test/table/bravo_tours?limit=20
```

### Example 3: Check Database Tables
```
GET http://localhost:8000/api/test/database
```
This shows ALL tables and their row counts!

### Example 4: Get Hotel Data
```
GET http://localhost:8000/api/test/table/bravo_hotels
```

---

## ⚡ Quick Tips

### Urdu Instructions:
1. **Postman kholo** aur new request banao
2. **Method** GET rakho
3. **URL** copy paste karo (upar se)
4. **Send** button dabao
5. **Response** neeche dekho

### Database Tables Dekhne K Liye:
```
http://localhost:8000/api/test/database
```
Ye sab tables ki list aur unme kitne records hain dikhaega!

### Kisi Bhi Table Ka Data Dekhne K Liye:
```
http://localhost:8000/api/test/table/TABLE_NAME_YAHAN_LIKHO
```

---

## 🛠️ Troubleshooting

### Error: "Connection refused"
**Solution:**
```powershell
# Server chalu karo
C:\xampp\php\php.exe artisan serve
```

### Error: "Database connection failed"
**Solution:**
```powershell
# MySQL XAMPP se start karo
# XAMPP Control Panel → MySQL → Start
```

### Error: "Table not found"
**Solution:**
- Pehle `/api/test/database` endpoint hit karo
- Response me table names dekho
- Exact table name use karo

---

## 📊 Response Codes

- **200 OK** - Success, data mil gaya
- **404 Not Found** - Table ya endpoint nahi mila
- **500 Internal Server Error** - Database error ya code issue

---

## 🎊 All Set!

Ab tum Postman se apne database ka data dekh sakte ho!

**Database:** `u202859753_exploreheroes`
**Host:** `127.0.0.1`
**Username:** `u202859753_exploreuser`

Server chal raha hai: **http://localhost:8000**

Happy Testing! 🚀
