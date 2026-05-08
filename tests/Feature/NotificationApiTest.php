<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Notification;

uses(LazilyRefreshDatabase::class);

describe('notifications api', function () {
    it('lists authenticated user notifications', function () {
        $user = User::factory()->create();

        $user->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['title' => 'Course published'];
            }
        });

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.title', 'Course published');
    });

    it('marks all notifications as read', function () {
        $user = User::factory()->create();

        $user->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['title' => 'Payment complete'];
            }
        });

        expect($user->unreadNotifications()->count())->toBe(1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        expect($user->fresh()->unreadNotifications()->count())->toBe(0);
    });

    it('marks single notification as read', function () {
        $user = User::factory()->create();

        $user->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['title' => 'New review'];
            }
        });

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/notifications/{$notification->id}")
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->where('id', $notification->id)
                ->whereNot('read_at', null)
            );
    });
});
