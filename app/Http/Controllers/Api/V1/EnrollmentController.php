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
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with(['course.instructor', 'course.category'])
            ->latest('enrolled_at')
            ->paginate(15);

        return response()->json(EnrollmentResource::collection($enrollments));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $course = Course::findOrFail($request->integer('course_id'));

        abort_if($course->status !== CourseStatus::Published, 422, 'Course is not available for enrollment.');
        abort_if(! $course->isFree(), 422, 'Paid courses require payment. Use the payments endpoint.');

        $this->authorize('create', [Enrollment::class, $course]);

        $enrollment = DB::transaction(function () use ($request, $course) {
            return Enrollment::create([
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
            ]);
        });

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

        $progress = LectureProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lecture_id' => $lecture->id,
            ],
            ['completed_at' => now()]
        );

        if (! $progress->wasRecentlyCreated && $progress->completed_at === null) {
            $progress->update(['completed_at' => now()]);
        }

        $this->checkCourseCompletion($enrollment);

        return response()->json(['completed_at' => $progress->completed_at?->toISOString()]);
    }

    private function checkCourseCompletion(Enrollment $enrollment): void
    {
        $totalLectures = $enrollment->course->lectures()->count();
        $completedLectures = $enrollment->lectureProgress()->whereNotNull('completed_at')->count();

        if ($totalLectures > 0 && $totalLectures === $completedLectures && ! $enrollment->isCompleted()) {
            $enrollment->update(['completed_at' => now()]);
        }
    }
}
