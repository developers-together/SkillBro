<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'success_url' => ['sometimes', 'nullable', 'url'],
            'cancel_url' => ['sometimes', 'nullable', 'url'],
            'idempotency_key' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        /** @var Course $course */
        $course = Course::query()->findOrFail($data['course_id']);

        abort_if($course->status !== CourseStatus::Published, 422, 'Course is not available for purchase.');
        abort_if($course->isFree(), 422, 'Course is free. Use enrollment endpoint.');

        $user = $request->user();
        $alreadyEnrolled = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        abort_if($alreadyEnrolled, 422, 'You are already enrolled in this course.');

        $idempotencyKey = $request->header('Idempotency-Key')
            ?? $data['idempotency_key']
            ?? null;

        if ($idempotencyKey) {
            $existing = Payment::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                $successUrl = $data['success_url'] ?? config('app.url').'?payment=success';

                return response()->json([
                    'url' => $successUrl.'&session_id='.$existing->checkout_session_id,
                    'session_id' => $existing->checkout_session_id,
                    'idempotent_replay' => true,
                ]);
            }
        }

        $sessionId = 'cs_'.Str::random(24);
        $intentId = 'pi_'.Str::random(24);

        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'currency' => 'usd',
            'checkout_session_id' => $sessionId,
            'payment_intent_id' => $intentId,
            'idempotency_key' => $idempotencyKey,
            'status' => PaymentStatus::Pending,
        ]);

        $successUrl = $data['success_url'] ?? config('app.url').'?payment=success';

        return response()->json([
            'url' => $successUrl.'&session_id='.$sessionId,
            'session_id' => $payment->checkout_session_id,
        ], 201);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'event_id' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', 'string'],
            'session_id' => ['sometimes', 'nullable', 'string'],
            'payment_intent_id' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string'],
            'data' => ['sometimes', 'array'],
            'data.object' => ['sometimes', 'array'],
            'data.object.id' => ['sometimes', 'nullable', 'string'],
            'data.object.payment_intent' => ['sometimes', 'nullable', 'string'],
            'data.object.payment_status' => ['sometimes', 'nullable', 'string'],
        ]);

        $eventId = $payload['event_id']
            ?? sha1(json_encode($payload));

        $alreadyProcessed = PaymentWebhookEvent::query()
            ->where('event_id', $eventId)
            ->exists();

        if ($alreadyProcessed) {
            return response()->json(['message' => 'Webhook already processed.']);
        }

        $sessionId = $payload['session_id']
            ?? data_get($payload, 'data.object.id');
        $intentId = $payload['payment_intent_id']
            ?? data_get($payload, 'data.object.payment_intent');
        $status = $payload['status']
            ?? data_get($payload, 'data.object.payment_status');
        $eventType = $payload['type'] ?? null;

        $payment = Payment::query()
            ->when($sessionId, fn ($query) => $query->orWhere('checkout_session_id', $sessionId))
            ->when($intentId, fn ($query) => $query->orWhere('payment_intent_id', $intentId))
            ->first();

        $event = PaymentWebhookEvent::query()->create([
            'event_id' => $eventId,
            'provider' => 'internal',
            'payment_id' => $payment?->id,
            'status' => 'received',
            'payload' => $payload,
        ]);

        if (! $payment) {
            $event->update([
                'status' => 'payment_not_found',
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $isCompleted = in_array($status, ['paid', 'completed', 'succeeded'], true)
            || in_array($eventType, ['checkout.completed', 'payment.completed'], true);

        if (! $isCompleted) {
            if ($payment->status === PaymentStatus::Pending && $payment->canTransitionTo(PaymentStatus::Failed)) {
                $payment->update(['status' => PaymentStatus::Failed]);
            }

            $event->update([
                'status' => 'failed',
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Webhook processed as failed.']);
        }

        DB::transaction(function () use ($payment): void {
            $payment->refresh();

            if ($payment->status === PaymentStatus::Completed || $payment->status === PaymentStatus::Refunded) {
                return;
            }

            if (! $payment->canTransitionTo(PaymentStatus::Completed)) {
                return;
            }

            $payment->update(['status' => PaymentStatus::Completed]);

            Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'course_id' => $payment->course_id,
                ],
                [
                    'enrolled_at' => now(),
                ],
            );
        });

        $event->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        return response()->json(['message' => 'Webhook processed.']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(15);

        return PaymentResource::collection($payments);
    }

    public function requestRefund(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_if($payment->status !== PaymentStatus::Completed, 422, 'Only completed payments can be refunded.');
        abort_if($payment->refund_requested_at !== null, 422, 'Refund already requested.');

        $payment->update([
            'refund_requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Refund request submitted.',
        ]);
    }
}
