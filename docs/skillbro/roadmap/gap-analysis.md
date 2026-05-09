# Gap Analysis (Remaining Work)

This list = what still needed to claim full original plan completeness.

## High priority gaps

1. Completion certificate generation not implemented yet.
- Need endpoint or async job to generate/store certificate on full course completion.

2. Payment persistence hardening.
- Add webhook event id storage/replay protection.
- Add stronger audit trail for refund decisions.

3. Payment webhook security hardening pending.
- Current webhook good for showcase mock flow.
- For stronger production-like behavior, add signed webhook secret check + event replay protection.

4. Email verification enforcement on sensitive student actions.
- Original plan says verified middleware on enrollment/payment routes.
- Current routes allow auth users even if unverified.
- Add `verified` middleware on enrollment/payment groups.

5. Instructor revenue dashboard endpoint.
- Admin stats includes platform revenue totals.
- Per-instructor revenue analytics endpoint still missing.

## Medium priority gaps

6. Course publication moderation metadata.
- Current flow has pending/publish status.
- No explicit review notes/rejection reason workflow.

7. Rich role-specific frontend pages.
- Current `/skillbro` is universal API workspace console.
- Build separate polished student/instructor/admin UX pages for showcase depth.

8. File storage abstraction docs + config matrix.
- Need explicit docs for local/public/s3 variants by environment.

## Low priority / polish

9. API versioned response examples in docs.
10. Sequence diagrams for auth/enrollment/payment/refund flows.
11. Seed data script for demo day walkthrough.
