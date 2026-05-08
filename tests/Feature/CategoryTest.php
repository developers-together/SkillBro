<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('category hierarchy safety', function () {
    it('rejects making a category its own parent', function () {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", [
                'parent_id' => $category->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });

    it('rejects assigning a descendant as parent', function () {
        $admin = User::factory()->admin()->create();
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/categories/{$root->id}", [
                'parent_id' => $child->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    });
});
