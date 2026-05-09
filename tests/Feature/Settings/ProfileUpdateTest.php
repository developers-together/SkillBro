<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profile payload is returned via api', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

test('profile can be updated via api', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/user', [
            'name' => 'Updated Name',
            'bio' => 'Instructor and builder',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Updated Name');

    expect($user->fresh()->name)->toBe('Updated Name');
});
