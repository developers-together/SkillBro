<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('forgot password works via api', function () {
    $user = User::factory()->create(['email' => 'pw@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertOk();

    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
});

test('password can be reset via api token', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertOk();

    expect(auth()->attempt(['email' => $user->email, 'password' => 'NewPassword123!']))->toBeTrue();
});
