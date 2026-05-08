<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    public function create(User $user, Section $section): bool
    {
        $course = $section->relationLoaded('course') ? $section->course : $section->course()->first();

        return $course?->isOwnedBy($user) ?? false;
    }

    public function update(User $user, Section $section): bool
    {
        $course = $section->relationLoaded('course') ? $section->course : $section->course()->first();

        return $course?->isOwnedBy($user) ?? false;
    }

    public function delete(User $user, Section $section): bool
    {
        $course = $section->relationLoaded('course') ? $section->course : $section->course()->first();

        return $course?->isOwnedBy($user) ?? false;
    }
}
