# ⚡ Next.js Integration Guide - Booking Core API

## 🎯 Overview

Is guide mein aap seekhenge k Laravel backend ko Next.js frontend se kaise connect karein.

---

## 📁 Next.js Project Setup

### **STEP 1: Next.js App Banana**

```powershell
# Next.js app create karein
npx create-next-app@latest booking-frontend
cd booking-frontend
```

Setup options:
- ✅ TypeScript? **Yes** (recommended)
- ✅ ESLint? **Yes**
- ✅ Tailwind CSS? **Yes** (recommended)
- ✅ App Router? **Yes** (recommended)

### **STEP 2: Zaroori Packages Install Karein**

```powershell
# API calls k liye
npm install axios

# State management (optional but recommended)
npm install zustand

# Forms k liye
npm install react-hook-form

# Date handling
npm install date-fns
```

---

## 🔧 API Configuration

### **Create API Client** (`lib/api.ts`)

```typescript
import axios from 'axios';

// Laravel backend URL
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// Axios instance
export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - Token add karna
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Error handling
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired - logout user
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

---

## 🔐 Authentication Services

### **Auth Service** (`services/auth.ts`)

```typescript
import { api } from '@/lib/api';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export const authService = {
  // Login
  async login(credentials: LoginCredentials) {
    const response = await api.post('/auth/login', credentials);
    if (response.access_token) {
      localStorage.setItem('auth_token', response.access_token);
    }
    return response;
  },

  // Register
  async register(data: RegisterData) {
    const response = await api.post('/auth/register', data);
    if (response.access_token) {
      localStorage.setItem('auth_token', response.access_token);
    }
    return response;
  },

  // Logout
  async logout() {
    await api.post('/auth/logout');
    localStorage.removeItem('auth_token');
  },

  // Get current user
  async getCurrentUser() {
    return api.get('/auth/me');
  },

  // Change password
  async changePassword(oldPassword: string, newPassword: string) {
    return api.post('/auth/change-password', {
      current_password: oldPassword,
      password: newPassword,
      password_confirmation: newPassword,
    });
  },
};
```

---

## 🏨 Service APIs

### **Tour/Hotel/Flight Services** (`services/booking.ts`)

```typescript
import { api } from '@/lib/api';

export interface SearchParams {
  location?: string;
  date_from?: string;
  date_to?: string;
  adults?: number;
  children?: number;
  page?: number;
}

export const bookingService = {
  // Search tours
  async searchTours(params: SearchParams) {
    return api.get('/tour/search', { params });
  },

  // Get tour details
  async getTourDetail(id: number) {
    return api.get(`/tour/detail/${id}`);
  },

  // Check availability
  async checkAvailability(type: string, id: number, date: string) {
    return api.get(`/${type}/availability/${id}`, {
      params: { date }
    });
  },

  // Search hotels
  async searchHotels(params: SearchParams) {
    return api.get('/hotel/search', { params });
  },

  // Search flights
  async searchFlights(params: SearchParams) {
    return api.get('/flight/search', { params });
  },

  // Get configurations
  async getConfigs() {
    return api.get('/configs');
  },

  // Get home page layout
  async getHomeLayout() {
    return api.get('/home-page');
  },

  // Add to cart
  async addToCart(data: any) {
    return api.post('/booking/addToCart', data);
  },

  // Checkout
  async checkout(data: any) {
    return api.post('/booking/doCheckout', data);
  },

  // Get booking details
  async getBooking(code: string) {
    return api.get(`/booking/${code}`);
  },
};
```

---

## 📍 Location & Review Services

### **Location Service** (`services/location.ts`)

```typescript
import { api } from '@/lib/api';

export const locationService = {
  // Search locations
  async searchLocations(keyword: string) {
    return api.get('/locations', {
      params: { s: keyword }
    });
  },

  // Get location details
  async getLocationDetail(id: number) {
    return api.get(`/location/${id}`);
  },
};
```

### **Review Service** (`services/review.ts`)

```typescript
import { api } from '@/lib/api';

export interface ReviewData {
  title: string;
  content: string;
  rate_number: number; // 1-5
}

export const reviewService = {
  // Write review
  async writeReview(type: string, id: number, data: ReviewData) {
    return api.post(`/${type}/write-review/${id}`, data);
  },
};
```

---

## 👤 User Services

### **User Service** (`services/user.ts`)

```typescript
import { api } from '@/lib/api';

export const userService = {
  // Get booking history
  async getBookingHistory() {
    return api.get('/user/booking-history');
  },

  // Add to wishlist
  async addToWishlist(serviceId: number, serviceType: string) {
    return api.post('/user/wishlist', {
      service_id: serviceId,
      service_type: serviceType,
    });
  },

  // Get wishlist
  async getWishlist() {
    return api.get('/user/wishlist');
  },
};
```

---

## 🎨 Example: Search Page Component

### **Tour Search Page** (`app/tours/page.tsx`)

```typescript
'use client';

import { useState, useEffect } from 'react';
import { bookingService } from '@/services/booking';

export default function ToursPage() {
  const [tours, setTours] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchParams, setSearchParams] = useState({
    location: '',
    date_from: '',
    date_to: '',
    adults: 1,
  });

  useEffect(() => {
    loadTours();
  }, []);

  const loadTours = async () => {
    try {
      setLoading(true);
      const response = await bookingService.searchTours(searchParams);
      setTours(response.data || []);
    } catch (error) {
      console.error('Error loading tours:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    await loadTours();
  };

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">Search Tours</h1>
      
      {/* Search Form */}
      <form onSubmit={handleSearch} className="mb-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <input
            type="text"
            placeholder="Location"
            value={searchParams.location}
            onChange={(e) => setSearchParams({...searchParams, location: e.target.value})}
            className="border rounded px-4 py-2"
          />
          <input
            type="date"
            value={searchParams.date_from}
            onChange={(e) => setSearchParams({...searchParams, date_from: e.target.value})}
            className="border rounded px-4 py-2"
          />
          <input
            type="date"
            value={searchParams.date_to}
            onChange={(e) => setSearchParams({...searchParams, date_to: e.target.value})}
            className="border rounded px-4 py-2"
          />
          <button type="submit" className="bg-blue-600 text-white rounded px-6 py-2">
            Search
          </button>
        </div>
      </form>

      {/* Results */}
      {loading ? (
        <p>Loading...</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {tours.map((tour: any) => (
            <div key={tour.id} className="border rounded-lg p-4 hover:shadow-lg">
              <img src={tour.image_url} alt={tour.title} className="w-full h-48 object-cover rounded mb-4" />
              <h3 className="text-xl font-semibold mb-2">{tour.title}</h3>
              <p className="text-gray-600 mb-4">{tour.location?.name}</p>
              <div className="flex justify-between items-center">
                <span className="text-2xl font-bold text-blue-600">${tour.price}</span>
                <a href={`/tours/${tour.id}`} className="bg-blue-600 text-white px-4 py-2 rounded">
                  View Details
                </a>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

---

## 🔐 Environment Variables

### **Create `.env.local`**

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_NAME=Booking Core
```

---

## 🚀 Running Both Servers

### **Terminal 1: Laravel Backend**
```powershell
cd "c:\Users\Nomi Malik\Downloads\exploreheroes.com\public_html"
php artisan serve
# Runs on: http://localhost:8000
```

### **Terminal 2: Next.js Frontend**
```powershell
cd booking-frontend
npm run dev
# Runs on: http://localhost:3000
```

---

## ✅ Testing Integration

1. Laravel backend: http://localhost:8000/api/configs
2. Next.js frontend: http://localhost:3000
3. Test API calls from Next.js console

---

## 🎯 Important Notes

### **CORS Issues Fix** (Agar errors aayein)

Laravel mein `config/cors.php` update karein:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

---

## 📚 Additional Resources

- Service Types: `tour`, `hotel`, `flight`, `car`, `boat`, `event`, `space`
- Authentication: JWT tokens
- File uploads: Use FormData for images
- Pagination: Laravel automatic pagination support

---

## 🎉 Success Checklist

- ✅ Laravel backend running
- ✅ Next.js frontend running
- ✅ API connection working
- ✅ Authentication working
- ✅ Search & booking working
- ✅ User dashboard working

