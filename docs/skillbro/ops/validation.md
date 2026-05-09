# Build and Validation

## Backend

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Frontend

```bash
npm run lint:check
npm run types:check
npm run build
```

## Expected

- all tests pass
- no lint/type errors
- Vite build succeeds
