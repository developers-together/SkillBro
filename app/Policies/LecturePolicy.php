<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lecture;
use App\Models\User;

class LecturePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    public function create(User $user, Lecture $lecture): bool
    {
        return $lecture->section->course->isOwnedBy($user);
    }

    public function update(User $user, Lecture $lecture): bool
    {
        return $lecture->section->course->isOwnedBy($user);
    }

    public function delete(User $user, Lecture $lecture): bool
    {
        return $lecture->section->course->isOwnedBy($user);
    }
}
