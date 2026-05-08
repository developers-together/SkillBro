# SkillBro System Documentation

## 1. Overview

SkillBro is API-first learning platform built with Laravel 13 + Vue 3.

- Backend: REST API under `/api/v1`.
- Frontend: Vue SPA shell at `/skillbro` (API-only, no Inertia for SkillBro workspace).
- Auth: Laravel Sanctum token auth.
- Roles: `student`, `instructor`, `admin`.

## 2. Tech Stack

- PHP 8.4
- Laravel 13
- Sanctum
- Fortify
- Pest / PHPUnit
- Vue 3 + TypeScript
- Tailwind v4
- Vite

## 3. Runtime Architecture

### Backend layers

- Routes: `routes/api.php`
- Controllers: `app/Http/Controllers/Api/V1/**`
- Validation: `app/Http/Requests/**`
- Domain models: `app/Models/**`
- Serialization: `app/Http/Resources/**`
- Authorization: policies in `app/Policies/**` + role middleware `app/Http/Middleware/CheckRole.php`

### Frontend layers

- SPA entry: `resources/js/skillbro/main.ts`
- Main UI shell: `resources/js/skillbro/SkillbroApp.vue`
- API client/composable: `resources/js/composables/useSkillbroApi.ts`
- Shared types: `resources/js/types/skillbro-api.ts`
- Blade mount shell: `resources/views/skillbro.blade.php`

## 4. Data Model

Core tables:

- `users`
- `personal_access_tokens`
- `categories`
- `tags`
- `course_tag`
- `courses`
- `sections`
- `lectures`
- `enrollments`
- `lecture_progress`
- `quizzes`
- `quiz_questions`
- `quiz_answers`
- `quiz_attempts`
- `payments`
- `reviews`
- `notifications`
- `password_reset_tokens`

### Key relations

- User has many Courses, Enrollments, Reviews, Payments
- Course belongs to Instructor(User), Category; has many Sections, Enrollments, Reviews, Payments
- Section has many Lectures
- Lecture has one Quiz
- Enrollment has many LectureProgress and QuizAttempts
- Payment belongs to User and Course

## 5. Roles and Access Rules

### Student

- Register/login/logout
- View/browse courses
- Enroll in free course
- Buy paid course via checkout
- Request refund
- Mark lecture complete
- Attempt quizzes
- Review enrolled course
- View own notifications/payments/profile

### Instructor

- All student capabilities
- Create/update/delete own courses
- Submit/archive courses
- Manage sections/lectures
- Upload lecture video
- Create/update quizzes for quiz lectures
- Reply to reviews

### Admin

- All read/write controls over governance endpoints
- Publish courses
- Manage users (role/ban)
- View stats
- View payments and approve/reject refund requests

## 6. API Surface

Base prefix: `/api/v1`

### Auth

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/verify-email/{id}/{hash}` (signed)
- `POST /auth/email/resend`

### Public Discovery

- `GET /categories`
- `GET /tags`
- `GET /courses`
- `GET /courses/{course}`
- `GET /courses/{course}/sections`
- `GET /courses/{course}/reviews`
- `GET /instructors/{user}`

### User Profile

- `GET /user`
- `PUT /user`
- `POST /user/avatar`

### Categories / Tags (admin write)

- `POST /categories`
- `PUT /categories/{category}`
- `DELETE /categories/{category}`
- `POST /tags`
- `DELETE /tags/{tag}`

### Courses

- `POST /courses`
- `PUT /courses/{course}`
- `DELETE /courses/{course}`
- `POST /courses/{course}/thumbnail`
- `POST /courses/{course}/submit`
- `POST /courses/{course}/publish` (admin)
- `POST /courses/{course}/archive`

### Sections

- `POST /courses/{course}/sections`
- `PUT /courses/{course}/sections/{section}`
- `DELETE /courses/{course}/sections/{section}`
- `POST /courses/{course}/sections/reorder`

### Lectures

- `POST /sections/{section}/lectures`
- `PUT /sections/{section}/lectures/{lecture}`
- `DELETE /sections/{section}/lectures/{lecture}`
- `POST /sections/{section}/lectures/reorder`
- `POST /lectures/{lecture}/video`

### Enrollment

- `GET /enrollments`
- `POST /enrollments`
- `GET /enrollments/{enrollment}`
- `POST /enrollments/{enrollment}/lectures/{lecture}/complete`

### Payments

- `POST /payments/checkout`
- `POST /payments/webhook` (public)
- `GET /payments`
- `POST /payments/{payment}/refund`
- `GET /admin/payments` (admin)
- `PUT /admin/payments/{payment}/refund` (admin)

### Reviews

- `POST /courses/{course}/reviews`
- `PUT /courses/{course}/reviews/{review}`
- `DELETE /courses/{course}/reviews/{review}`
- `POST /courses/{course}/reviews/{review}/reply`

### Quizzes

- `POST /lectures/{lecture}/quiz`
- `PUT /lectures/{lecture}/quiz`
- `POST /lectures/{lecture}/quiz/attempt`
- `GET /lectures/{lecture}/quiz/attempts`

### Notifications

- `GET /notifications`
- `POST /notifications/read-all`
- `PUT /notifications/{notificationId}`

### Admin

- `GET /admin/users`
- `PUT /admin/users/{user}/ban`
- `PUT /admin/users/{user}/role`
- `GET /admin/courses`
- `GET /admin/stats`

## 7. Payment Flow (Current Implementation)

1. Student calls `POST /payments/checkout` with `course_id`.
2. API creates `payments` row with `pending` status and returns checkout URL/session id.
3. Webhook (`POST /payments/webhook`) marks payment completed and creates enrollment idempotently.
4. Student can request refund (`POST /payments/{id}/refund`).
5. Admin approves/rejects via `PUT /admin/payments/{id}/refund`.

## 8. Frontend Behavior

`/skillbro` provides operation console by domain:

- Auth
- Users
- Categories & Tags
- Courses / Sections / Lectures
- Enrollment
- Payments
- Reviews
- Quizzes
- Notifications
- Admin

Token handling:

- Token persisted in localStorage key `skillbro_api_token`.
- Composable adds `Authorization: Bearer <token>` automatically.

## 9. Setup and Run

### Install

```bash
composer install --no-interaction --prefer-dist
npm install
```

### Environment

```bash
cp .env.example .env
php artisan key:generate --force --no-interaction
```

### Database

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate --force --no-interaction
```

### Serve

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

- `http://127.0.0.1:8000/skillbro`

## 10. Validation Commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run lint:check
npm run types:check
npm run build
```

## 11. Test Coverage Map

Main test files:

- `tests/Feature/AuthenticationTest.php`
- `tests/Feature/AuthApiExtensionsTest.php`
- `tests/Feature/CourseTest.php`
- `tests/Feature/SectionLectureTest.php`
- `tests/Feature/EnrollmentTest.php`
- `tests/Feature/ReviewApiTest.php`
- `tests/Feature/QuizApiTest.php`
- `tests/Feature/NotificationApiTest.php`
- `tests/Feature/TagApiTest.php`
- `tests/Feature/CategoryTest.php`
- `tests/Feature/AdminStatsApiTest.php`
- `tests/Feature/PaymentApiTest.php`
- `tests/Feature/SkillbroFrontendRouteTest.php`

## 12. Operational Notes

- API returns JSON for all `/api/*` paths.
- Role middleware strictly enforces exact role match.
- Course and review use soft deletes.
- Payment webhook implementation currently internal/mock-compatible and ready to replace with real Stripe signature verification + SDK event parsing.
- For production, move token storage to more hardened strategy (httpOnly cookie pattern) if required by threat model.

## 13. Next Upgrade Targets

- Stripe SDK signature verification for webhook
- Certificate generation flow on completion
- Instructor revenue analytics endpoint
- Rich frontend pages per role (student/instructor/admin dashboards)
