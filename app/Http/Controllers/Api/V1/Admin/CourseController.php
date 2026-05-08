<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = Course::withTrashed()
            ->with(['instructor', 'category'])
            ->withCount('enrollments')
            ->latest('id')
            ->paginate(25);

        return CourseResource::collection($courses);
    }
}
