<?php

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('browse courses', function () {
    it('lists only published courses publicly', function () {
        Course::factory()->published()->free()->create();
        Course::factory()->create(['status' => CourseStatus::Draft]);

        $this->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by category', function () {
        $category = Category::factory()->create();
        Course::factory()->published()->free()->create(['category_id' => $category->id]);
        Course::factory()->published()->free()->create();

        $this->getJson("/api/v1/courses?category={$category->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('create course', function () {
    it('instructor can create a course', function () {
        $instructor = User::factory()->instructor()->create();

        $this->actingAs($instructor, 'sanctum')
            ->postJson('/api/v1/courses', [
                'title' => 'Laravel for Beginners',
                'description' => 'A comprehensive course.',
                'price' => 0,
            ])
            ->assertStatus(201)
            ->assertJson(['title' => 'Laravel for Beginners', 'status' => 'draft']);
    });

    it('student cannot create a course', function () {
        $student = User::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/courses', [
                'title' => 'Hack the planet',
                'description' => 'nope',
            ])
            ->assertStatus(403);
    });

    it('auto-generates slug from title', function () {
        $instructor = User::factory()->instructor()->create();

        $this->actingAs($instructor, 'sanctum')
            ->postJson('/api/v1/courses', [
                'title' => 'My Awesome Course',
                'description' => 'desc',
            ])
            ->assertStatus(201)
            ->assertJson(['slug' => 'my-awesome-course']);
    });
});

describe('update course', function () {
    it('instructor can update own course', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);

        $this->actingAs($instructor, 'sanctum')
            ->putJson("/api/v1/courses/{$course->id}", ['title' => 'Updated Title'])
            ->assertOk()
            ->assertJson(['title' => 'Updated Title']);
    });

    it('instructor cannot update another instructor\'s course', function () {
        $instructor = User::factory()->instructor()->create();
        $other = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $other->id]);

        $this->actingAs($instructor, 'sanctum')
            ->putJson("/api/v1/courses/{$course->id}", ['title' => 'Stolen Title'])
            ->assertStatus(403);
    });
});

describe('course status transitions', function () {
    it('instructor can submit draft course for review', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id, 'status' => CourseStatus::Draft]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/submit")
            ->assertOk()
            ->assertJson(['status' => 'pending']);
    });

    it('admin can publish a pending course', function () {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['status' => CourseStatus::Pending]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/publish")
            ->assertOk()
            ->assertJson(['status' => 'published']);
    });

    it('instructor cannot publish a course', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id, 'status' => CourseStatus::Pending]);

        $this->actingAs($instructor, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/publish")
            ->assertStatus(403);
    });
});

describe('delete course', function () {
    it('soft deletes the course', function () {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);

        $this->actingAs($instructor, 'sanctum')
            ->deleteJson("/api/v1/courses/{$course->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    });
});
