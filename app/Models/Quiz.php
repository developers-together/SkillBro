<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['lecture_id', 'pass_percentage'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pass_percentage' => 'integer',
        ];
    }

    /** @return BelongsTo<Lecture, $this> */
    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class);
    }

    /** @return HasMany<QuizQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
