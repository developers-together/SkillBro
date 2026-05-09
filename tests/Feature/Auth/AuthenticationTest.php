<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('spa shell renders at root', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});

test('spa fallback renders for app routes', function () {
    $this->get('/app/account')
        ->assertOk()
        ->assertSee('id="skillbro-app"', false);
});
