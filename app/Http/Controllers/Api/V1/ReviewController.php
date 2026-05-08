<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\ReplyReviewRequest;
use App\Http\Requests\Course\StoreReviewRequest;
use App\Http\Requests\Course\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(Course $course): AnonymousResourceCollection
    {
        $reviews = $course->reviews()
            ->with('student')
            ->latest('id')
            ->paginate(20);

        return ReviewResource::collection($reviews);
    }

    public function store(StoreReviewRequest $request, Course $course): JsonResponse
    {
        $this->authorize('create', [Review::class, $course]);

        $alreadyReviewed = Review::query()
            ->where('course_id', $course->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_if($alreadyReviewed, 422, 'You have already reviewed this course.');

        $review = Review::create([
            ...$request->validated(),
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(
            new ReviewResource($review->fresh()->load('student')),
            201,
        );
    }

    public function update(UpdateReviewRequest $request, Course $course, Review $review): JsonResponse
    {
        abort_unless($review->course_id === $course->id, 404);

        $this->authorize('update', $review);

        $review->update($request->validated());

        return response()->json(new ReviewResource($review->fresh()->load('student')));
    }

    public function destroy(Course $course, Review $review): JsonResponse
    {
        abort_unless($review->course_id === $course->id, 404);

        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(null, 204);
    }

    public function reply(ReplyReviewRequest $request, Course $course, Review $review): JsonResponse
    {
        abort_unless($review->course_id === $course->id, 404);

        $this->authorize('reply', $review);

        $review->update([
            'instructor_reply' => $request->string('instructor_reply')->toString(),
        ]);

        return response()->json(new ReviewResource($review->fresh()->load('student')));
    }
}
