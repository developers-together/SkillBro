<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class CourseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = Course::published()
            ->filtered($request->only(['search', 'category', 'level', 'price_max', 'free']))
            ->with(['instructor', 'category'])
            ->withCount(['sections', 'enrollments'])
            ->latest('id')
            ->paginate(15);

        return CourseResource::collection($courses);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $course = $request->user()->courses()->create($data);

        if ($tags) {
            $course->tags()->sync($tags);
        }

        return response()->json(
            new CourseDetailResource($course->fresh()->load(['instructor', 'category', 'tags', 'sections'])),
            201
        );
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $course->load(['instructor', 'category', 'tags', 'sections.lectures']);

        // Signal to LectureResource whether the authenticated user is enrolled
        $isEnrolled = $request->user()
            && $course->enrollments()->where('user_id', $request->user()->id)->exists();

        $request->attributes->set('is_enrolled', $isEnrolled);

        return response()->json(new CourseDetailResource($course));
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $course->update($data);

        if ($tags !== null) {
            $course->tags()->sync($tags);
        }

        return response()->json(new CourseDetailResource($course->fresh()->load(['instructor', 'category', 'tags', 'sections'])));
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return response()->json(null, 204);
    }

    public function uploadThumbnail(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $request->validate([
            'thumbnail' => ['required', File::image()->max(5 * 1024)->mimes('jpg', 'jpeg', 'png', 'webp')],
        ]);

        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        // UUID-namespaced path prevents cross-instructor collision on numeric course IDs
        $path = $request->file('thumbnail')->store('thumbnails/'.Str::uuid(), 'public');

        $course->update(['thumbnail' => $path]);

        return response()->json(['thumbnail' => $path]);
    }

    public function submit(Course $course): JsonResponse
    {
        $this->authorize('submit', $course);

        $course->update(['status' => CourseStatus::Pending]);

        return response()->json(['status' => CourseStatus::Pending->value]);
    }

    public function publish(Course $course): JsonResponse
    {
        $this->authorize('publish', $course);

        $course->update(['status' => CourseStatus::Published]);

        return response()->json(['status' => CourseStatus::Published->value]);
    }

    public function archive(Course $course): JsonResponse
    {
        $this->authorize('archive', $course);

        $course->update(['status' => CourseStatus::Archived]);

        return response()->json(['status' => CourseStatus::Archived->value]);
    }
}
