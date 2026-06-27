<div align="center">
  <br/>
  <h1>⚙️ Gerai Jasa — Backend API</h1>
  <p>
    <strong>Laravel 11 REST API</strong>
    <br/>
    Backend untuk platform booking multi-vendor
  </p>

  <p>
    <a href="#">
      <img src="https://img.shields.io/badge/status-selesai-brightgreen?style=flat-square&color=%231E6F5C" alt="Status"/>
    </a>
    <a href="https://laravel.com">
      <img src="https://img.shields.io/badge/Laravel-11-%23FF2D20?style=flat-square&logo=laravel" alt="Laravel"/>
    </a>
    <a href="https://www.php.net">
      <img src="https://img.shields.io/badge/PHP-8.3-%23777BB4?style=flat-square&logo=php" alt="PHP"/>
    </a>
    <a href="https://www.postgresql.org">
      <img src="https://img.shields.io/badge/PostgreSQL-16-%234169E1?style=flat-square&logo=postgresql" alt="PostgreSQL"/>
    </a>
    <a href="https://redis.io">
      <img src="https://img.shields.io/badge/Redis-7-%23DC382D?style=flat-square&logo=redis" alt="Redis"/>
    </a>
  </p>

  <br/>
</div>

---

## 📋 Daftar Isi

- [Tentang](#-tentang)
- [Tech Stack](#-tech-stack)
- [Arsitektur](#-arsitektur)
- [Fitur](#-fitur)
- [Struktur Folder](#-struktur-folder)
- [Database](#-database)
- [State Machine](#-state-machine)
- [API Endpoints](#-api-endpoints)
- [Setup](#-setup)
- [Environment](#-environment)
- [Testing API](#-testing-api)
- [Repositori Terkait](#-repositori-terkait)
- [Lisensi](#-lisensi)

---

## 🎯 Tentang

Backend API untuk **Gerai Jasa** — platform booking multi-vendor modern untuk pasar Indonesia. Dibangun dengan Laravel 11 menggunakan arsitektur **Repository Pattern** + **Service Layer** untuk maintainability dan testability yang optimal.

**✅ Status: Production Ready** — Seluruh fitur inti telah diimplementasikan dan diuji.

---

## 🛠️ Tech Stack

| Kategori | Teknologi |
|----------|-----------|
| **Runtime** | PHP 8.3 |
| **Framework** | Laravel 11 |
| **Database** | PostgreSQL 16 (UUID, JSONB) |
| **Cache & Queue** | Redis 7 |
| **Auth** | Laravel Sanctum (token-based) |
| **Queue Monitor** | Laravel Horizon |
| **Image** | Intervention Image (GD) |
| **Payment** | Midtrans Snap API |
| **Notifications** | FCM, Fonnte (WhatsApp) |

---

## 🏗️ Arsitektur

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│   Request    │────▶│    Controller    │────▶│   Service    │
└──────────────┘     └──────────────────┘     └──────┬───────┘
                                                      │
                                               ┌──────▼───────┐
                                               │  Repository  │
                                               └──────┬───────┘
                                                      │
                                               ┌──────▼───────┐
                                               │    Model     │
                                               └──────┬───────┘
                                                      │
                                               ┌──────▼───────┐
                                               │  Database    │
                                               └──────────────┘
```

### Pola Desain
- **Controller** → menerima request, memanggil Service, mengembalikan response
- **Service** → business logic, orchestrasi repository
- **Repository** → query database via Eloquent
- **API Resource** → formatting response konsisten
- **Form Request** → validasi terpusat

### Format Response
```json
{
  "success": true,
  "message": "Booking retrieved successfully.",
  "data": { ... },
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 50
    }
  }
}
```

---

## ✨ Fitur

### ✅ Selesai & Production Ready

| Fitur | Detail |
|-------|--------|
| **Auth** | OTP-based phone login + email/password login |
| **Role Management** | `customer`, `vendor`, `admin` — middleware per role |
| **Vendor Management** | CRUD, kategori, foto, status aktif/nonaktif |
| **Service Management** | Layanan per vendor (harga, durasi, deskripsi) |
| **Schedule Management** | Jadwal operasional mingguan |
| **Slot Generation** | Auto-generate time slot 60 hari ke depan |
| **Booking Engine** | State machine + pessimistic locking |
| **Payment** | Midtrans Snap + COD |
| **Webhook** | Midtrans callback dengan signature verification |
| **Notifications** | FCM push + WhatsApp (Fonnte) |
| **Reviews** | Rating & komentar per booking |
| **Filter & Pagination** | Booking filter by status, date range |
| **Soft Deletes** | Di semua model |
| **Queue Jobs** | Async notification delivery |
| **API Documentation** | Endpoints lengkap di bawah |

---

## 📁 Struktur Folder

```
app/
├── Console/
│   └── Commands/
│       └── GenerateSlotsCommand.php      # php artisan slots:generate
│
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── AuthController.php           # OTP, login, register
│   │       ├── CustomerBookingController.php # Booking CRUD
│   │       ├── VendorBookingController.php   # Vendor booking actions
│   │       ├── VendorProfileController.php   # Profile & schedules
│   │       ├── VendorServiceController.php   # Service management
│   │       ├── AdminBookingController.php    # Admin booking view
│   │       ├── AdminVendorController.php     # Admin vendor mgmt
│   │       ├── AdminCategoryController.php   # Category CRUD
│   │       ├── PublicVendorController.php    # Public listing
│   │       └── WebhookController.php         # Payment callbacks
│   │
│   ├── Middleware/
│   │   ├── RoleMiddleware.php                # Role-based access
│   │   └── ...
│   │
│   ├── Requests/
│   │   ├── Auth/                             # Login, register requests
│   │   ├── Booking/                          # Booking validation
│   │   └── Vendor/                           # Profile, schedule requests
│   │
│   └── Resources/
│       ├── BookingResource.php               # Booking response format
│       ├── VendorResource.php                # Vendor response format
│       └── ...
│
├── Jobs/
│   ├── SendBookingConfirmationJob.php        # Push after confirm
│   └── SendBookingNotificationJob.php        # General notifications
│
├── Models/
│   ├── User.php
│   ├── Vendor.php
│   ├── Booking.php
│   ├── Service.php
│   ├── TimeSlot.php
│   ├── Schedule.php
│   ├── Review.php
│   ├── Payment.php
│   ├── Category.php
│   ├── Otp.php
│   └── Notification.php
│
├── Repositories/
│   ├── Contracts/
│   │   ├── BookingRepositoryInterface.php
│   │   └── ...
│   └── Eloquent/
│       ├── BookingRepository.php
│       └── ...
│
├── Services/
│   ├── BookingService.php                    # Booking business logic
│   ├── SlotGenerationService.php             # Time slot engine
│   ├── PaymentService.php                    # Payment orchestration
│   └── NotificationService.php               # Push/WA dispatch
│
└── Traits/
    └── ApiResponseTrait.php                  # Consistent JSON response
```

---

## 🗄️ Database

### Entity Relationship

```
┌──────────┐     ┌───────────┐     ┌──────────┐
│   users  │────▶│  vendors  │◀────│categories│
└──────────┘     └─────┬─────┘     └──────────┘
                       │
              ┌────────┼────────┐
              ▼        ▼        ▼
        ┌────────┐┌────────┐┌──────────┐
        │services││schedules││vendor_photos
        └───┬────┘└────────┘└──────────┘
            │
      ┌─────▼──────┐
      │ time_slots │
      └─────┬──────┘
            │
      ┌─────▼──────┐     ┌──────────┐
      │  bookings  │────▶│ payments │
      └─────┬──────┘     └──────────┘
            │
      ┌─────▼──────┐     ┌──────────────┐
      │  reviews   │     │ notifications│
      └────────────┘     └──────────────┘
```

- **12 tabel** — seluruhnya menggunakan UUID primary key
- **Soft deletes** di semua tabel
- **Timestamps** dalam UTC ISO 8601
- **Indexed** untuk query performance

---

## 🔄 State Machine

```
                     ┌──────────────────┐
                     │     pending      │
                     └────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │    confirmed      │◀── Vendor konfirmasi
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │   in_progress     │◀── Vendor mulai
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │    completed      │◀── Vendor selesai
                    └───────────────────┘

Cancel flow:
    pending ──────▶ cancelled     (oleh customer atau vendor)
    confirmed ────▶ cancelled     (oleh vendor)
```

---

## 📡 API Endpoints

### 🔐 Auth
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | `/api/v1/auth/request-otp` | ❌ | Request OTP via WhatsApp |
| POST | `/api/v1/auth/verify-otp` | ❌ | Verifikasi OTP → dapat token |
| POST | `/api/v1/auth/register` | ❌ | Register user baru |
| POST | `/api/v1/auth/login` | ❌ | Login email/password |
| POST | `/api/v1/auth/logout` | ✅ | Logout |
| GET | `/api/v1/auth/me` | ✅ | Profil user saat ini |
| PATCH | `/api/v1/auth/profile` | ✅ | Update profil |
| PATCH | `/api/v1/auth/fcm-token` | ✅ | Register FCM token |

### 🌍 Public
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/categories` | Semua kategori |
| GET | `/api/v1/vendors` | List vendor (filter: category_id, city, search, featured) |
| GET | `/api/v1/vendors/{slug}` | Detail vendor + layanan + foto |
| GET | `/api/v1/vendors/{vendorId}/services` | Layanan vendor |
| GET | `/api/v1/vendors/{vendorId}/slots` | Slot tersedia (query: service_id, date) |
| GET | `/api/v1/bookings/{id}` | Track booking status (by kode) |

### 👤 Customer (auth required)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/bookings` | Buat booking baru |
| GET | `/api/v1/bookings` | Booking saya (paginated, filter status) |
| GET | `/api/v1/bookings/{id}` | Detail booking |
| PATCH | `/api/v1/bookings/{id}/cancel` | Batalkan booking (alasan) |
| POST | `/api/v1/bookings/{id}/pay` | Inisiasi pembayaran Midtrans |
| POST | `/api/v1/bookings/{id}/review` | Kirim rating & ulasan |

### 🏪 Vendor (role: vendor)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/vendor/bookings` | Booking masuk (filter: status, from, to) |
| PATCH | `/api/v1/vendor/bookings/{id}/confirm` | Konfirmasi booking |
| PATCH | `/api/v1/vendor/bookings/{id}/complete` | Selesaikan booking |
| GET | `/api/v1/vendor/schedules` | Jadwal operasional |
| PATCH | `/api/v1/vendor/schedules` | Update jadwal (trigger slot regeneration) |
| GET | `/api/v1/vendor/services` | Layanan vendor |
| POST | `/api/v1/vendor/services` | Tambah layanan |
| PUT | `/api/v1/vendor/services/{id}` | Update layanan |
| DELETE | `/api/v1/vendor/services/{id}` | Hapus layanan |
| GET | `/api/v1/vendor/profile` | Profil vendor |
| PATCH | `/api/v1/vendor/profile` | Update profil |
| POST | `/api/v1/vendor/photos` | Upload foto vendor |
| PATCH | `/api/v1/vendor/reviews/{id}/reply` | Balas ulasan |

### 🛡️ Admin (role: admin)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/admin/dashboard` | Statistik dashboard |
| GET | `/api/v1/admin/bookings` | Semua booking (filter: status, vendor) |
| GET | `/api/v1/admin/vendors` | Semua vendor |
| PATCH | `/api/v1/admin/vendors/{id}/status` | Approve/suspend vendor |
| CRUD | `/api/v1/admin/categories` | Kelola kategori |
| GET | `/api/v1/admin/users` | Semua user |

### 📞 Webhooks (no auth)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/webhooks/midtrans` | Midtrans payment callback |
| POST | `/api/v1/webhooks/xendit` | Xendit payment callback |

---

## ⚙️ Setup

### Prasyarat
- PHP 8.3+
- Composer 2.x
- PostgreSQL 16
- Redis 7

### Instalasi
```bash
# Clone
git clone https://github.com/Naufall18/Gerai-Jasa-backend.git
cd Gerai-Jasa-backend

# Dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Konfigurasi database di .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=geraijasa
DB_USERNAME=postgres
DB_PASSWORD=secret

# Migrate & seed
php artisan migrate --seed

# Generate time slots
php artisan slots:generate

# Start queue worker (untuk notifikasi)
php artisan queue:work

# Jalankan
php artisan serve
```

### Development
```bash
# Generate slots for specific vendor
php artisan slots:generate --vendor-id=uuid

# Clear all caches
php artisan optimize:clear

# Queue monitoring
php artisan horizon
```

---

## 🌍 Environment Variables

| Variable | Wajib | Default | Deskripsi |
|----------|-------|---------|-----------|
| `APP_ENV` | ✅ | `local` | Environment |
| `APP_DEBUG` | ✅ | `true` | Debug mode |
| `DB_*` | ✅ | — | PostgreSQL connection |
| `REDIS_*` | ❌ | `127.0.0.1` | Redis connection |
| `SANCTUM_TOKEN_PREFIX` | ❌ | — | Token prefix |
| `MIDTRANS_SERVER_KEY` | ❌ | — | Midtrans server key |
| `MIDTRANS_CLIENT_KEY` | ❌ | — | Midtrans client key |
| `MIDTRANS_IS_PRODUCTION` | ❌ | `false` | Midtrans mode |
| `FCM_SERVER_KEY` | ❌ | — | Firebase server key |
| `FONNTE_API_KEY` | ❌ | — | WhatsApp gateway |
| `MAIL_*` | ❌ | — | Mailgun config |
| `FILESYSTEM_DISK` | ❌ | `local` | Storage disk |

---

## 🧪 Testing API

### Contoh Request

**Login via OTP:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/request-otp \
  -H "Content-Type: application/json" \
  -d '{"phone": "082244089648"}'
```

**Buat Booking:**
```bash
curl -X POST http://localhost:8000/api/v1/bookings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "vendor_id": "uuid",
    "service_id": "uuid",
    "time_slot_id": "uuid",
    "payment_method": "cod"
  }'
```

### Collection Postman
Import file `docs/geraijasa.postman_collection.json` untuk koleksi endpoint lengkap.

---

## 📦 Repositori Terkait

| Repositori | Link | Status |
|-----------|------|--------|
| Monorepo Utama | [Gerai-Jasa](https://github.com/Naufall18/Gerai-Jasa) | 🔒 Private |
| Web Dashboard | [Gerai-Jasa-web](https://github.com/Naufall18/Gerai-Jasa-web) | ✅ Public |
| Mobile App | [Gerai-Jasa-mobile](https://github.com/Naufall18/Gerai-Jasa-mobile) | ✅ Public |

---

## 👨‍💻 Pengembang

**Naufall18** — [GitHub](https://github.com/Naufall18)

---

## 📄 Lisensi

**MIT License** — Copyright © 2026 Naufall18

<div align="center">
  <br/>
  <sub>Dibangun dengan ❤️ untuk ekosistem digital Indonesia</sub>
  <br/>
  <br/>
</div>
