# SkillBro — Laravel REST API Backend Plan

> API-only. No Blade, no Inertia. Consumed by Vue SPA via Sanctum token auth.

---

## 1. Feature List

### Auth
- Register (student default role)
- Login / Logout
- Email verification
- Password reset
- Sanctum token issuance
- Role-based access: `student`, `instructor`, `admin`

### Users / Profiles
- View/update own profile
- Avatar upload
- Instructor public profile (bio, courses list)

### Courses
- CRUD (instructors own courses)
- Course status: `draft` → `pending` → `published` → `archived`
- Category & tag assignment
- Thumbnail upload
- Course search & filtering (title, category, price, rating)
- Course sections + lectures (ordered)
- Lecture types: `video`, `text`, `quiz`
- Free preview flag per lecture

### Enrollment
- Enroll in course (free or after payment)
- Track lecture progress (mark complete)
- Course completion certificate generation
- Enrolled courses list for student

### Payments
- Create Stripe checkout session
- Webhook to confirm payment → trigger enrollment
- Payment history per user
- Refund request (admin approves)
- Revenue dashboard for instructor (read-only totals)

### Reviews
- Post review + rating (1–5) after enrollment
- One review per student per course
- Instructor reply to review
- Admin delete abusive review

### Quiz
- Attach quiz to lecture
- Questions + multiple-choice answers
- Student quiz attempt + score
- Pass/fail threshold per quiz

### Notifications
- In-app notification model (DB driver)
- Events trigger notifications (enrollment, review, payment)

### Admin
- Approve/reject course publication
- Manage users (ban, role change)
- View platform stats (revenue, enrollment counts)
- Manage refunds

---

## 2. Database Schema

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| email_verified_at | timestamp nullable | |
| password | string | hashed |
| role | enum: student,instructor,admin | default: student |
| avatar | string nullable | S3 path |
| bio | text nullable | instructor only |
| is_banned | boolean | default: false |
| remember_token | string nullable | |
| timestamps | | |
> Soft deletes: **no** (ban flag used instead)

### `personal_access_tokens`
> Sanctum default table.

### `categories`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string unique | |
| slug | string unique | |
| parent_id | bigint FK nullable | self-referential |
| timestamps | | |

### `courses`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK → users | instructor |
| category_id | bigint FK nullable | |
| title | string | |
| slug | string unique | |
| description | text | |
| thumbnail | string nullable | S3 path |
| price | decimal(8,2) | 0.00 = free |
| status | enum: draft,pending,published,archived | default: draft |
| level | enum: beginner,intermediate,advanced | |
| language | string | default: en |
| requirements | json nullable | |
| what_you_learn | json nullable | |
| timestamps | | |
| deleted_at | timestamp nullable | **soft delete** |

### `tags`
| Column | Type |
|--------|------|
| id | bigint PK |
| name | string unique |
| slug | string unique |

### `course_tag` (pivot)
| Column | Type |
|--------|------|
| course_id | bigint FK |
| tag_id | bigint FK |

### `sections`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| course_id | bigint FK | |
| title | string | |
| position | unsignedSmallInt | order |
| timestamps | | |

### `lectures`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| section_id | bigint FK | |
| title | string | |
| type | enum: video,text,quiz | |
| content | text nullable | for text type |
| video_path | string nullable | S3 path |
| video_duration | unsignedInt nullable | seconds |
| is_preview | boolean | default: false |
| position | unsignedSmallInt | |
| timestamps | | |

### `enrollments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK | student |
| course_id | bigint FK | |
| enrolled_at | timestamp | |
| completed_at | timestamp nullable | |
| timestamps | | |
> Unique: (user_id, course_id). Soft deletes: **no**.

### `lecture_progress`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| enrollment_id | bigint FK | |
| lecture_id | bigint FK | |
| completed_at | timestamp nullable | |
> Unique: (enrollment_id, lecture_id).

### `quizzes`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| lecture_id | bigint FK unique | one quiz per lecture |
| pass_percentage | unsignedTinyInt | default: 70 |
| timestamps | | |

### `quiz_questions`
| Column | Type |
|--------|------|
| id | bigint PK |
| quiz_id | bigint FK |
| question | text |
| position | unsignedSmallInt |

### `quiz_answers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| question_id | bigint FK | |
| answer | string | |
| is_correct | boolean | |

### `quiz_attempts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| enrollment_id | bigint FK | |
| quiz_id | bigint FK | |
| score | unsignedTinyInt | percentage |
| passed | boolean | |
| answers | json | snapshot of chosen answer IDs |
| created_at | timestamp | |

### `payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK | student |
| course_id | bigint FK | |
| amount | decimal(8,2) | |
| currency | string | default: usd |
| stripe_payment_intent_id | string unique | |
| stripe_session_id | string nullable | |
| status | enum: pending,completed,refunded,failed | |
| refund_requested_at | timestamp nullable | |
| timestamps | | |

### `reviews`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK | student |
| course_id | bigint FK | |
| rating | unsignedTinyInt | 1–5 |
| body | text nullable | |
| instructor_reply | text nullable | |
| timestamps | | |
| deleted_at | timestamp nullable | **soft delete** |
> Unique: (user_id, course_id).

### `notifications`
> Laravel default `notifications` table (DB driver).

### `password_reset_tokens`
> Laravel default.

---

## 3. API Endpoints

Base prefix: `/api/v1`

### Auth
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| POST | /auth/register | guest | Register new student |
| POST | /auth/login | guest | Login, return Sanctum token |
| POST | /auth/logout | bearer | Revoke current token |
| POST | /auth/forgot-password | guest | Send password reset email |
| POST | /auth/reset-password | guest | Reset password via token |
| GET | /auth/verify-email/{id}/{hash} | signed URL | Verify email address |
| POST | /auth/email/resend | bearer | Resend verification email |

### Users
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /user | bearer | Get own profile |
| PUT | /user | bearer | Update own profile |
| POST | /user/avatar | bearer | Upload avatar |
| GET | /instructors/{user} | public | Instructor public profile |

### Categories
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /categories | public | List all categories (tree) |
| POST | /categories | admin | Create category |
| PUT | /categories/{category} | admin | Update category |
| DELETE | /categories/{category} | admin | Delete category |

### Tags
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /tags | public | List all tags |
| POST | /tags | admin | Create tag |
| DELETE | /tags/{tag} | admin | Delete tag |

### Courses
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /courses | public | Browse published courses (filter, search, paginate) |
| POST | /courses | instructor | Create course (draft) |
| GET | /courses/{course} | public* | View course detail (*full content if enrolled) |
| PUT | /courses/{course} | instructor | Update own course |
| DELETE | /courses/{course} | instructor/admin | Soft delete course |
| POST | /courses/{course}/thumbnail | instructor | Upload thumbnail |
| POST | /courses/{course}/submit | instructor | Submit for review (draft→pending) |
| POST | /courses/{course}/publish | admin | Approve & publish course |
| POST | /courses/{course}/archive | instructor/admin | Archive course |

### Sections
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /courses/{course}/sections | public* | List sections + lectures |
| POST | /courses/{course}/sections | instructor | Create section |
| PUT | /courses/{course}/sections/{section} | instructor | Update section |
| DELETE | /courses/{course}/sections/{section} | instructor | Delete section |
| POST | /courses/{course}/sections/reorder | instructor | Reorder sections |

### Lectures
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| POST | /sections/{section}/lectures | instructor | Create lecture |
| PUT | /sections/{section}/lectures/{lecture} | instructor | Update lecture |
| DELETE | /sections/{section}/lectures/{lecture} | instructor | Delete lecture |
| POST | /sections/{section}/lectures/reorder | instructor | Reorder lectures |
| POST | /lectures/{lecture}/video | instructor | Upload lecture video |

### Enrollments
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /enrollments | student | List enrolled courses |
| POST | /enrollments | student | Enroll (free course only) |
| GET | /enrollments/{enrollment} | student | Enrollment detail + progress |
| POST | /enrollments/{enrollment}/lectures/{lecture}/complete | student | Mark lecture complete |

### Payments
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| POST | /payments/checkout | student | Create Stripe checkout session |
| POST | /payments/webhook | public (Stripe sig) | Handle Stripe webhook |
| GET | /payments | student | Own payment history |
| POST | /payments/{payment}/refund | student | Request refund |
| GET | /admin/payments | admin | All payments |
| PUT | /admin/payments/{payment}/refund | admin | Approve/reject refund |

### Reviews
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /courses/{course}/reviews | public | List reviews for course |
| POST | /courses/{course}/reviews | student | Post review (must be enrolled) |
| PUT | /courses/{course}/reviews/{review} | student | Edit own review |
| DELETE | /courses/{course}/reviews/{review} | student/admin | Soft delete review |
| POST | /courses/{course}/reviews/{review}/reply | instructor | Reply to review |

### Quizzes
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| POST | /lectures/{lecture}/quiz | instructor | Create quiz for lecture |
| PUT | /lectures/{lecture}/quiz | instructor | Update quiz |
| POST | /lectures/{lecture}/quiz/attempt | student | Submit quiz attempt |
| GET | /lectures/{lecture}/quiz/attempts | student | Own attempts for quiz |

### Notifications
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /notifications | bearer | List own notifications |
| POST | /notifications/read-all | bearer | Mark all read |
| PUT | /notifications/{id} | bearer | Mark single read |

### Admin
| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | /admin/users | admin | List all users |
| PUT | /admin/users/{user}/ban | admin | Ban/unban user |
| PUT | /admin/users/{user}/role | admin | Change user role |
| GET | /admin/courses | admin | All courses (any status) |
| GET | /admin/stats | admin | Platform stats |

---

## 4. Laravel Implementation Notes

### Auth
- **Sanctum** token auth with `auth:sanctum` middleware
- `CreateNewUser` action (Fortify pattern) for registration
- `UpdateUserProfileInformation` action for profile update
- `PasswordBroker` + `Password::reset()` for reset flow
- Middleware: `verified` (email verification) on enrollment/payment routes
- **Policy**: `UserPolicy` for own-profile guard
- **Form Requests**: `LoginRequest`, `RegisterRequest`, `ResetPasswordRequest`

### Courses
- **Policy**: `CoursePolicy` — instructors own their courses, admins override
- **Eloquent Scopes**: `PublishedScope`, `scopeFiltered()` for search/filter
- **API Resources**: `CourseResource`, `CourseDetailResource` (full vs. list view)
- **Observer**: `CourseObserver` — auto-generate slug on create, clear cache on publish
- **Events**: `CourseSubmittedForReview`, `CoursePublished`
- **Spatie Media Library** or manual S3 for thumbnail storage
- Full-text search: MySQL FULLTEXT index on `title` + `description`, or Scout+Meilisearch

### Sections & Lectures
- **Policy**: `LecturePolicy` — check `$lecture->section->course->user_id === $user->id`
- Reorder endpoint uses array of `[id, position]` pairs, bulk update
- Video upload returns signed S3 URL or triggers `ProcessVideoUpload` job

### Enrollment
- **Gate**: `enroll` — checks not already enrolled, course is published
- **Observer**: `EnrollmentObserver` — fire `StudentEnrolled` event on create
- Progress computed from `lecture_progress` count vs. total lectures in course
- Completion check in `LectureProgressService` — fires `CourseCompleted` if 100%

### Payments
- **cashier-stripe** or raw Stripe SDK
- Stripe webhook verified via `Stripe\Webhook::constructEvent()` with secret
- `PaymentController::webhook` — `NoSessionExpiration`, `VerifyCsrfToken` excluded
- **Job**: `ProcessPaymentWebhook` queued for idempotent enrollment after payment
- **Policy**: `PaymentPolicy` — students see own payments only

### Reviews
- **Policy**: `ReviewPolicy` — `create` gate checks active enrollment exists
- Average rating stored as computed or updated via `ReviewObserver` updating `courses.rating_cache` (optional denormalization)
- **Form Request**: `StoreReviewRequest` — rating 1–5, unique per user+course

### Quizzes
- Attempt answers stored as JSON snapshot (answer IDs at time of attempt)
- Score computed server-side on submission, never trust client score
- **Policy**: `QuizPolicy` — attempt only if enrolled in parent course

### Notifications
- Laravel DB notifications via `Notifiable` trait on `User`
- `StudentEnrolled` → `InstructorNewEnrollmentNotification`
- `CoursePublished` → `InstructorCourseApprovedNotification`
- `CourseSubmittedForReview` → `AdminCourseReviewNeededNotification`
- `PaymentCompleted` → `StudentPaymentReceiptNotification`

### Admin
- `EnsureUserIsAdmin` middleware (checks `$user->role === 'admin'`)
- Stats endpoint aggregates with Eloquent aggregate methods + caching (Redis, 5 min TTL)

---

## 5. Auth Strategy

**Package**: Laravel Sanctum

**Token Flow**:
1. SPA calls `POST /api/v1/auth/login`
2. Server returns `{ token, user }` — SPA stores token in memory or `httpOnly` cookie
3. All protected requests: `Authorization: Bearer <token>`
4. Logout revokes current token via `$request->user()->currentAccessToken()->delete()`

**Roles** (stored as `users.role` enum):
| Role | Capabilities |
|------|-------------|
| `student` | Enroll, pay, review, track progress |
| `instructor` | All student abilities + create/manage own courses, reply to reviews |
| `admin` | Full access: approve courses, manage users, handle refunds, view stats |

**Middleware Groups**:
```
auth:sanctum          → any authenticated user
auth:sanctum + role:instructor
auth:sanctum + role:admin
```
`CheckRole` middleware reads `$request->user()->role`.

**Token Abilities** (optional granularity):
- `course:manage` issued to instructor tokens
- `admin:access` issued to admin tokens

**Email Verification**: enforced on enrollment + payment routes via `EnsureEmailIsVerified` middleware.

---

## 6. File Storage Plan

**Driver**: S3 (or S3-compatible: MinIO for local dev, Cloudflare R2 for prod)

### Buckets / Prefixes

| Asset | Path | Access |
|-------|------|--------|
| Course thumbnails | `thumbnails/{course_id}/{uuid}.{ext}` | public-read |
| Lecture videos | `videos/{course_id}/{lecture_id}/{uuid}.mp4` | **private** (signed URL) |
| Lecture attachments | `attachments/{lecture_id}/{uuid}.{ext}` | private (signed URL) |
| User avatars | `avatars/{user_id}/{uuid}.{ext}` | public-read |

**Signed URLs**: generated via `Storage::temporaryUrl()` for private assets (24h expiry for videos).

**Video Upload Flow**:
1. Instructor calls `POST /lectures/{lecture}/video`
2. Server generates S3 presigned **upload** URL → returns to client
3. Client uploads directly to S3
4. Client pings `PUT /lectures/{lecture}/video/confirm` with S3 object key
5. Server dispatches `ProcessVideoUpload` job to validate + extract duration

**Local Dev**: `filesystem.php` with `local` driver + Vite proxy or `php artisan storage:link`.

---

## 7. Queue & Jobs

**Queue Driver**: Redis (prod). `sync` for local dev.

| Job | Trigger | Description |
|-----|---------|-------------|
| `SendEnrollmentConfirmationEmail` | `StudentEnrolled` event | Email student enrollment receipt |
| `SendPaymentReceiptEmail` | `PaymentCompleted` event | Email payment confirmation |
| `ProcessPaymentWebhook` | Stripe webhook | Idempotent: create payment record + enrollment |
| `ProcessVideoUpload` | Video confirm endpoint | Validate S3 object exists, extract duration via FFprobe, update lecture |
| `SendCourseApprovedNotification` | `CoursePublished` event | Notify instructor via email + DB |
| `SendCourseSubmittedNotification` | `CourseSubmittedForReview` event | Notify admins |
| `GenerateCompletionCertificate` | `CourseCompleted` event | PDF cert generation, store S3, notify student |
| `UpdateCourseRatingCache` | `ReviewCreated` / `ReviewDeleted` event | Recompute avg rating on course |

**Queue Configuration**:
- High-priority queue: `payments`, `webhooks`
- Default queue: `emails`, `notifications`
- Low-priority queue: `video-processing`

**Failed Jobs**: `failed_jobs` table. Retry up to 3x with exponential backoff.

---

## 8. Suggested Package List

| Package | Use | Reason |
|---------|-----|--------|
| `laravel/sanctum` | API token auth | Already in Laravel, SPA-first |
| `stripe/stripe-php` | Payment processing | Official Stripe SDK |
| `spatie/laravel-permission` | Role management | If roles grow complex (skip if enum suffices) |
| `spatie/laravel-query-builder` | Filtering/sorting API resources | Cleaner than manual filter logic |
| `spatie/laravel-media-library` | File attachments | Only if media management grows complex |
| `laravel/scout` + `meilisearch/meilisearch-php` | Full-text course search | When MySQL FULLTEXT isn't enough |
| `barryvdh/laravel-dompdf` | Certificate PDF generation | Lightweight, no JS required |
| `league/flysystem-aws-s3-v3` | S3 file storage | S3 driver for Flysystem |
| `laravel/horizon` | Queue monitoring | Redis queue dashboard |
| `laravel/telescope` | Local debugging | Dev only, exclude from prod |

> `spatie/laravel-permission` optional — start with `role` enum on `users`, add package only if per-model permissions needed later.

---

## Development Priorities (Phased)

### Phase 1 — Core
- Auth (register, login, logout, verify, reset)
- Course CRUD + sections + lectures
- Enrollment (free courses)
- Basic file upload (thumbnails)

### Phase 2 — Monetization
- Payments via Stripe
- Paid enrollment via webhook
- Payment history + refund requests

### Phase 3 — Learning Experience
- Lecture progress tracking
- Quiz system
- Course completion + certificates

### Phase 4 — Community & Polish
- Reviews + ratings
- Instructor analytics
- Admin dashboard
- Notifications
- Full-text search
- Video upload (presigned S3)
