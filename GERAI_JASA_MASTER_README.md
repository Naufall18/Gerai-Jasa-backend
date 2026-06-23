# BOOKLY — Multi-Vendor Booking Platform
## Master README & AI Prompt Bundle (Cline + Figma/Stitch)

---

# BAGIAN 1 — PROJECT OVERVIEW

## Nama Project
**Gerai Jasa** — Platform booking multi-vendor (salon, klinik, bengkel, dan sejenisnya)

## Tech Stack
| Layer | Teknologi |
|---|---|
| Backend API | Laravel 11, PHP 8.3 |
| Admin & Vendor Dashboard | React 18 + TypeScript + Vite |
| Customer Mobile App | Flutter 3.x (Dart) |
| Database | PostgreSQL 16 |
| Cache & Queue | Redis 7 |
| Payment | Midtrans + Xendit + COD |
| Notifikasi | FCM Push, WhatsApp (Fonnte), Email (Mailgun) |
| Realtime | Laravel Echo + Pusher/Soketi |
| Auth | Laravel Sanctum (JWT-based) |
| Storage | Laravel + S3-compatible (MinIO / Cloudflare R2) |

## Struktur Repo (Monorepo)
```
geraijasa/
├── backend/          ← Laravel 11 API
├── web/              ← React + TypeScript (Admin & Vendor Dashboard)
├── mobile/           ← Flutter customer app
├── docs/             ← Dokumentasi API, ERD, flow diagram
└── docker-compose.yml
```

---

# BAGIAN 2 — CLINE MASTER PROMPT

> Salin prompt ini ke Cline sebagai **system/project context** saat memulai sesi baru.
> Paste di bagian "Custom Instructions" atau di awal chat Cline.

---

```
You are a senior full-stack engineer helping build "Gerai Jasa" — a multi-vendor booking platform for Indonesia market (salons, clinics, workshops, etc.).

## Project Structure
Monorepo with 3 apps:
1. `backend/` — Laravel 11 REST API (PHP 8.3)
2. `web/` — React 18 + TypeScript + Vite (Admin + Vendor Dashboard)
3. `mobile/` — Flutter 3.x (Customer App)

## Core Rules — Always Follow
- Laravel: use Repository Pattern + Service Layer. Never put business logic in Controllers.
- React: use TanStack Query for server state, Zustand for client state, React Hook Form + Zod for forms.
- Flutter: use Riverpod for state management, Dio for HTTP, GoRouter for navigation.
- All API responses must follow this structure:
  { "success": true, "message": "...", "data": {...}, "meta": {...} }
- All IDs must be UUID (not auto-increment).
- All timestamps in UTC ISO 8601.
- Every model must have soft deletes.
- Validation always in Form Request classes (Laravel), Zod schemas (React), and form validators (Flutter).
- Never write business logic in Controllers or UI components.
- Always write PHPDoc for all methods.
- React components must be typed with TypeScript interfaces, never use `any`.
- Flutter: always handle loading, error, and empty states in every screen.

## Database Rules
- Use PostgreSQL. Use uuid-ossp extension for UUIDs.
- JSON columns for flexible metadata (vendor category-specific fields).
- All foreign keys must have explicit cascade rules.
- Add DB indexes on: email, phone, status, vendor_id, booking_code, slot_date.

## Authentication Flow
- Laravel Sanctum tokens.
- Role-based: customer | vendor | admin (stored in `users.role`).
- Middleware groups: `auth:sanctum`, `role:vendor`, `role:admin`.
- OTP login via phone number for customers.

## Naming Conventions
- Laravel: snake_case for DB, PascalCase for classes, camelCase for variables.
- React: PascalCase for components, camelCase for functions/variables, kebab-case for filenames.
- Flutter: PascalCase for widgets/classes, camelCase for variables, snake_case for files.
- API routes: /api/v1/{resource} (plural, kebab-case).

## Folder Structure

### Laravel (backend/)
app/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
├── Models/
├── Repositories/
│   ├── Contracts/
│   └── Eloquent/
├── Services/
├── Jobs/
├── Events/
├── Listeners/
└── Notifications/
database/
├── migrations/
├── seeders/
└── factories/
routes/
├── api.php
└── channels.php

### React (web/)
src/
├── api/           ← axios instances + API calls
├── components/    ← shared components (ui/, layout/, forms/)
├── features/      ← feature modules (bookings/, vendors/, dashboard/)
│   └── bookings/
│       ├── components/
│       ├── hooks/
│       ├── pages/
│       └── types.ts
├── hooks/         ← global custom hooks
├── lib/           ← utils, constants, zod schemas
├── stores/        ← zustand stores
├── types/         ← global TypeScript types
└── router/        ← TanStack Router or React Router config

### Flutter (mobile/)
lib/
├── core/
│   ├── api/       ← dio client, interceptors
│   ├── constants/
│   ├── router/    ← GoRouter config
│   ├── theme/     ← app theme, colors, text styles
│   └── utils/
├── features/
│   ├── auth/
│   ├── home/
│   ├── search/
│   ├── booking/
│   ├── payment/
│   └── profile/
└── shared/
    ├── widgets/
    └── models/

## When I ask you to build a feature, always:
1. Start with the Laravel migration + model.
2. Then Repository interface + Eloquent implementation.
3. Then Service class with business logic.
4. Then Controller (thin, only calls Service).
5. Then API Resource for response formatting.
6. Then React types + API hook + UI component.
7. Then Flutter model + repository + provider + screen.
8. Ask me before making any breaking DB schema changes.
9. Always show file path at the top of every code block.

## Payment Integration Notes
- Midtrans: use Snap for in-app payment. Webhook endpoint: POST /api/v1/webhooks/midtrans
- Xendit: use Invoice API. Webhook endpoint: POST /api/v1/webhooks/xendit
- COD: booking created with payment_method=cod, status=awaiting_service
- Always verify webhook signature/hash before processing.
- Exempt webhook routes from CSRF and auth middleware.

## Booking State Machine
pending → confirmed → in_progress → completed
                ↓               ↓
           cancelled        cancelled
COD special: pending → confirmed → awaiting_payment → completed

## Slot Logic
- `schedules` table: define weekly operating hours per vendor.
- `time_slots` table: generated slots per date (generate 60 days ahead via scheduled job).
- When booking is confirmed, decrement `time_slots.available_count`.
- Use DB transaction + pessimistic locking when confirming a slot to prevent race condition.

## Notification Events to Implement
- booking.created → notify vendor (push + WA)
- booking.confirmed → notify customer (push + WA)
- booking.cancelled → notify both
- booking.reminder → notify customer (H-1 dan H-0 via queue)
- payment.success → notify both

Now, whenever I say "build [feature]", follow the full stack flow above.
```

---

# BAGIAN 3 — CLINE PROMPT PER FASE

## FASE 1A — Setup Project

```
Initialize the Gerai Jasa monorepo:

1. Create Laravel 11 project in `backend/` with:
   - Install: sanctum, spatie/laravel-permission, intervention/image, spatie/laravel-query-builder, laravel/horizon, predis/predis
   - Configure PostgreSQL in .env
   - Enable uuid-ossp in PostgreSQL
   - Setup base API response trait (ApiResponseTrait)
   - Setup base Repository interface and abstract class
   - Setup CORS for localhost:5173 (React dev)
   - Setup Laravel Horizon for queue monitoring

2. Create React + TypeScript project in `web/` with Vite:
   - Install: axios, @tanstack/react-query, @tanstack/react-router, zustand, react-hook-form, zod, @hookform/resolvers, lucide-react, recharts, date-fns, clsx, tailwind-merge
   - Setup Tailwind CSS v3
   - Setup shadcn/ui (New York style, slate color)
   - Setup axios instance with interceptors (auth token, refresh, error handling)
   - Setup TanStack Query client with default options

3. Create Flutter project in `mobile/`:
   - Install: dio, riverpod, flutter_riverpod, go_router, shared_preferences, flutter_secure_storage, intl, cached_network_image, shimmer, lottie, flutter_local_notifications, firebase_messaging, google_fonts, freezed, json_annotation
   - Run build_runner for freezed and json_serializable
   - Setup app theme with custom colors and typography
   - Setup GoRouter with auth guard

Show all file paths and complete code for each file created.
```

## FASE 1B — Database & Auth

```
Build the complete database schema and authentication for Gerai Jasa:

Create all Laravel migrations in this order:
1. users (id uuid, name, email unique, phone unique, role enum, avatar_url, fcm_token, email_verified_at, phone_verified_at, is_active, timestamps, softDeletes)
2. categories (id uuid, name, slug unique, icon_url, description, is_active, sort_order, timestamps)
3. vendors (id uuid, user_id FK, category_id FK, name, slug unique, description, address, city, lat decimal, lng decimal, status enum pending|active|suspended, commission_rate decimal 5.2, rating_avg decimal 3.2, rating_count int, meta jsonb, is_featured, timestamps, softDeletes)
4. vendor_photos (id uuid, vendor_id FK, url, caption, sort_order, timestamps)
5. services (id uuid, vendor_id FK, name, description, price decimal 12.2, duration_minutes int, max_advance_days int default 30, is_active, meta jsonb, timestamps, softDeletes)
6. schedules (id uuid, vendor_id FK, day_of_week 0-6, open_time time, close_time time, is_closed bool, timestamps)
7. time_slots (id uuid, vendor_id FK, service_id FK nullable, slot_date date, slot_time time, capacity int default 1, booked_count int default 0, is_available bool, timestamps) + index on [vendor_id, slot_date, is_available]
8. bookings (id uuid, booking_code unique, customer_id FK, vendor_id FK, service_id FK, time_slot_id FK, status enum, notes text, special_requests text, total_price decimal 12.2, commission_amount decimal 12.2, payment_method enum cod|midtrans|xendit, confirmed_at, completed_at, cancelled_at, cancellation_reason, timestamps, softDeletes)
9. payments (id uuid, booking_id FK unique, gateway enum, amount decimal 12.2, status enum pending|paid|failed|refunded, gateway_ref, gateway_response jsonb, paid_at, expired_at, timestamps)
10. reviews (id uuid, booking_id FK unique, customer_id FK, vendor_id FK, rating tinyint 1-5, comment text, vendor_reply text, replied_at, is_visible, timestamps)
11. notifications (id uuid, notifiable_type, notifiable_id, type, data jsonb, read_at, timestamps)
12. otps (id uuid, phone, code, type, expires_at, used_at, timestamps)

Then build:
- All Eloquent Models with relationships, casts, and fillable
- Auth endpoints: POST /api/v1/auth/request-otp, POST /api/v1/auth/verify-otp, POST /api/v1/auth/register, POST /api/v1/auth/login (email+password for admin/vendor), POST /api/v1/auth/logout, GET /api/v1/auth/me
- AuthController → AuthService → UserRepository
- Role middleware for vendor and admin
- All seeders: CategorySeeder, AdminSeeder, VendorSeeder (with fake data), ServiceSeeder, SlotSeeder
```

## FASE 1C — Booking Engine

```
Build the core booking engine for Gerai Jasa:

1. SlotGenerationService:
   - Method generateForVendor(Vendor $vendor, int $days = 60)
   - Reads vendor schedules, generates time_slots records
   - Respects service duration (no overlapping slots)
   - Scheduled command: php artisan slots:generate (run daily via scheduler)

2. BookingService with methods:
   - getAvailableSlots(vendorId, serviceId, date): return available slots (use Redis cache, TTL 60s)
   - createBooking(customerId, data): DB transaction, lock slot, create booking + payment record
   - confirmBooking(bookingId, vendorId): vendor confirms, send notification to customer
   - cancelBooking(bookingId, userId, reason): update both booking and slot, trigger refund if paid
   - completeBooking(bookingId, vendorId): mark complete, release commission calculation

3. API Endpoints:
   GET  /api/v1/vendors — list vendors (filter: category, city, rating, search)
   GET  /api/v1/vendors/{slug} — vendor detail + services
   GET  /api/v1/vendors/{id}/slots?service_id=&date= — available slots
   POST /api/v1/bookings — create booking
   GET  /api/v1/bookings — customer's bookings (paginated)
   GET  /api/v1/bookings/{id} — booking detail
   PATCH /api/v1/bookings/{id}/cancel — cancel booking

   Vendor routes (auth:sanctum, role:vendor):
   GET  /api/v1/vendor/bookings — incoming bookings
   PATCH /api/v1/vendor/bookings/{id}/confirm
   PATCH /api/v1/vendor/bookings/{id}/complete
   GET  /api/v1/vendor/schedules — get schedules
   PUT  /api/v1/vendor/schedules — update schedules
   CRUD /api/v1/vendor/services

4. Payment webhooks:
   POST /api/v1/webhooks/midtrans
   POST /api/v1/webhooks/xendit
   (exempt from auth + csrf, verify signature)

5. Jobs:
   - SendBookingConfirmationJob (push + WA notification)
   - SendBookingReminderJob (dispatch H-1 and H-0 via scheduler)
   - GenerateTimeSlotsJob

Show complete code for all files.
```

## FASE 2 — React Dashboard

```
Build the React TypeScript dashboard for Gerai Jasa with these pages:

Design system: Tailwind CSS + shadcn/ui (New York style).
Color scheme: Primary = Indigo (#6366F1), use slate for neutrals.
Font: Inter (via Google Fonts or Fontsource).
Style: Clean, minimal, data-dense. Sidebar navigation.

Pages to build:

SUPER ADMIN layout (route: /admin/*):
- /admin/dashboard — stats cards (total bookings, revenue, active vendors, users), recent bookings table, booking trend chart (Recharts LineChart)
- /admin/vendors — table with search/filter, approve/suspend actions, status badges
- /admin/bookings — all bookings with filters (status, date range, vendor, category)
- /admin/categories — CRUD categories with icon upload
- /admin/users — user management table

VENDOR layout (route: /vendor/*):
- /vendor/dashboard — today's bookings card, revenue this month, pending confirmations count, upcoming bookings list
- /vendor/bookings — booking list with tabs (pending/confirmed/completed/cancelled), confirm/reject actions
- /vendor/calendar — calendar view (react-big-calendar or custom) showing booked slots per day
- /vendor/services — CRUD services (name, price, duration, active toggle)
- /vendor/schedule — weekly schedule form (open/close time per day, day off toggle)
- /vendor/profile — edit vendor profile, upload photos, business info
- /vendor/reviews — list reviews with reply feature
- /vendor/payouts — payout history table

For each page:
1. TypeScript types/interfaces
2. API hooks (TanStack Query)
3. Zod validation schemas for forms
4. Complete React component with loading/error/empty states
5. Show file path for every file

Use shadcn/ui components: Table, Card, Badge, Dialog, Sheet, Form, Input, Select, DatePicker, Tabs, Skeleton.
Add subtle animations using tailwindcss-animate (already included in shadcn).
```

## FASE 3 — Flutter Customer App

```
Build the Flutter customer app for Gerai Jasa with complete screens:

Design: Modern, clean, warm color palette.
Primary color: Indigo (#6366F1) with coral accent (#F97316).
Font: Plus Jakarta Sans (Google Fonts).
Use Riverpod for state, GoRouter for navigation, Dio for API.
Add Lottie animations for empty states and success states.
Use shimmer for loading skeletons.
Add Hero transitions between list and detail screens.
Use SlideTransition and FadeTransition for page transitions.

Screens to build:

AUTH FLOW:
- SplashScreen — animated logo, check token, redirect
- OnboardingScreen — 3 slides with Lottie animations
- PhoneInputScreen — input phone, send OTP
- OtpVerificationScreen — 6-digit OTP input with countdown timer
- ProfileSetupScreen — name, email after first login

MAIN FLOW (BottomNavigationBar: Home, Search, Bookings, Profile):
- HomeScreen:
  - Search bar (navigates to SearchScreen)
  - Category chips (horizontal scroll)
  - Featured vendors (horizontal scroll cards)
  - Nearby vendors (vertical list)
  - All with shimmer loading

- SearchScreen:
  - Search input with debounce
  - Filter bottom sheet (category, city, min rating, price range)
  - Results list with infinite scroll

- VendorDetailScreen:
  - Hero image with parallax scroll
  - Vendor info, rating, address with map preview
  - Services list (expandable cards)
  - Reviews section
  - "Book Now" sticky button

- BookingScreen:
  - Step 1: Select service
  - Step 2: Select date (custom calendar widget)
  - Step 3: Select time slot (grid of available slots)
  - Step 4