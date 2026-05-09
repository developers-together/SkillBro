<?php

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('payments api', function () {
    it('creates checkout session for paid published course', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->paid()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/payments/checkout', [
                'course_id' => $course->id,
            ])
            ->assertCreated()
            ->assertJsonStructure(['url', 'session_id']);

        $this->assertDatabaseHas('payments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    });

    it('processes webhook completion and enrolls student', function () {
        $student = User::factory()->create();
        $course = Course::factory()->published()->paid()->create();

        $payment = Payment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->postJson('/api/v1/payments/webhook', [
            'type' => 'checkout.completed',
            'session_id' => $payment->checkout_session_id,
            'status' => 'paid',
        ])->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    });

    it('returns authenticated student payment history', function () {
        $student = User::factory()->create();

        Payment::factory()->count(2)->create([
            'user_id' => $student->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('allows student to request refund for completed payment', function () {
        $student = User::factory()->create();

        $payment = Payment::factory()->completed()->create([
            'user_id' => $student->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/refund")
            ->assertOk();

        expect($payment->fresh()->refund_requested_at)->not->toBeNull();
    });

    it('prevents student from refunding another user payment', function () {
        $student = User::factory()->create();
        $other = User::factory()->create();

        $payment = Payment::factory()->completed()->create([
            'user_id' => $other->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/refund")
            ->assertForbidden();
    });

    it('allows admin to approve refund request', function () {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->completed()->create([
            'refund_requested_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/payments/{$payment->id}/refund", [
                'approve' => true,
            ])
            ->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    });

    it('forbids non-admin from admin payments endpoint', function () {
        $student = User::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/admin/payments')
            ->assertForbidden();
    });
});
