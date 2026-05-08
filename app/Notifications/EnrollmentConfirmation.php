<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Enrollment $enrollment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're enrolled in {$this->enrollment->course->title}!")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You've successfully enrolled in **{$this->enrollment->course->title}**.")
            ->action('Start Learning', url('/courses/'.$this->enrollment->course->slug))
            ->line('Happy learning!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enrollment_confirmation',
            'course_id' => $this->enrollment->course_id,
            'course_title' => $this->enrollment->course->title,
        ];
    }
}
