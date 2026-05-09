# Payments and Refunds (Gateway-Agnostic)

## Goal

Showcase-ready paid enrollment flow without binding system to single provider.

## Entities

- `payments`
- status enum: `pending`, `completed`, `refunded`, `failed`

## Endpoints

- `POST /payments/checkout`
- `POST /payments/webhook`
- `GET /payments`
- `POST /payments/{payment}/refund`
- `GET /admin/payments`
- `PUT /admin/payments/{payment}/refund`

## Current flow

1. Student requests checkout for paid published course.
2. System creates pending payment + checkout/session identifiers.
3. Webhook marks payment completed/failed.
4. On completion, system idempotently creates enrollment.
5. Student requests refund.
6. Admin approves/rejects refund request.

## Important

- Implementation intentionally provider-neutral at API contract layer.
- DB and API naming both provider-neutral (`payment_intent_id`, `checkout_session_id`).
