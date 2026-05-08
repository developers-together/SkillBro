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

    private string $courseTitle;

    private string $courseSlug;

    private int $courseId;

    public function __construct(public readonly Enrollment $enrollment)
    {
        // Capture at dispatch time, not during queued job execution
        $this->courseTitle = $enrollment->course->title;
        $this->courseSlug = $enrollment->course->slug;
        $this->courseId = $enrollment->course->id;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', url('/'));

        return (new MailMessage)
            ->subject("You're enrolled in {$this->courseTitle}!")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You've successfully enrolled in **{$this->courseTitle}**.")
            ->action('Start Learning', "{$frontendUrl}/courses/{$this->courseSlug}")
            ->line('Happy learning!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enrollment_confirmation',
            'course_id' => $this->courseId,
            'course_title' => $this->courseTitle,
        ];
    }
}
