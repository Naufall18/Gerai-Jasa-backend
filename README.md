# Gerai Jasa Backend API

Laravel 11 REST API untuk **Gerai Jasa** — platform booking multi-vendor (salon, klinik, bengkel, dll) untuk pasar Indonesia.

**Desain:** Pine & Amber (#1E6F5C primary, #F2A444 accent)

---

## Tech Stack

- **PHP 8.3** + **Laravel 11**
- **PostgreSQL 16** (UUID primary keys, JSONB metadata)
- **Redis 7** (cache, queues, sessions)
- **Laravel Sanctum** (token-based auth)
- **Laravel Horizon** (queue monitoring)
- **Intervention Image** (GD driver)

---

## Arsitektur

- **Repository Pattern** + **Service Layer** — business logic tidak pernah di controller
- **API Resources** untuk response formatting
- **Form Request** classes untuk validation
- Semua response mengikuti format: `{ success, message, data, meta }`

---

## Fitur

- ✅ OTP-based phone login + email/password login
- ✅ Role-based access: `customer`, `vendor`, `admin`
- ✅ Vendor management dengan kategori
- ✅ Service & schedule management
- ✅ Time slot generation (auto-generate 60 hari ke depan)
- ✅ Booking engine dengan state machine (locking untuk race condition)
- ✅ Payment integration (Midtrans Snap, COD)
- ✅ Webhook endpoints dengan signature verification
- ✅ Notification jobs (FCM push, WhatsApp)
- ✅ Filter & pagination di endpoint vendor bookings
- ✅ Kalender booking dengan date range filter
- ✅ Soft deletes di semua model

---

## Struktur Folder

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
├── Providers/              # Service providers
├── Repositories/
│   ├── Contracts/          # Repository interfaces
│   └── Eloquent/           # Implementasi Eloquent
├── Services/               # Business logic layer
└── Traits/                 # ApiResponseTrait
```

### Database (12 tabel)

`users`, `categories`, `vendors`, `vendor_photos`, `services`, `schedules`, `time_slots`, `bookings`, `payments`, `reviews`, `notifications`, `otps`

Semua ID menggunakan UUID. Semua tabel memiliki soft deletes dan timestamp UTC ISO 8601.

---

## API Endpoints

### Auth
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/auth/request-otp` | Request OTP via WhatsApp |
| POST | `/api/v1/auth/verify-otp` | Verifikasi OTP & dapat token |
| POST | `/api/v1/auth/register` | Register user baru |
| POST | `/api/v1/auth/login` | Login email/password |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Profil user saat ini |

### Public
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/vendors` | List vendor (filter: category, city, rating) |
| GET | `/api/v1/vendors/{slug}` | Detail vendor + layanan |
| GET | `/api/v1/vendors/{vendorId}/services` | Layanan vendor |
| GET | `/api/v1/vendors/{vendorId}/slots` | Slot tersedia (query: service_id, date) |

### Customer (auth required)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/bookings` | Buat booking baru |
| GET | `/api/v1/bookings` | Booking saya (paginated) |
| GET | `/api/v1/bookings/{id}` | Detail booking |
| PATCH | `/api/v1/bookings/{id}/cancel` | Batalkan booking |
| POST | `/api/v1/bookings/{id}/review` | Kirim ulasan |
| POST | `/api/v1/bookings/{id}/pay` | Inisiasi pembayaran Midtrans |

### Vendor (auth + role:vendor)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/vendor/bookings` | Booking masuk (filter: status, date range) |
| PATCH | `/api/v1/vendor/bookings/{id}/confirm` | Konfirmasi booking |
| PATCH | `/api/v1/vendor/bookings/{id}/complete` | Selesaikan booking |
| GET | `/api/v1/vendor/schedules` | Jadwal operasional |
| PATCH | `/api/v1/vendor/schedules` | Update jadwal (trigger regenerasi slot) |
| CRUD | `/api/v1/vendor/services` | Kelola layanan |
| GET/PATCH | `/api/v1/vendor/profile` | Profil vendor |

### Admin (auth + role:admin)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/admin/bookings` | Semua booking (filter: status, vendor) |
| CRUD | `/api/v1/admin/categories` | Kelola kategori |
| CRUD | `/api/v1/admin/vendors` | Kelola vendor |

### Webhooks
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/webhooks/midtrans` | Midtrans payment callback |
| POST | `/api/v1/webhooks/xendit` | Xendit payment callback |

---

## Setup

```bash
git clone https://github.com/Naufall18/Gerai-Jasa-backend.git
cd Gerai-Jasa-backend

composer install
cp .env.example .env
php artisan key:generate

# Konfigurasi .env (PostgreSQL, Redis, dll)
php artisan migrate --seed

# Generate time slots
php artisan slots:generate

# Jalankan
php artisan serve
```

### Environment Variables

| Variable | Deskripsi |
|----------|-----------|
| `DB_*` | PostgreSQL connection |
| `REDIS_*` | Redis connection |
| `MIDTRANS_*` | Midtrans API keys |
| `FCM_*` | Firebase Cloud Messaging |
| `FONNTE_API_KEY` | WhatsApp gateway |
| `MAIL_*` | Mailgun config |
| `FILESYSTEM_DISK` | S3-compatible storage (MinIO) |

---

## Booking State Machine

```
pending → confirmed → in_progress → completed
    ↓           ↓
cancelled    cancelled
```

### Trigger
| Aksi | Transition | Dilakukan Oleh |
|------|-----------|--------------|
| Confirm booking | pending → confirmed | Vendor |
| Start service | confirmed → in_progress | Vendor |
| Complete service | in_progress → completed | Vendor |
| Cancel (pending) | pending → cancelled | Customer / Vendor |
| Cancel (confirmed) | confirmed → cancelled | Vendor |

---

## Deployment (Vercel)

Backend siap dideploy ke Vercel via [Bref](https://bref.sh) untuk Laravel serverless.

```bash
composer require bref/bref bref/laravel-bridge
serverless deploy
```

Atau gunakan hosting tradisional (Railway, DigitalOcean) untuk full Laravel.

---

## Repositori Terkait

- **Web Dashboard**: [Gerai-Jasa-web](https://github.com/Naufall18/Gerai-Jasa-web)
- **Mobile App**: [Gerai-Jasa-mobile](https://github.com/Naufall18/Gerai-Jasa-mobile)
- **Fullstack**: [Gerai-Jasa](https://github.com/Naufall18/Gerai-Jasa)

---

## Lisensi

MIT
