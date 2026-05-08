<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    public function show(User $user): JsonResponse
    {
        abort_if($user->role !== UserRole::Instructor, 404);

        $courses = $user->courses()
            ->published()
            ->with(['category', 'instructor'])
            ->withCount('enrollments')
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'instructor' => new UserResource($user),
            'courses' => CourseResource::collection($courses)->response()->getData(true),
        ]);
    }
}
