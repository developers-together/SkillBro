# Deployment Notes

## Production concerns

- Use managed DB (not local sqlite).
- Use queue worker for async jobs/events.
- Configure mail driver for verification/reset emails.
- Configure storage disk for media uploads.

## Security hardening

- enforce strict CORS
- rotate keys/secrets
- consider httpOnly cookie strategy for SPA auth if needed
- add rate limiting on sensitive endpoints

## Observability

- request/error logging
- webhook event audit log
- admin action log
