<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->latest('id')
            ->paginate(25);

        return PaymentResource::collection($payments);
    }

    public function decideRefund(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
        ]);

        abort_if($payment->refund_requested_at === null, 422, 'No refund requested for this payment.');

        $payment->update([
            'status' => $data['approve'] ? PaymentStatus::Refunded : PaymentStatus::Completed,
            'refund_requested_at' => null,
        ]);

        return response()->json(new PaymentResource($payment->fresh()));
    }
}
