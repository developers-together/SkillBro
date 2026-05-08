<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courses = Course::withTrashed()
            ->with(['instructor', 'category'])
            ->withCount('enrollments')
            ->latest('id')
            ->paginate(25);

        return response()->json(CourseResource::collection($courses));
    }
}
