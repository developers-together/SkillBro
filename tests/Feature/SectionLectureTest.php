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

    it('returns not found when section does not belong to course in update route', function () {
        $instructor = User::factory()->instructor()->create();
        $courseA = Course::factory()->create(['user_id' => $instructor->id]);
        $courseB = Course::factory()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $courseA->id]);

        $this->actingAs($instructor, 'sanctum')
            ->putJson("/api/v1/courses/{$courseB->id}/sections/{$section->id}", [
                'title' => 'Updated Through Wrong Course',
            ])
            ->assertNotFound();
    });

    it('returns not found when section does not belong to course in delete route', function () {
        $instructor = User::factory()->instructor()->create();
        $courseA = Course::factory()->create(['user_id' => $instructor->id]);
        $courseB = Course::factory()->create(['user_id' => $instructor->id]);
        $section = Section::factory()->create(['course_id' => $courseA->id]);

        $this->actingAs($instructor, 'sanctum')
            ->deleteJson("/api/v1/courses/{$courseB->id}/sections/{$section->id}")
            ->assertNotFound();
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

    it('returns not found when lecture does not belong to section in update route', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);
        $sectionA = Section::factory()->create(['course_id' => $course->id]);
        $sectionB = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create(['section_id' => $sectionA->id]);

        $this->actingAs($instructor, 'sanctum')
            ->putJson("/api/v1/sections/{$sectionB->id}/lectures/{$lecture->id}", [
                'title' => 'Wrong Parent',
            ])
            ->assertNotFound();
    });

    it('returns not found when lecture does not belong to section in delete route', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);
        $sectionA = Section::factory()->create(['course_id' => $course->id]);
        $sectionB = Section::factory()->create(['course_id' => $course->id]);
        $lecture = Lecture::factory()->create(['section_id' => $sectionA->id]);

        $this->actingAs($instructor, 'sanctum')
            ->deleteJson("/api/v1/sections/{$sectionB->id}/lectures/{$lecture->id}")
            ->assertNotFound();
    });
});
