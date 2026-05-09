# Admin Domain

## Endpoints

- Users list
- Ban/unban
- Role change
- Courses list across statuses
- Stats aggregation
- Payments list + refund decisions

## Stats

Includes:

- user totals by role
- course totals by status
- enrollment totals/completed
- revenue totals (completed/refunded)

## Caching

Stats endpoint cached with short TTL (5 min) for stability/perf.
