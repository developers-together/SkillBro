<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('admin stats api', function () {
    it('allows admin to view aggregated platform stats', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create();
        User::factory()->instructor()->create();

        $published = Course::factory()->published()->create();
        Course::factory()->create(['status' => CourseStatus::Draft]);

        Enrollment::factory()->create(['course_id' => $published->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonStructure([
                'users' => ['total', 'students', 'instructors', 'admins'],
                'courses' => ['total', 'by_status' => ['draft', 'pending', 'published', 'archived']],
                'enrollments' => ['total', 'completed'],
            ]);
    });

    it('forbids non-admin from viewing stats', function () {
        $student = User::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/admin/stats')
            ->assertForbidden();
    });
});
