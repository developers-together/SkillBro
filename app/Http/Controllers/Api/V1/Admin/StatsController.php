<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\CourseStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stats = Cache::remember('admin:platform-stats', now()->addMinutes(5), function (): array {
            $totalUsers = User::query()->count();
            $totalInstructors = User::query()->where('role', 'instructor')->count();
            $totalStudents = User::query()->where('role', 'student')->count();
            $totalEnrollments = Enrollment::query()->count();

            $coursesByStatus = [
                'draft' => Course::query()->where('status', CourseStatus::Draft)->count(),
                'pending' => Course::query()->where('status', CourseStatus::Pending)->count(),
                'published' => Course::query()->where('status', CourseStatus::Published)->count(),
                'archived' => Course::query()->where('status', CourseStatus::Archived)->count(),
            ];

            return [
                'users' => [
                    'total' => $totalUsers,
                    'students' => $totalStudents,
                    'instructors' => $totalInstructors,
                    'admins' => User::query()->where('role', 'admin')->count(),
                ],
                'courses' => [
                    'total' => array_sum($coursesByStatus),
                    'by_status' => $coursesByStatus,
                ],
                'enrollments' => [
                    'total' => $totalEnrollments,
                    'completed' => Enrollment::query()->whereNotNull('completed_at')->count(),
                ],
                'revenue' => [
                    'completed_total' => (string) Payment::query()
                        ->where('status', PaymentStatus::Completed)
                        ->sum('amount'),
                    'refunded_total' => (string) Payment::query()
                        ->where('status', PaymentStatus::Refunded)
                        ->sum('amount'),
                ],
            ];
        });

        return response()->json($stats);
    }
}
