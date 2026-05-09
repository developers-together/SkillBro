# API Reference (Endpoint Matrix)

Base: `/api/v1`

## Auth

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/verify-email/{id}/{hash}`
- `POST /auth/email/resend`

## Public discovery

- `GET /categories`
- `GET /tags`
- `GET /courses`
- `GET /courses/{course}`
- `GET /courses/{course}/sections`
- `GET /courses/{course}/reviews`
- `GET /instructors/{user}`

## Profile

- `GET /user`
- `PUT /user`
- `POST /user/avatar`

## Category/Tag admin writes

- `POST /categories`
- `PUT /categories/{category}`
- `DELETE /categories/{category}`
- `POST /tags`
- `DELETE /tags/{tag}`

## Courses

- `POST /courses`
- `PUT /courses/{course}`
- `DELETE /courses/{course}`
- `POST /courses/{course}/thumbnail`
- `POST /courses/{course}/submit`
- `POST /courses/{course}/publish`
- `POST /courses/{course}/archive`

## Sections

- `POST /courses/{course}/sections`
- `PUT /courses/{course}/sections/{section}`
- `DELETE /courses/{course}/sections/{section}`
- `POST /courses/{course}/sections/reorder`

## Lectures

- `POST /sections/{section}/lectures`
- `PUT /sections/{section}/lectures/{lecture}`
- `DELETE /sections/{section}/lectures/{lecture}`
- `POST /sections/{section}/lectures/reorder`
- `POST /lectures/{lecture}/video`

## Enrollment

- `GET /enrollments`
- `POST /enrollments`
- `GET /enrollments/{enrollment}`
- `POST /enrollments/{enrollment}/lectures/{lecture}/complete`

## Payments

- `POST /payments/checkout`
- `POST /payments/webhook`
- `GET /payments`
- `POST /payments/{payment}/refund`
- `GET /admin/payments`
- `PUT /admin/payments/{payment}/refund`

## Reviews

- `POST /courses/{course}/reviews`
- `PUT /courses/{course}/reviews/{review}`
- `DELETE /courses/{course}/reviews/{review}`
- `POST /courses/{course}/reviews/{review}/reply`

## Quizzes

- `POST /lectures/{lecture}/quiz`
- `PUT /lectures/{lecture}/quiz`
- `POST /lectures/{lecture}/quiz/attempt`
- `GET /lectures/{lecture}/quiz/attempts`

## Notifications

- `GET /notifications`
- `POST /notifications/read-all`
- `PUT /notifications/{notificationId}`

## Admin

- `GET /admin/users`
- `PUT /admin/users/{user}/ban`
- `PUT /admin/users/{user}/role`
- `GET /admin/courses`
- `GET /admin/stats`
