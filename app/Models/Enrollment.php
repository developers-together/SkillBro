<?php

namespace App\Models;

use App\Observers\EnrollmentObserver;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(EnrollmentObserver::class)]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['user_id', 'course_id', 'enrolled_at', 'completed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<LectureProgress, $this> */
    public function lectureProgress(): HasMany
    {
        return $this->hasMany(LectureProgress::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
