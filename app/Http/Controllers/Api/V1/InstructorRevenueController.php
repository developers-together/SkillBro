<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstructorRevenueController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, [UserRole::Instructor, UserRole::Admin], true),
            403
        );

        $baseQuery = Payment::query()
            ->whereHas('course', fn ($query) => $query->where('user_id', $user->id));

        $completed = (clone $baseQuery)->where('status', PaymentStatus::Completed);
        $refunded = (clone $baseQuery)->where('status', PaymentStatus::Refunded);
        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? 'strftime("%Y-%m", created_at)'
            : 'DATE_FORMAT(created_at, "%Y-%m")';

        $monthly = (clone $completed)
            ->selectRaw($monthExpression.' as month, SUM(amount) as total')
            ->groupBy(DB::raw($monthExpression))
            ->orderBy('month')
            ->get();

        return response()->json([
            'summary' => [
                'completed_total' => (string) $completed->sum('amount'),
                'refunded_total' => (string) $refunded->sum('amount'),
                'completed_count' => $completed->count(),
                'refunded_count' => $refunded->count(),
            ],
            'monthly' => $monthly->map(
                fn ($row) => [
                    'month' => $row->month,
                    'total' => (string) $row->total,
                ]
            )->values(),
        ]);
    }
}
