<div align="center">

# 🛒 Gerai Jasa — Backend API

**Laravel REST API for a multi-vendor service booking platform**
<br/>
Booking online untuk salon, klinik, bengkel & jasa lainnya — pasar Indonesia.

<p>
  <img src="https://img.shields.io/badge/status-Selesai-brightgreen?style=for-the-badge" alt="Status" />
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL" />
  <img src="https://img.shields.io/badge/Redis-7-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis" />
  <img src="https://img.shields.io/badge/license-MIT-1E6F5C?style=for-the-badge" alt="License" />
</p>

</div>

---

## 📖 Overview

The **Gerai Jasa Backend** is the REST API powering the entire Gerai Jasa platform — it serves the customer **mobile app**, the admin & vendor **web dashboard**, and handles the full booking lifecycle: discovery, real-time slot booking, payments, and notifications.

Built on **Laravel 11 (PHP 8.3)** with a clean, layered architecture and a battle-tested booking engine that prevents double-booking under concurrent load.

---

## 🧱 Tech Stack

| Layer | Technology |
|-------|------------|
| **Framework** | Laravel 11 · PHP 8.3 |
| **Database** | PostgreSQL 16 (UUID PKs, JSONB metadata) |
| **Cache / Queue** | Redis 7 · Laravel Horizon |
| **Auth** | Laravel Sanctum (token-based) |
| **Payments** | Midtrans Snap · Xendit Invoice · COD |
| **Notifications** | FCM (push) · Fonnte (WhatsApp) · Mailgun (email) |

## 🏛️ Architecture

- **Repository Pattern + Service Layer** — controllers stay thin, business logic lives in services
- **API Resources** for consistent response formatting
- **Form Request** classes for validation
- **UUID primary keys** & **soft deletes** on all models
- Every response follows one envelope:

```json
{ "success": true, "message": "...", "data": { }, "meta": { } }
```

---

## ✨ Features

- ✅ OTP-based phone login (customers) + email/password login (admin/vendor)
- ✅ Role-based access control — `customer`, `vendor`, `admin`
- ✅ Vendor & category management
- ✅ Service & weekly schedule management
- ✅ Automatic time-slot generation (60 days ahead)
- ✅ Booking engine with state machine — `pending → confirmed → in_progress → completed`
- ✅ **Pessimistic locking** on slot booking to prevent race conditions
- ✅ Payment integration — Midtrans, Xendit, COD
- ✅ Webhook endpoints with signature verification
- ✅ Queued notifications — push, WhatsApp, email

### Booking State Machine

```
pending → confirmed → in_progress → completed
              ↓             ↓
          cancelled     cancelled

COD: pending → confirmed → awaiting_payment → completed
```

---

## 🗂️ Project Structure

```
app/
├── Console/Commands/        # Artisan commands (slot generation)
├── Http/
│   ├── Controllers/Api/V1/  # Thin controllers
│   ├── Requests/            # Form Request validation
│   ├── Resources/           # API Resources
│   └── Middleware/          # Role middleware
├── Jobs/                    # Queue jobs (notifications, reminders)
├── Models/                  # Eloquent models (UUID, soft deletes)
├── Providers/               # Service providers (repository bindings)
├── Repositories/
│   ├── Contracts/           # Repository interfaces
│   └── Eloquent/            # Eloquent implementations
├── Services/                # Business logic layer
└── Traits/                  # ApiResponseTrait
```

**Database** — 12 tables: `users`, `categories`, `vendors`, `vendor_photos`, `services`, `schedules`, `time_slots`, `bookings`, `payments`, `reviews`, `notifications`, `otps`.

---

## 🔌 API Endpoints

<details>
<summary><strong>Auth</strong></summary>

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/request-otp` | Request OTP via phone |
| POST | `/api/v1/auth/verify-otp` | Verify OTP & get token |
| POST | `/api/v1/auth/register` | Register new user |
| POST | `/api/v1/auth/login` | Login (email/password) |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Current user profile |

</details>

<details>
<summary><strong>Public</strong></summary>

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/vendors` | List vendors (filter: category, city, rating) |
| GET | `/api/v1/vendors/{slug}` | Vendor detail + services |
| GET | `/api/v1/vendors/{id}/slots` | Available slots (query: service_id, date) |

</details>

<details>
<summary><strong>Customer</strong> (auth required)</summary>

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/bookings` | Create booking |
| GET | `/api/v1/bookings` | My bookings (paginated) |
| GET | `/api/v1/bookings/{id}` | Booking detail |
| PATCH | `/api/v1/bookings/{id}/cancel` | Cancel booking |

</details>

<details>
<summary><strong>Vendor</strong> (auth + role:vendor) & <strong>Webhooks</strong></summary>

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/vendor/bookings` | Incoming bookings |
| PATCH | `/api/v1/vendor/bookings/{id}/confirm` | Confirm booking |
| PATCH | `/api/v1/vendor/bookings/{id}/complete` | Complete booking |
| GET/PUT | `/api/v1/vendor/schedules` | Get/update schedules |
| CRUD | `/api/v1/vendor/services` | Manage services |
| POST | `/api/v1/webhooks/midtrans` | Midtrans payment callback |
| POST | `/api/v1/webhooks/xendit` | Xendit payment callback |

</details>

---

## 🚀 Getting Started

```bash
# Clone
git clone https://github.com/Naufall18/Gerai-Jasa-backend.git
cd Gerai-Jasa-backend

# Install dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate
# → configure PostgreSQL, Redis, payment & notification keys in .env

# Database
php artisan migrate --seed

# Generate time slots
php artisan slots:generate

# Run
php artisan serve
```

> See `.env.example` for the full list of variables (PostgreSQL, Redis, Midtrans/Xendit, FCM, Fonnte, Mailgun, S3 storage).

---

## 🧩 Part of the Gerai Jasa Platform

| Repository | Stack | Role |
|------------|-------|------|
| **Gerai-Jasa-backend** *(this repo)* | Laravel 11 | REST API & booking engine |
| [Gerai-Jasa-web](https://github.com/Naufall18/Gerai-Jasa-web) | React + TypeScript | Admin & vendor dashboard |
| [Gerai-Jasa-mobile](https://github.com/Naufall18/Gerai-Jasa-mobile) | Flutter | Customer app |

---

## 📄 License

Released under the **MIT License**.

<div align="center">
<br/>
Built by <a href="https://github.com/Naufall18">Naufal Dwi Arifianto</a> · <a href="https://naufall18.github.io/portofolio/">Portfolio</a>
</div>
