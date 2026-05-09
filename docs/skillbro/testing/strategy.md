# Testing Strategy

## Pillars

- Feature tests for API contracts and role permissions.
- Business invariants around enrollment/progress/quiz scoring.
- Integration checks for auth flows and refunds.

## Current status

- Pest suite green.
- Frontend lint/types/build green.
- Route shell test for `/skillbro` present.

## Validation commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run lint:check
npm run types:check
npm run build
```
