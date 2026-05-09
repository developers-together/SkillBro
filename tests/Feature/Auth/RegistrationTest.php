<?php

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users can register through api with student role by default', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'API User',
        'email' => 'api-user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertCreated()
        ->assertJsonPath('user.role', UserRole::Student->value);
});
