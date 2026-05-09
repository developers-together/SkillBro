<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'course_id',
        'amount',
        'currency',
        'payment_intent_id',
        'checkout_session_id',
        'idempotency_key',
        'status',
        'refund_requested_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'refund_requested_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function canTransitionTo(PaymentStatus $next): bool
    {
        return match ($this->status) {
            PaymentStatus::Pending => in_array($next, [PaymentStatus::Completed, PaymentStatus::Failed], true),
            PaymentStatus::Completed => $next === PaymentStatus::Refunded,
            PaymentStatus::Refunded, PaymentStatus::Failed => false,
        };
    }
}
