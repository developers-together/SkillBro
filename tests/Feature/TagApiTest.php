<?php

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('tags api', function () {
    it('lists tags publicly', function () {
        Tag::factory()->count(2)->create();

        $this->getJson('/api/v1/tags')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('allows admin to create a tag', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/tags', ['name' => 'Laravel'])
            ->assertCreated()
            ->assertJsonPath('name', 'Laravel');

        $this->assertDatabaseHas('tags', ['name' => 'Laravel', 'slug' => 'laravel']);
    });

    it('forbids non-admin from creating a tag', function () {
        $student = User::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/tags', ['name' => 'Vue'])
            ->assertForbidden();
    });

    it('allows admin to delete a tag', function () {
        $admin = User::factory()->admin()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/tags/{$tag->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    });
});
