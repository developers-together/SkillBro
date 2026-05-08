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
        $section = $lecture->relationLoaded('section') ? $lecture->section : $lecture->section()->first();
        $course = $section?->relationLoaded('course') ? $section->course : $section?->course()->first();

        return $course?->isOwnedBy($user) ?? false;
    }

    public function update(User $user, Lecture $lecture): bool
    {
        $section = $lecture->relationLoaded('section') ? $lecture->section : $lecture->section()->first();
        $course = $section?->relationLoaded('course') ? $section->course : $section?->course()->first();

        return $course?->isOwnedBy($user) ?? false;
    }

    public function delete(User $user, Lecture $lecture): bool
    {
        $section = $lecture->relationLoaded('section') ? $lecture->section : $lecture->section()->first();
        $course = $section?->relationLoaded('course') ? $section->course : $section?->course()->first();

        return $course?->isOwnedBy($user) ?? false;
    }
}
