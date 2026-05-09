# SkillBro API

A Laravel 13 REST API backend for a course marketplace. API-only — no Blade, no Inertia. Consumed by a separate Vue SPA frontend via Sanctum bearer token auth.

---

## Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Auth | Laravel Sanctum (bearer tokens) |
| Database | SQLite (dev) / MySQL or PostgreSQL (prod) |
| Queue | Redis (prod), `sync` (dev/test) |
| Storage | Local (dev) / S3-compatible (prod) |
| Tests | Pest 4 |

---

## Quick Start

```bash
git clone <repo>
cd SkillBro

# Install dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Run dev server
php artisan serve
```

Run tests:
```bash
vendor/bin/pest --no-coverage
```

---

## Authentication

All protected endpoints require a `Bearer` token in the `Authorization` header.

**Obtain a token:**
```http
POST /api/v1/auth/register
POST /api/v1/auth/login
```

**Use a token:**
```http
Authorization: Bearer <token>
```

**Revoke:**
```http
POST /api/v1/auth/logout
```

### Roles

| Role | Capabilities |
|------|-------------|
| `student` | Browse courses, enroll (free), track progress, reviews |
| `instructor` | All student abilities + create/manage own courses, reply to reviews |
| `admin` | Full access — approve courses, manage users, handle refunds, stats |

---

## API Reference

Base URL: `/api/v1`

All responses are JSON. Validation errors return `422` with an `errors` object. Unauthenticated requests return `401`. Unauthorized actions return `403`.

---

### Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/auth/register` | Guest | Register, returns token + user |
| `POST` | `/auth/login` | Guest | Login, returns token + user |
| `POST` | `/auth/logout` | Bearer | Revoke current token |

**Register / Login request body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "device_name": "web"
}
```

**Response:**
```json
{
  "token": "1|abc...",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": "student",
    "avatar": null,
    "bio": null,
    "email_verified_at": "2026-01-01T00:00:00.000000Z",
    "created_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### User Profile

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/user` | Bearer | Get own profile |
| `PUT` | `/user` | Bearer | Update own profile (`name`, `bio`) |
| `POST` | `/user/avatar` | Bearer | Upload avatar (multipart `avatar` field, max 2 MB) |
| `GET` | `/instructors/{user}` | Public | Instructor public profile + published courses |

---

### Categories

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/categories` | Public | List all categories (tree with children) |
| `POST` | `/categories` | Admin | Create category |
| `PUT` | `/categories/{category}` | Admin | Update category |
| `DELETE` | `/categories/{category}` | Admin | Delete category |

---

### Courses

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/courses` | Public | Browse published courses (paginated, filterable) |
| `GET` | `/courses/{course}` | Public* | Course detail (`*` full content if enrolled) |
| `POST` | `/courses` | Instructor | Create course (starts as `draft`) |
| `PUT` | `/courses/{course}` | Instructor | Update own course |
| `DELETE` | `/courses/{course}` | Instructor/Admin | Soft-delete course |
| `POST` | `/courses/{course}/thumbnail` | Instructor | Upload thumbnail (multipart, max 5 MB) |
| `POST` | `/courses/{course}/submit` | Instructor | Submit draft for review → `pending` |
| `POST` | `/courses/{course}/publish` | Admin | Approve and publish → `published` |
| `POST` | `/courses/{course}/archive` | Instructor/Admin | Archive course |

**Browse query params:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Full-text search on title + description |
| `category` | integer | Filter by category ID |
| `level` | string | `beginner` / `intermediate` / `advanced` |
| `price_max` | number | Maximum price |
| `free` | any | Only free courses |

**Course status lifecycle:**
```
draft → pending → published → archived
```

**Create course body:**
```json
{
  "title": "Laravel for Beginners",
  "description": "Learn Laravel from scratch.",
  "category_id": 1,
  "price": 0,
  "level": "beginner",
  "language": "en",
  "requirements": ["Basic PHP knowledge"],
  "what_you_learn": ["Build REST APIs", "Use Eloquent ORM"],
  "tags": [1, 2]
}
```

---

### Sections

Nested under a course. All write operations require course ownership.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/courses/{course}/sections` | Bearer* | List sections + lectures |
| `POST` | `/courses/{course}/sections` | Instructor | Create section |
| `PUT` | `/courses/{course}/sections/{section}` | Instructor | Update section |
| `DELETE` | `/courses/{course}/sections/{section}` | Instructor | Delete section |
| `POST` | `/courses/{course}/sections/reorder` | Instructor | Bulk reorder sections |

**Reorder body:**
```json
{
  "sections": [
    { "id": 1, "position": 0 },
    { "id": 2, "position": 1 }
  ]
}
```

---

### Lectures

Nested under a section.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/sections/{section}/lectures` | Instructor | Create lecture |
| `PUT` | `/sections/{section}/lectures/{lecture}` | Instructor | Update lecture |
| `DELETE` | `/sections/{section}/lectures/{lecture}` | Instructor | Delete lecture |
| `POST` | `/sections/{section}/lectures/reorder` | Instructor | Bulk reorder lectures |

**Lecture types:** `video` / `text` / `quiz`

**Create lecture body:**
```json
{
  "title": "Introduction to Routing",
  "type": "video",
  "is_preview": true,
  "position": 0
}
```

---

### Enrollments

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/enrollments` | Bearer | List own enrollments (paginated) |
| `POST` | `/enrollments` | Bearer | Enroll in a **free** published course |
| `GET` | `/enrollments/{enrollment}` | Bearer | Enrollment detail + lecture progress |
| `POST` | `/enrollments/{enrollment}/lectures/{lecture}/complete` | Bearer | Mark lecture as complete |

**Enroll body:**
```json
{ "course_id": 42 }
```

> **Note:** Paid course enrollment requires a payment checkout (Phase 2). This endpoint rejects paid courses with `422`.

Marking the last lecture complete automatically sets `completed_at` on the enrollment.

---

### Admin

All require `role:admin`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/admin/users` | List all users (paginated) |
| `PUT` | `/admin/users/{user}/ban` | Toggle ban status |
| `PUT` | `/admin/users/{user}/role` | Change user role |
| `GET` | `/admin/courses` | List all courses (all statuses, including soft-deleted) |

---

## Project Structure

```
app/
├── Enums/
│   ├── CourseLevel.php       # beginner / intermediate / advanced
│   ├── CourseStatus.php      # draft / pending / published / archived
│   ├── LectureType.php       # video / text / quiz
│   └── UserRole.php          # student / instructor / admin
├── Events/
│   └── StudentEnrolled.php
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── Admin/
│   │       │   ├── CourseController.php
│   │       │   └── UserController.php
│   │       ├── Auth/
│   │       │   └── AuthController.php
│   │       ├── CategoryController.php
│   │       ├── CourseController.php
│   │       ├── EnrollmentController.php
│   │       ├── InstructorController.php
│   │       ├── LectureController.php
│   │       ├── ProfileController.php
│   │       └── SectionController.php
│   ├── Middleware/
│   │   └── CheckRole.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   └── Course/
│   │       ├── StoreCourseRequest.php
│   │       ├── StoreLectureRequest.php
│   │       ├── StoreSectionRequest.php
│   │       ├── UpdateCourseRequest.php
│   │       ├── UpdateLectureRequest.php
│   │       └── UpdateSectionRequest.php
│   └── Resources/
│       ├── CategoryResource.php
│       ├── CourseDetailResource.php   # full content incl. sections
│       ├── CourseResource.php         # list view
│       ├── EnrollmentResource.php
│       ├── LectureProgressResource.php
│       ├── LectureResource.php
│       ├── SectionResource.php
│       └── UserResource.php
├── Listeners/
│   └── SendEnrollmentConfirmationEmail.php  # queued
├── Models/
│   ├── Category.php
│   ├── Course.php            # SoftDeletes, ObservedBy CourseObserver
│   ├── Enrollment.php        # ObservedBy EnrollmentObserver
│   ├── Lecture.php
│   ├── LectureProgress.php
│   ├── Section.php
│   ├── Tag.php
│   └── User.php
├── Notifications/
│   └── EnrollmentConfirmation.php    # mail + database, queued
├── Observers/
│   ├── CourseObserver.php    # auto-generates unique slug
│   └── EnrollmentObserver.php
└── Policies/
    ├── CoursePolicy.php
    ├── EnrollmentPolicy.php
    ├── LecturePolicy.php
    └── SectionPolicy.php

database/
├── factories/          # UserFactory, CourseFactory, EnrollmentFactory ...
├── migrations/         # 14 migrations
└── seeders/

routes/
├── api.php             # all /api/v1/* routes
├── web.php             # existing Fortify web routes (untouched)
└── settings.php

tests/
└── Feature/
    ├── AuthenticationTest.php
    ├── CourseTest.php
    ├── EnrollmentTest.php
    └── SectionLectureTest.php
```

---

## Database Schema

```
users                   categories              tags
─────────────────       ──────────────────      ────────────
id                      id                      id
name                    parent_id (nullable)    name
email (unique)          name (unique)           slug (unique)
email_verified_at       slug (unique)
password
role (student|instructor|admin)
avatar
bio
is_banned
two_factor_*

courses                 course_tag (pivot)
─────────────────       ──────────────────
id                      course_id
user_id → users         tag_id
category_id → categories
title
slug (unique)
description
thumbnail
price
status (draft|pending|published|archived)
level (beginner|intermediate|advanced)
language
requirements (json)
what_you_learn (json)
deleted_at              ← soft delete

sections                lectures
─────────────────       ─────────────────
id                      id
course_id → courses     section_id → sections
title                   title
position                type (video|text|quiz)
                        content
                        video_path
                        video_duration
                        is_preview
                        position

enrollments             lecture_progress
─────────────────       ─────────────────
id                      id
user_id → users         enrollment_id → enrollments
course_id → courses     lecture_id → lectures
enrolled_at             completed_at
completed_at
UNIQUE(user_id, course_id)

personal_access_tokens  notifications
(Sanctum default)       (Laravel default)
```

---

## Authorization

### Policy Structure

All policies have an `admin bypass` via the `before()` hook — admins pass all checks.

| Policy | Model | Key Rules |
|--------|-------|-----------|
| `CoursePolicy` | `Course` | Instructors own their courses; `publish` is admin-only |
| `SectionPolicy` | `Section` | Traverses `section → course → instructor` |
| `LecturePolicy` | `Lecture` | Traverses `lecture → section → course → instructor` |
| `EnrollmentPolicy` | `Enrollment` | `create` prevents duplicate enrollment; `view` owns enrollment |

### Middleware

`role:instructor` and `role:admin` are registered as middleware aliases in `bootstrap/app.php` via the `CheckRole` middleware. Used directly on route groups.

---

## Background Jobs

| Job / Listener | Trigger | Queue | Description |
|---------------|---------|-------|-------------|
| `SendEnrollmentConfirmationEmail` | `StudentEnrolled` event | `emails` | Notifies student via mail + DB notification |

> **Phase 2 jobs (not yet implemented):** `ProcessPaymentWebhook`, `ProcessVideoUpload`, `GenerateCompletionCertificate`, `UpdateCourseRatingCache`

---

## File Storage

| Asset | Path | Access |
|-------|------|--------|
| User avatars | `avatars/{user_id}/{uuid}.{ext}` | public |
| Course thumbnails | `thumbnails/{course_id}/{uuid}.{ext}` | public |
| Lecture videos | `videos/{course_id}/{lecture_id}/{uuid}.mp4` | private (signed URL) |
| Lecture attachments | `attachments/{lecture_id}/{uuid}.{ext}` | private (signed URL) |

Configure `FILESYSTEM_DISK` in `.env`. Use `public` for local dev, `s3` for production.

---

## Environment Variables

Key variables to configure:

```env
APP_NAME=SkillBro
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite         # or mysql / pgsql
DB_DATABASE=/path/to/db.sqlite

QUEUE_CONNECTION=sync        # use redis in production

FILESYSTEM_DISK=public       # use s3 in production

# S3 (production)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@skillbro.com
MAIL_FROM_NAME="SkillBro"
```

---

## Development Roadmap

### ✅ Phase 1 — Core (complete)
- Auth (register, login, logout) via Sanctum
- Course CRUD + sections + lectures (ordered)
- Free course enrollment + lecture progress tracking
- Basic file upload (thumbnails, avatars)
- Role-based access (student / instructor / admin)

### 🔜 Phase 2 — Monetization
- Provider-neutral payments (checkout session + webhook)
- Paid course enrollment
- Payment history + refund requests
- Instructor revenue dashboard

### 🔜 Phase 3 — Learning Experience
- Quiz system (questions, attempts, scoring)
- Course completion certificates (PDF)
- Email verification enforcement

### 🔜 Phase 4 — Community & Polish
- Reviews + ratings
- Instructor analytics
- Platform-wide admin stats
- Full-text search (Scout + Meilisearch)
- Presigned S3 upload for lecture videos
- In-app notifications endpoint
