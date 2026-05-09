<?php

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('instructor revenue api', function () {
    it('forbids students from accessing instructor revenue endpoint', function () {
        $student = User::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/instructors/revenue/me')
            ->assertForbidden();
    });

    it('returns only current instructor revenue summary', function () {
        $instructor = User::factory()->instructor()->create();
        $otherInstructor = User::factory()->instructor()->create();
        $student = User::factory()->create();

        $courseA = Course::factory()->published()->paid()->create(['user_id' => $instructor->id, 'price' => 50]);
        $courseB = Course::factory()->published()->paid()->create(['user_id' => $instructor->id, 'price' => 20]);
        $otherCourse = Course::factory()->published()->paid()->create(['user_id' => $otherInstructor->id, 'price' => 90]);

        Payment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $courseA->id,
            'amount' => 50,
            'status' => PaymentStatus::Completed,
        ]);

        Payment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $courseB->id,
            'amount' => 20,
            'status' => PaymentStatus::Refunded,
        ]);

        Payment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $otherCourse->id,
            'amount' => 90,
            'status' => PaymentStatus::Completed,
        ]);

        $this->actingAs($instructor, 'sanctum')
            ->getJson('/api/v1/instructors/revenue/me')
            ->assertOk()
            ->assertJsonPath('summary.completed_total', '50')
            ->assertJsonPath('summary.refunded_total', '20')
            ->assertJsonPath('summary.completed_count', 1)
            ->assertJsonPath('summary.refunded_count', 1);
    });
});
