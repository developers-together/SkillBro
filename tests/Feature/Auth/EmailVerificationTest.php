<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('email can be verified via api signed url', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'api.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->getJson($verificationUrl)->assertOk();

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
