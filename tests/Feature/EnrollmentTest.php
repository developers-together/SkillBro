<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

describe('enroll in free course', function () {
    it('student can enroll in a free published course', function () {
        Event::fake();

        $student = User::factory()->create();
        $course = Course::factory()->published()->free()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(201);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    });

    it('student cannot enroll twice', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->free()->create();

        Enrollment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id]);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(403);
    });

    it('student cannot enroll in a paid course via free endpoint', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->paid()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(422);
    });

    it('student cannot enroll in a draft course', function () {
        $student = User::factory()->create();
        $course = Course::factory()->free()->create(['status' => CourseStatus::Draft]);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(422);
    });

    it('unauthenticated user cannot enroll', function () {
        $course = Course::factory()->published()->free()->create();

        $this->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(401);
    });
});

describe('lecture progress', function () {
    it('student can mark a lecture as complete', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->free()->create();
        $section = Section::factory()->for($course)->create();
        $lecture = Lecture::factory()->for($section)->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/enrollments/{$enrollment->id}/lectures/{$lecture->id}/complete")
            ->assertOk()
            ->assertJsonStructure(['completed_at']);

        $this->assertDatabaseHas('lecture_progress', [
            'enrollment_id' => $enrollment->id,
            'lecture_id' => $lecture->id,
        ]);
    });

    it('other student cannot mark progress on another\'s enrollment', function () {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::factory()->published()->free()->create();
        $section = Section::factory()->for($course)->create();
        $lecture = Lecture::factory()->for($section)->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id]);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/enrollments/{$enrollment->id}/lectures/{$lecture->id}/complete")
            ->assertStatus(403);
    });

    it('marks course as completed when all lectures done', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->free()->create();
        $section = Section::factory()->for($course)->create();
        $lecture = Lecture::factory()->for($section)->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'course_id' => $course->id]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/enrollments/{$enrollment->id}/lectures/{$lecture->id}/complete")
            ->assertOk();

        expect($enrollment->fresh()->completed_at)->not->toBeNull();
    });
});
