<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function create(User $user, Lecture $lecture): bool
    {
        if ($user->role !== UserRole::Instructor) {
            return false;
        }

        return $lecture->section()->whereHas('course', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })->exists();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        if ($user->role !== UserRole::Instructor) {
            return false;
        }

        return $quiz->lecture()->whereHas('section.course', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })->exists();
    }

    public function attempt(User $user, Quiz $quiz, Enrollment $enrollment): bool
    {
        if ($user->role !== UserRole::Student) {
            return false;
        }

        if ($enrollment->user_id !== $user->id) {
            return false;
        }

        return $quiz->lecture()->whereHas('section', function ($query) use ($enrollment): void {
            $query->where('course_id', $enrollment->course_id);
        })->exists();
    }

    public function viewAttempts(User $user, Quiz $quiz, Enrollment $enrollment): bool
    {
        return $this->attempt($user, $quiz, $enrollment);
    }
}
