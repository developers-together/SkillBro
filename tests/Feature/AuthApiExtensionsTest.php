<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

describe('auth api extensions', function () {
    it('sends forgot password token for existing email', function () {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    });

    it('resets password with valid token', function () {
        $user = User::factory()->create(['email' => 'john@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        expect(auth()->attempt(['email' => $user->email, 'password' => 'NewPassword123!']))->toBeTrue();
    });

    it('resends verification email for unverified user', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/email/resend')
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    it('verifies email using signed url endpoint', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(5),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->getJson($url)
            ->assertOk();

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });
});
