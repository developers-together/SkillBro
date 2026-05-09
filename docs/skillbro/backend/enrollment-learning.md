# Enrollment and Learning Progress

## Entities

- `enrollments`
- `lecture_progress`

## Flows

- Free course enrollment via `POST /enrollments`.
- Lecture completion via `POST /enrollments/{enrollment}/lectures/{lecture}/complete`.
- Enrollment detail includes progress snapshot.

## Completion logic

When completed lectures count reaches total course lecture count, enrollment `completed_at` set.

## Rules

- Enrollment unique per user/course.
- Student cannot mutate another student's enrollment.
