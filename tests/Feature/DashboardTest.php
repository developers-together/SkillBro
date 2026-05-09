<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can access the spa shell', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});

test('authenticated users can access app routes through spa shell', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $this->get('/app/learn')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});
