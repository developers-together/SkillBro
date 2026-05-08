<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\LectureProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['course.instructor', 'course.category'])
            ->latest('enrolled_at')
            ->paginate(15);

        return EnrollmentResource::collection($enrollments);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        /** @var Course $course */
        $course = Course::find($request->integer('course_id'));

        // Authorize first — gate before business rules, so probing is not possible
        $this->authorize('create', [Enrollment::class, $course]);

        abort_if($course->status !== CourseStatus::Published, 422, 'Course is not available for enrollment.');
        abort_if(! $course->isFree(), 422, 'Paid courses require payment. Use the payments endpoint.');

        $enrollment = Enrollment::create([
            'user_id' => $request->user()->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return response()->json(
            new EnrollmentResource($enrollment->load(['course.instructor'])),
            201
        );
    }

    public function show(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['course.sections.lectures', 'lectureProgress']);

        return response()->json(new EnrollmentResource($enrollment));
    }

    public function completeLecture(Request $request, Enrollment $enrollment, Lecture $lecture): JsonResponse
    {
        $this->authorize('completeLecture', $enrollment);

        // Guard against marking a lecture from a different course
        abort_if(
            $lecture->section->course_id !== $enrollment->course_id,
            403,
            'Lecture does not belong to this enrollment\'s course.'
        );

        $progress = DB::transaction(function () use ($enrollment, $lecture): LectureProgress {
            /** @var LectureProgress|null $existing */
            $existing = LectureProgress::where('enrollment_id', $enrollment->id)
                ->where('lecture_id', $lecture->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->completed_at === null) {
                    $existing->update(['completed_at' => now()]);
                }

                return $existing;
            }

            return LectureProgress::create([
                'enrollment_id' => $enrollment->id,
                'lecture_id' => $lecture->id,
                'completed_at' => now(),
            ]);
        });

        $this->checkCourseCompletion($enrollment);

        return response()->json(['completed_at' => $progress->fresh()->completed_at?->toISOString()]);
    }

    private function checkCourseCompletion(Enrollment $enrollment): void
    {
        // Eager-load to avoid lazy-load crash if course is soft-deleted or not pre-loaded
        $enrollment->loadMissing('course');

        $totalLectures = $enrollment->course->lectures()->count();
        $completedLectures = $enrollment->lectureProgress()->whereNotNull('completed_at')->count();

        if ($totalLectures > 0 && $totalLectures === $completedLectures && ! $enrollment->isCompleted()) {
            $enrollment->update(['completed_at' => now()]);
        }
    }
}
