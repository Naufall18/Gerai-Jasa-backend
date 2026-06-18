# Gerai Jasa Backend API

Laravel 11 REST API for **Gerai Jasa** — a multi-vendor booking platform (salons, clinics, workshops, etc.) for the Indonesian market.

## Tech Stack

- **PHP 8.3** + **Laravel 11**
- **PostgreSQL 16** (UUID primary keys, JSONB metadata)
- **Redis 7** (cache, queues, sessions)
- **Laravel Sanctum** (JWT-based auth)
- **Laravel Horizon** (queue monitoring)

## Architecture

- **Repository Pattern** + **Service Layer** — business logic never in controllers
- **API Resources** for response formatting
- **Form Request** classes for validation
- All responses follow: `{ "success": true, "message": "...", "data": {...}, "meta": {...} }`

## Features

- ✅ OTP-based phone login (customers) + email/password login (admin/vendor)
- ✅ Role-based access: `customer`, `vendor`, `admin`
- ✅ Vendor management with categories
- ✅ Service & schedule management
- ✅ Time slot generation (auto-generate 60 days ahead)
- ✅ Booking engine with state machine (pending → confirmed → in_progress → completed)
- ✅ Pessimistic locking for slot booking (race condition prevention)
- ✅ Payment integration (Midtrans Snap, Xendit Invoice, COD)
- ✅ Webhook endpoints with signature verification
- ✅ Notification jobs (push, WhatsApp, email)
- ✅ Soft deletes on all models

## Folder Structure

```
app/
├── Console/Commands/       # Artisan commands (slot generation)
├── Http/
│   ├── Controllers/Api/V1/ # Thin controllers
│   ├── Requests/           # Form Request validation
│   ├── Resources/          # API Resources
│   └── Middleware/          # Role middleware
├── Jobs/                   # Queue jobs (notifications, reminders)
├── Models/                 # Eloquent models (UUID, soft deletes)
├── Providers/              # Service providers (repository bindings)
├── Repositories/
│   ├── Contracts/          # Repository interfaces
│   └── Eloquent/           # Eloquent implementations
├── Services/               # Business logic layer
└── Traits/                 # ApiResponseTrait
```

## Database Schema

12 tables: `users`, `categories`, `vendors`, `vendor_photos`, `services`, `schedules`, `time_slots`, `bookings`, `payments`, `reviews`, `notifications`, `otps`

All IDs are UUIDs. All tables have soft deletes and UTC ISO 8601 timestamps.

## API Endpoints

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/request-otp` | Request OTP via phone |
| POST | `/api/v1/auth/verify-otp` | Verify OTP & get token |
| POST | `/api/v1/auth/register` | Register new user |
| POST | `/api/v1/auth/login` | Login (email/password) |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Current user profile |

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/vendors` | List vendors (filter: category, city, rating) |
| GET | `/api/v1/vendors/{slug}` | Vendor detail + services |
| GET | `/api/v1/vendors/{id}/slots` | Available slots (query: service_id, date) |

### Customer (auth required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/bookings` | Create booking |
| GET | `/api/v1/bookings` | My bookings (paginated) |
| GET | `/api/v1/bookings/{id}` | Booking detail |
| PATCH | `/api/v1/bookings/{id}/cancel` | Cancel booking |

### Vendor (auth + role:vendor)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/vendor/bookings` | Incoming bookings |
| PATCH | `/api/v1/vendor/bookings/{id}/confirm` | Confirm booking |
| PATCH | `/api/v1/vendor/bookings/{id}/complete` | Complete booking |
| GET/PUT | `/api/v1/vendor/schedules` | Get/update schedules |
| CRUD | `/api/v1/vendor/services` | Manage services |

### Webhooks (no auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/webhooks/midtrans` | Midtrans payment callback |
| POST | `/api/v1/webhooks/xendit` | Xendit payment callback |

## Setup

```bash
# Clone
git clone https://github.com/Naufall18/geraijasa-backend.git
cd geraijasa-backend

# Install dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Configure .env with your PostgreSQL & Redis credentials

# Database
php artisan migrate
php artisan db:seed

# Generate time slots
php artisan slots:generate

# Run
php artisan serve
```

## Environment Variables

See `.env.example` for all required variables including:
- PostgreSQL connection
- Redis connection
- Midtrans & Xendit API keys
- FCM, Fonnte (WhatsApp), Mailgun credentials
- S3-compatible storage config

## Booking State Machine

```
pending → confirmed → in_progress → completed
               ↓               ↓
          cancelled        cancelled

COD: pending → confirmed → awaiting_payment → completed
```

## Related Repositories

- **Web Dashboard**: [geraijasa-web](https://github.com/Naufall18/geraijasa-web) — React 18 + TypeScript (Admin & Vendor Dashboard)
- **Mobile App**: [geraijasa-mobile](https://github.com/Naufall18/geraijasa-mobile) — Flutter 3.x (Customer App)

## License

MIT