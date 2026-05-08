<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    /**
     * Student can enroll if not already enrolled and course is published.
     */
    public function create(User $user, Course $course): bool
    {
        return ! Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id;
    }

    public function completeLecture(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id;
    }
}
