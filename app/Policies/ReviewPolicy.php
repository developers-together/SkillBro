<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'delete' && $user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    public function create(User $user, Course $course): bool
    {
        if ($user->role !== UserRole::Student) {
            return false;
        }

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function reply(User $user, Review $review): bool
    {
        if ($user->role !== UserRole::Instructor) {
            return false;
        }

        return $review->course()->where('user_id', $user->id)->exists();
    }
}
