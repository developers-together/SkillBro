<?php

namespace App\Observers;

use App\Models\Course;
use Illuminate\Support\Str;

class CourseObserver
{
    /**
     * Auto-generate a unique slug from the course title before creation.
     */
    public function creating(Course $course): void
    {
        if (empty($course->slug)) {
            $course->slug = $this->generateUniqueSlug($course->title);
        }
    }

    /**
     * Regenerate slug if the title changes.
     */
    public function updating(Course $course): void
    {
        if ($course->isDirty('title') && ! $course->isDirty('slug')) {
            $course->slug = $this->generateUniqueSlug($course->title, $course->id);
        }
    }

    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (
            Course::withTrashed()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }
}
