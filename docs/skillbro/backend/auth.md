# Auth and Identity

## Endpoints

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/verify-email/{id}/{hash}` (signed)
- `POST /auth/email/resend`

## Behavior

- Register returns token + user.
- Login rejects banned users.
- Logout revokes current access token.
- Forgot/reset use Laravel password broker.
- Verify endpoint validates signature and email hash.

## Model requirements

`User` implements `MustVerifyEmail` and uses `MustVerifyEmail` trait.

## Notes

- For public APIs, consider request throttling and abuse mitigation hardening.
