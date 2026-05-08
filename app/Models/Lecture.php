<?php

namespace App\Models;

use App\Enums\LectureType;
use Database\Factories\LectureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lecture extends Model
{
    /** @use HasFactory<LectureFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'section_id',
        'title',
        'type',
        'content',
        'video_path',
        'video_duration',
        'is_preview',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LectureType::class,
            'is_preview' => 'boolean',
            'video_duration' => 'integer',
        ];
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return HasMany<LectureProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(LectureProgress::class);
    }

    /** @return HasOne<Quiz, $this> */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }
}
