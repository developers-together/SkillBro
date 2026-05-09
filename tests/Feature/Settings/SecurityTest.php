<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unverified user cannot enroll because verified middleware is enforced', function () {
    $user = User::factory()->unverified()->create();
    $course = Course::factory()->create([
        'status' => CourseStatus::Published,
        'price' => 0,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
        ->assertStatus(403);
});

test('unverified user cannot create checkout because verified middleware is enforced', function () {
    $user = User::factory()->unverified()->create();
    $course = Course::factory()->published()->paid()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/checkout', ['course_id' => $course->id])
        ->assertStatus(403);
});
