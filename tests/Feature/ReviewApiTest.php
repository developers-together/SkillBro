<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('reviews api', function () {
    it('lists course reviews publicly', function () {
        $course = Course::factory()->published()->create();
        Review::factory()->count(2)->create(['course_id' => $course->id]);

        $this->getJson("/api/v1/courses/{$course->id}/reviews")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('allows enrolled student to create a review', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create();

        Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/reviews", [
                'rating' => 5,
                'body' => 'Great course',
            ])
            ->assertCreated()
            ->assertJsonPath('rating', 5);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
        ]);
    });

    it('forbids non-enrolled student from creating a review', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/reviews", [
                'rating' => 4,
                'body' => 'Nice',
            ])
            ->assertForbidden();
    });

    it('prevents duplicate review by same student for same course', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create();

        Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        Review::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/reviews", [
                'rating' => 3,
                'body' => 'Second review',
            ])
            ->assertStatus(422);
    });

    it('allows student to update own review', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create();

        $review = Review::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 2,
        ]);

        $this->actingAs($student, 'sanctum')
            ->putJson("/api/v1/courses/{$course->id}/reviews/{$review->id}", [
                'rating' => 4,
                'body' => 'Improved after updates',
            ])
            ->assertOk()
            ->assertJsonPath('rating', 4);
    });

    it('forbids other student from updating review', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::factory()->published()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/v1/courses/{$course->id}/reviews/{$review->id}", [
                'rating' => 1,
            ])
            ->assertForbidden();
    });

    it('allows course owner instructor to reply', function () {
        $instructor = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->published()->create(['user_id' => $instructor->id]);

        $review = Review::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/reviews/{$review->id}/reply", [
                'instructor_reply' => 'Thanks for the feedback!',
            ])
            ->assertOk()
            ->assertJsonPath('instructor_reply', 'Thanks for the feedback!');
    });

    it('forbids non-owner instructor from replying', function () {
        $owner = User::factory()->instructor()->create();
        $otherInstructor = User::factory()->instructor()->create();
        $student = User::factory()->create();

        $course = Course::factory()->published()->create(['user_id' => $owner->id]);
        $review = Review::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($otherInstructor, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/reviews/{$review->id}/reply", [
                'instructor_reply' => 'Unauthorized reply',
            ])
            ->assertForbidden();
    });

    it('allows admin to delete any review', function () {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->published()->create();
        $review = Review::factory()->create(['course_id' => $course->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/courses/{$course->id}/reviews/{$review->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    });
});
