<div align="center">

# ⚡ Zodiac Store — Laravel API Backend

[![Laravel](https://img.shields.io/badge/Laravel-11%2F12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Sanctum](https://img.shields.io/badge/Laravel_Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/sanctum)
[![Bakong KHQR](https://img.shields.io/badge/Bakong-KHQR_API-ED1C24?style=for-the-badge)](https://bakong.nbc.gov.kh/)
[![ABA PayWay](https://img.shields.io/badge/ABA-PayWay_Gateway-005696?style=for-the-badge)](https://www.ababank.com/)

[![Live API](https://img.shields.io/badge/⚡_Live_API-Render_Backend-46E3B7?style=for-the-badge&logo=render&logoColor=black)](https://zodiac-backend-6vfn.onrender.com)

<br/><br/>

<p align="center">
  A RESTful e-commerce API backend built with Laravel, featuring Sanctum token authentication, dual payment processing with <b>Bakong KHQR</b> & <b>ABA PayWay</b>, Gmail SMTP OTP password recovery, Telegram order alerts, and an administrative control suite.
</p>

</div>


---

## 📑 Table of Contents

- [✨ Architecture & Core Capabilities](#-architecture--core-capabilities)
- [🛠️ Tech Stack & Requirements](#️-tech-stack--requirements)
- [⚡ Quick Start & Installation](#-quick-start--installation)
- [⚙️ Environment Variables Configuration](#️-environment-variables-configuration)
- [🗄️ Database & Eloquent Models](#️-database--eloquent-models)
- [📡 API Endpoint Reference](#-api-endpoint-reference)
  - [1. Authentication & Password Reset](#1-authentication--password-reset)
  - [2. Products & Catalog](#2-products--catalog)
  - [3. Shopping Cart](#3-shopping-cart)
  - [4. Orders & Checkout](#4-orders--checkout)
  - [5. Bakong KHQR Payment Gateway](#5-bakong-khqr-payment-gateway)
  - [6. ABA PayWay Payment Gateway](#6-aba-payway-payment-gateway)
  - [7. Contact Submissions](#7-contact-submissions)
  - [8. Admin Control Suite](#8-admin-control-suite)
- [🧪 Running Automated Tests](#-running-automated-tests)
- [🚀 Production Deployment & Optimization](#-production-deployment--optimization)

---

## ✨ Architecture & Core Capabilities

- **🔐 Token Authentication & Authorization**: Laravel Sanctum bearer tokens with role-based middleware (`admin`).
- **📧 OTP Email Recovery**: 6-digit OTP verification sent via Gmail SMTP for secure password resetting.
- **🇰🇭 Bakong KHQR Payment Engine**:
  - Dynamic KHQR code generation adhering to NBC specifications.
  - MD5 transaction hash tracking and real-time status check.
  - Development simulation mode for instant sandbox testing.
- **💳 ABA PayWay Gateway Integration**:
  - HMAC-SHA512 signature creation for secure checkout requests.
  - Check-transaction status polling and public callback webhook handler.
- **📱 Telegram Notifications**: Automated order alerts sent directly to store admins via Telegram Bot API.
- **🛒 Synchronized Cart & Order Management**: Database persistence for authenticated user carts and comprehensive order status tracking.
- **📊 Admin Dashboard Operations**: Real-time sales metrics, order fulfillment management, product inventory CRUD, and user directory management.

---

## 🛠️ Tech Stack & Requirements

- **PHP**: `^8.2` (PHP 8.3 recommended)
- **Framework**: Laravel 11 / 12 (`laravel/framework ^13.8`)
- **Authentication**: Laravel Sanctum (`laravel/sanctum ^4.0`)
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Dependency Manager**: Composer 2.x
- **Testing**: PHPUnit 12.x / Pest

---

## ⚡ Quick Start & Installation

### 1. Clone & Navigate to Backend
```bash
cd Backend
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Setup Environment File
```bash
cp .env.example .env
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Configure Database
Update your database credentials in `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e-commerce
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run Migrations & Seeders
```bash
# Run migrations
php artisan migrate

# (Optional) Import full pre-configured database from root directory
mysql -u root -p e-commerce < ../database/zodiac_store.sql
```

### 7. Start the Laravel Server
```bash
php artisan serve
```

The API will be accessible at: **`http://localhost:8000/api`**

---

## ⚙️ Environment Variables Configuration

Here is a full breakdown of the necessary environment variables in `Backend/.env`:

```ini
APP_NAME="Zodiac Store"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

# Database Settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e-commerce
DB_USERNAME=root
DB_PASSWORD=

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# CORS & Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:5173

# Gmail SMTP for OTP Password Reset
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD="your_16_digit_app_password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your_gmail@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"

# ABA PayWay Sandbox Settings
ABA_PAYWAY_MERCHANT_ID=ec477686
ABA_PAYWAY_API_KEY=54d4636c94fab5d545fb6ac150890cebb6edc47a
ABA_PAYWAY_PURCHASE_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase
ABA_PAYWAY_CHECK_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction-2
ABA_PAYWAY_RETURN_URL=http://localhost:8000/payway/callback
ABA_PAYWAY_CURRENCY=USD

# Bakong KHQR Settings
BAKONG_ACCOUNT_ID=sopheareaksa_pheak@bkrt
BAKONG_ACCOUNT_TYPE=individual
BAKONG_MERCHANT_NAME="Sopheareaksa Pheak"
BAKONG_MERCHANT_CITY="Phnom Penh"
BAKONG_API_URL=https://api-bakong.nbc.gov.kh
BAKONG_API_TOKEN=your_jwt_bakong_token

# Telegram Bot (Order Notification Dispatch)
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_CHAT_ID=your_telegram_chat_id
```

---

## 🗄️ Database & Eloquent Models

The backend leverages Eloquent ORM with defined relationships:

```
User (1) ────┬────< Order (N) ────< OrderItem (N) >──── Product (1)
             │
             ├────< CartItem (N) >──── Product (1)
             │
             └────< Payment (N)
```

- **`User`** (`app/Models/User.php`): Handles authentication, password hashing, and user profile data.
- **`Product`** (`app/Models/Product.php`): Stores name, category, price, discount, special offer flags, and up to 4 image paths.
- **`CartItem`** (`app/Models/CartItem.php`): Maintains user cart items with quantity and product association.
- **`Order`** (`app/Models/Order.php`): Tracks total cost, shipping details, status (`on_hold`, `pending`, `processing`, `delivered`), and payment transaction IDs.
- **`OrderItem`** (`app/Models/OrderItem.php`): Snapshot of ordered items with name, price, quantity, and image at time of purchase.
- **`Payment`** (`app/Models/Payment.php`): Stores payment records and transaction hashes.
- **`Contact`** (`app/Models/Contact.php`): Captures inquiries submitted via the customer contact form.

---

## 📡 API Endpoint Reference

### 1. Authentication & Password Reset

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/register` | Public | Register a new user account (`name`, `email`, `password`) |
| `POST` | `/api/login` | Public | Login with email and password, returns Sanctum token |
| `POST` | `/api/forgot-password/send-otp` | Public | Send 6-digit verification code to email |
| `POST` | `/api/forgot-password/verify-otp` | Public | Verify the 6-digit OTP code |
| `POST` | `/api/forgot-password/reset` | Public | Reset password using verified OTP token |
| `POST` | `/api/logout` | `auth:sanctum` | Revoke current access token |
| `GET` | `/api/me` | `auth:sanctum` | Return current user details & role |
| `PUT` | `/api/profile` | `auth:sanctum` | Update user name and phone |
| `PUT` | `/api/change-password` | `auth:sanctum` | Change current password |

---

### 2. Products & Catalog

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/products` | Public | List all active products |
| `GET` | `/api/products/featured` | Public | List products marked as featured |
| `GET` | `/api/products/category/{slug}` | Public | List products by category (e.g. `apples`, `samsungs`) |
| `GET` | `/api/products/search?q={query}` | Public | Search products by name and category keyword |
| `GET` | `/api/products/{id}` | Public | Get full single product details |

---

### 3. Shopping Cart

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/api/cart` | `auth:sanctum` | Retrieve all cart items for authenticated user |
| `POST` | `/api/cart` | `auth:sanctum` | Add product to cart (`product_id`, `quantity`) |
| `PUT` | `/api/cart/{id}` | `auth:sanctum` | Update quantity for specific cart item |
| `DELETE`| `/api/cart/{id}` | `auth:sanctum` | Remove single item from cart |
| `DELETE`| `/api/cart` | `auth:sanctum` | Clear entire cart |

---

### 4. Orders & Checkout

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/orders` | `auth:sanctum` | Create new order with cart items and shipping details |
| `GET` | `/api/orders` | `auth:sanctum` | List all orders belonging to authenticated user |
| `GET` | `/api/orders/{id}` | `auth:sanctum` | Retrieve full order details including line items |

---

### 5. Bakong KHQR Payment Gateway

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/bakong/generate-khqr` | Public / Sanctum | Generate dynamic Bakong KHQR code and MD5 string |
| `POST` | `/api/bakong/check-payment` | Public / Sanctum | Query Bakong open API to check transaction status |
| `POST` | `/api/bakong/simulate-payment` | Public / Sanctum | Instantly mark order as paid (for development/testing) |
| `POST` | `/api/bakong/verify-khqr` | Public / Sanctum | Validate KHQR string structure |

---

### 6. ABA PayWay Payment Gateway

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/payments/aba/create` | `auth:sanctum` | Generate HMAC-SHA512 hash & purchase URL |
| `GET` | `/api/payments/aba/status/{order}` | `auth:sanctum` | Query ABA PayWay transaction check API |
| `POST` | `/api/payments/aba/simulate/{order}` | `auth:sanctum` | Simulate successful payment callback for order |
| `POST` | `/api/payments/aba/callback` | Public (Webhook) | Webhook endpoint called by ABA PayWay gateway |

---

### 7. Contact Submissions

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/contact` | `auth:sanctum` | Store user inquiry (`name`, `email`, `phone`, `message`) |

---

### 8. Admin Control Suite

*Requires Bearer Token with `is_admin = 1`.*

| Method | Endpoint | Middleware | Description |
|---|---|---|---|
| `GET` | `/api/admin/dashboard` | `auth:sanctum`, `admin` | Summary statistics (revenue, total orders, users) |
| `GET` | `/api/admin/users` | `auth:sanctum`, `admin` | Directory of all registered users |
| `GET` | `/api/admin/orders` | `auth:sanctum`, `admin` | List all customer orders across store |
| `GET` | `/api/admin/products` | `auth:sanctum`, `admin` | List all products with management metadata |
| `POST` | `/api/admin/products` | `auth:sanctum`, `admin` | Create new product with image uploads |
| `PUT` | `/api/admin/products/{id}` | `auth:sanctum`, `admin` | Update product details |
| `DELETE`| `/api/admin/products/{id}` | `auth:sanctum`, `admin` | Delete product from catalog |

---

## 🧪 Running Automated Tests

Run backend feature and unit test suites with PHPUnit / Artisan:

```bash
# Run all test suites
php artisan test

# Run Bakong KHQR payment feature tests specifically
php artisan test --filter=BakongPaymentTest
```

---

## 🚀 Production Deployment & Optimization

When deploying to staging or production environments, execute the following commands to cache configuration and boost API response speeds:

```bash
# Cache configuration files
php artisan config:cache

# Cache registered API routes
php artisan route:cache

# Cache compiled Blade email templates
php artisan view:cache

# Optimize class autoloader
composer install --optimize-autoloader --no-dev
```