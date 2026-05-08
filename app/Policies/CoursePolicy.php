<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Admins bypass all policy checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        if ($course->status === CourseStatus::Published) {
            return true;
        }

        return $user?->id === $course->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Instructor;
    }

    public function update(User $user, Course $course): bool
    {
        return $course->isOwnedBy($user);
    }

    public function delete(User $user, Course $course): bool
    {
        return $course->isOwnedBy($user);
    }

    /**
     * Instructor can submit a draft course for review.
     */
    public function submit(User $user, Course $course): bool
    {
        return $course->isOwnedBy($user) && $course->status === CourseStatus::Draft;
    }

    /**
     * Admin-only — handled by before() returning true for admins.
     */
    public function publish(User $user, Course $course): bool
    {
        return false;
    }

    public function archive(User $user, Course $course): bool
    {
        return $course->isOwnedBy($user);
    }
}
