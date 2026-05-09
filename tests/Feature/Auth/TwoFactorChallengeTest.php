<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy two factor path resolves to spa shell', function () {
    $this->get('/two-factor-challenge')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});

test('token auth remains usable without web challenge flow', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});
