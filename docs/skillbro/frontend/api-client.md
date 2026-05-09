# Frontend API Client Contract

Client composable: `resources/js/composables/useSkillbroApi.ts`

## Responsibilities

- token persistence
- auth header injection
- request normalization
- api error normalization
- endpoint methods per domain

## Token behavior

- Storage key: `skillbro_api_token`
- Bearer header auto-added for authenticated calls

## Domain method groups

- Auth
- User/Profile
- Categories/Tags
- Courses/Sections/Lectures
- Enrollments
- Payments
- Reviews
- Quizzes
- Notifications
- Admin

## Types

All response types in `resources/js/types/skillbro-api.ts`.
