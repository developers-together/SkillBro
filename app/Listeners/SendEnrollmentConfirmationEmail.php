<?php

namespace App\Listeners;

use App\Events\StudentEnrolled;
use App\Notifications\EnrollmentConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEnrollmentConfirmationEmail implements ShouldQueue
{
    /**
     * Number of times the listener may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff strategy in seconds.
     *
     * @var array<int, int>
     */
    public array $backoff = [1, 5, 10];

    public function handle(StudentEnrolled $event): void
    {
        $event->enrollment->student->notify(
            new EnrollmentConfirmation($event->enrollment)
        );
    }
}
