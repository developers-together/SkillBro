<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published()->paid(),
            'amount' => 19.99,
            'currency' => 'usd',
            'payment_intent_id' => 'pi_'.Str::random(16),
            'checkout_session_id' => 'cs_'.Str::random(16),
            'status' => PaymentStatus::Pending,
            'refund_requested_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Completed,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Refunded,
        ]);
    }
}
