<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(LazilyRefreshDatabase::class);

describe('register', function () {
    it('registers a new user and returns a token', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('token')
                ->has('user.id')
                ->where('user.email', 'john@example.com')
                ->where('user.role', 'student')
                ->missing('user.password')
            );
    });

    it('fails validation with duplicate email', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    });

    it('fails validation when passwords do not match', function () {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    });
});

describe('login', function () {
    it('logs in with valid credentials and returns a token', function () {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    });

    it('rejects invalid credentials', function () {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    });
});

describe('logout', function () {
    it('revokes the current token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // Token is revoked — DB record deleted
        expect($user->tokens()->count())->toBe(0);
    });

    it('does not fail when no current access token is attached', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    });
});
