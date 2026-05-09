# Reviews, Quizzes, Notifications

## Reviews

- Student can create one review per enrolled course.
- Student can edit/delete own review.
- Instructor can reply.
- Admin may remove abusive content.

## Quizzes

- Quiz attach to lecture (`type=quiz`).
- Questions + answers stored server-side.
- Attempt scoring always computed server-side.
- Attempts query scoped to enrollment ownership.

## Notifications

- DB notifications via Laravel notifications table.
- Endpoints: list, mark one read, mark all read.
