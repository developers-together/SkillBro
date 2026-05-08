<?php

use App\Models\Course;
use App\Models\Lecture;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('sections', function () {
    it('instructor can create a section', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/sections", ['title' => 'Introduction'])
            ->assertStatus(201)
            ->assertJsonFragment(['title' => 'Introduction']);
    });

    it('other instructor cannot add section to another\'s course', function () {
        $owner = User::factory()->instructor()->create();
        $other = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/sections", ['title' => 'Hack'])
            ->assertStatus(403);
    });

    it('instructor can reorder sections', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);
        $s1 = Section::factory()->create(['course_id' => $course->id, 'position' => 0]);
        $s2 = Section::factory()->create(['course_id' => $course->id, 'position' => 1]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/sections/reorder", [
                'sections' => [
                    ['id' => $s1->id, 'position' => 1],
                    ['id' => $s2->id, 'position' => 0],
                ],
            ])
            ->assertOk();

        expect($s1->fresh()->position)->toBe(1)
            ->and($s2->fresh()->position)->toBe(0);
    });
});

describe('lectures', function () {
    it('instructor can create a lecture', function () {
        $instructor = User::factory()->instructor()->create();
        $section = Section::factory()
            ->for(Course::factory()->create(['user_id' => $instructor->id]))
            ->create();

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/sections/{$section->id}/lectures", [
                'title' => 'Hello World',
                'type' => 'video',
            ])
            ->assertStatus(201);
    });

    it('instructor can reorder lectures', function () {
        $instructor = User::factory()->instructor()->create();
        $section = Section::factory()
            ->for(Course::factory()->create(['user_id' => $instructor->id]))
            ->create();

        $l1 = Lecture::factory()->create(['section_id' => $section->id, 'position' => 0]);
        $l2 = Lecture::factory()->create(['section_id' => $section->id, 'position' => 1]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/sections/{$section->id}/lectures/reorder", [
                'lectures' => [
                    ['id' => $l1->id, 'position' => 1],
                    ['id' => $l2->id, 'position' => 0],
                ],
            ])
            ->assertOk();

        expect($l1->fresh()->position)->toBe(1);
    });

    it('student cannot create a lecture', function () {
        $student = User::factory()->create();
        $section = Section::factory()
            ->for(Course::factory())
            ->create();

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/sections/{$section->id}/lectures", [
                'title' => 'Nope',
                'type' => 'text',
            ])
            ->assertStatus(403);
    });
});
