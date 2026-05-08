<?php

namespace App\Observers;

use App\Events\StudentEnrolled;
use App\Models\Enrollment;

class EnrollmentObserver
{
    /**
     * Dispatch StudentEnrolled event after a new enrollment is persisted.
     */
    public function created(Enrollment $enrollment): void
    {
        StudentEnrolled::dispatch($enrollment);
    }
}
