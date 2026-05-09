# Backend Routing and Middleware

## API prefix

All endpoints live under `/api/v1`.

## Middleware groups

- Public endpoints: no auth middleware.
- Authenticated endpoints: `auth:sanctum`.
- Role-restricted endpoints: `auth:sanctum` + `role:admin` (or route policy for instructor ownership).

## Security behavior

- API requests return JSON errors by default (configured in bootstrap exception handling).
- Signed URL required for email verification route.

## Route files

- API: `routes/api.php`
- Web mount for SPA: `routes/web.php` (`/skillbro` route)
