<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_intent_id' => $this->payment_intent_id,
            'checkout_session_id' => $this->checkout_session_id,
            'status' => $this->status->value,
            'refund_requested_at' => $this->refund_requested_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
