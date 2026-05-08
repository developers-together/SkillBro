# SkillBro Phase 1 — Code Review

---

## 🔴 Bugs (will break in production)

**EnrollmentController.php:L88**
`$enrollment->course->lectures()` — lazy-loads `course` every call. If `course` relation not pre-loaded and `course_id` row is soft-deleted, returns null, fatal. Eager-load `course.lectures` before `checkCourseCompletion()`.

**LectureResource.php:L24**
`$this->resource->relationLoaded('progress')` — `Lecture` has no `progress` relation; name should be `lectureProgress` and it lives on `Enrollment`, not `Lecture`. The content guard never fires for enrolled users. Replace with a flag passed through the resource or check enrollment via request context.

**CourseController.php:L35**
`Course::findOrFail()` on `EnrollmentController::store()` after `exists:courses,id` validation — double query on every enroll. Use route model binding for `course_id` instead, or at minimum reuse the already-validated ID without the extra find.

---

## 🟡 Risks (fragile, will bite under load)

**EnrollmentController.php:L42-48**
`DB::transaction()` wraps a single `Enrollment::create()` with no other queries — transaction adds overhead with zero isolation benefit here. Remove; the `unique(['user_id', 'course_id'])` DB constraint already handles the race condition atomically.

**EnrollmentController.php:L69-79**
`LectureProgress::firstOrCreate()` is not atomic — race condition between the `SELECT` and `INSERT`. Two concurrent requests can both pass the `firstOrCreate` check and attempt duplicate inserts. Use `insertOrIgnore()` or wrap in `DB::transaction()` with `lockForUpdate()`.

**CourseObserver.php:L36-44**
Slug uniqueness loop runs `N` queries in a while loop with no upper bound. Under concurrent creation of identically-titled courses, this will loop or produce a race. Use a single `MAX(slug LIKE 'title-%')` query + atomic DB unique constraint to enforce, letting the constraint be the truth.

**CheckRole.php:L21**
`$userRole->value !== $role` — string comparison. If a new `UserRole` case is added with a different value string (e.g. `SuperAdmin`), the check silently fails open for any misspelled role arg passed to middleware. Compare enum directly: `$userRole !== UserRole::from($role)` wrapped in a `try/catch ValueError` → 403.

**EnrollmentController.php:L37-38**
`abort_if()` runs before `$this->authorize()`. Correct order is: authorize first (gate), then validate business rules. As-is, a banned student can probe whether any course ID exists (gets 422 not 403).

**SendEnrollmentConfirmationEmail.php:L25**
Lazy-loads `$event->enrollment->student` inside a queued job. If the `User` is deleted between dispatch and processing, this throws `Attempt to get property on null`. Load the user in the event constructor or add a null guard.

**routes/api.php:L17-21**
No rate limiting on auth routes. `POST /auth/register` and `POST /auth/login` are wide open to brute force. Add `->middleware('throttle:6,1')` on the auth group (6 req/min/IP).

---

## 🟡 Missing Indexes (query performance)

**create_courses_table.php**
`title` and `description` used in `LIKE` search (Course:L117-118) with no full-text index. `LIKE '%term%'` does a full table scan. Add `$table->fullText(['title', 'description'])` for MySQL, or plan for Scout/Meilisearch in Phase 4.

**create_lectures_table.php** (not reviewed but inferred)
`section_id` + `position` are used for ordered queries. Add `$table->index(['section_id', 'position'])`.

**create_sections_table.php** (inferred)
Same pattern — `$table->index(['course_id', 'position'])`.

---

## 🟡 Security

**ProfileController.php:L33-34 / CourseController.php:L92**
`File::image()` validates by MIME sniffing only. A crafted PNG containing PHP code passes. Add `->mimes('jpg', 'jpeg', 'png', 'webp')` to restrict to safe extensions explicitly.

**CourseController.php:L99**
`$request->file('thumbnail')->store(...)` path uses `$course->id` (integer, safe). OK. But `thumbnails/{id}/` lets any authenticated instructor overwrite another's thumbnail if they know the numeric ID. Use a UUID-namespaced path: `thumbnails/{uuid}`.

**EnrollmentPolicy.php:L24-29 / Admin\UserController.php**
Admin `before()` bypass returns `true` for **all** abilities including `create` (enroll). An admin can enroll themselves in a course as a student and receive the enrollment confirmation email. Intentional? Clarify and document.

---

## 🔵 Nits

**Course.php:L124**
`$this->price == 0` — loose comparison on `decimal:2` cast. Use `$this->price <= 0` or cast to `float` and compare with `===`.

**EnrollmentController.php:L26**
`response()->json(EnrollmentResource::collection($enrollments))` — wraps paginator in an extra `{"data": {...}}` envelope because `json()` double-wraps. Return `EnrollmentResource::collection($enrollments)` directly (same fix already applied to `CourseController::index()`).

**User.php:L43**
`courses()` relation on User returns all courses. When used as `$request->user()->courses()->create()` in `CourseController`, this is correct. But the relationship name conflicts with the concept of "enrolled courses" — consider aliasing as `instructedCourses()` for clarity.

**CoursePolicy.php:L64-67**
`publish()` always returns `false` and relies on `before()` admin bypass. Fine, but `submit()` allows re-submitting a `pending` course back to `draft` via observer? No — `submit()` only allows `Draft` status. But `archive()` has no status guard — a `draft` course can be archived directly, skipping the review flow. Add status check: only `published` courses can be archived.

**routes/api.php:L55-59**
`GET /courses/{course}/sections` inside `auth:sanctum` group means unauthenticated users can't browse a published course's section list. Move the `index` route to the public group.

**SectionPolicy.php / LecturePolicy.php**
`$section->course()->first()` executes a query on every policy check. Pre-load the relation at the route model binding level using `Route::bind()` or a custom `ScopeBindings` — or cache with `once()` inside the policy.

---

## ❓ Questions

**EnrollmentController.php:L65**
`completeLecture` doesn't verify `$lecture->section->course_id === $enrollment->course_id`. Can a student mark a lecture from a different course as complete on their enrollment? Add that guard.

**CourseController.php:L106-113**
`submit()` transitions `draft → pending` but the policy only checks `status === Draft`. Who resets a `pending` course back to `draft` if the instructor wants to edit? No `reject` endpoint exists. Phase 2 concern, but worth deciding now before the DB has pending courses that are stuck.

**Listener (SendEnrollmentConfirmationEmail)**
No `failed()` method. If all 3 retries exhaust, the failure is silently swallowed — no log, no dead-letter handling. Add `failed(StudentEnrolled $event, Throwable $e): void` that at minimum logs the failure.
