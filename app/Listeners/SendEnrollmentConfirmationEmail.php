<?php

namespace App\Listeners;

use App\Events\StudentEnrolled;
use App\Notifications\EnrollmentConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        $student = $event->enrollment->student;

        // Guard: user may have been deleted between dispatch and processing
        if (! $student) {
            return;
        }

        $student->notify(new EnrollmentConfirmation($event->enrollment));
    }

    public function failed(StudentEnrolled $event, Throwable $exception): void
    {
        Log::error('EnrollmentConfirmation notification failed after all retries', [
            'enrollment_id' => $event->enrollment->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
