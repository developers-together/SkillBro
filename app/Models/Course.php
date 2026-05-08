<?php

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Observers\CourseObserver;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(CourseObserver::class)]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'price',
        'status',
        'level',
        'language',
        'requirements',
        'what_you_learn',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => CourseStatus::class,
            'level' => CourseLevel::class,
            'requirements' => 'array',
            'what_you_learn' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    /** @return HasManyThrough<Lecture, Section, $this> */
    public function lectures(): HasManyThrough
    {
        return $this->hasManyThrough(Lecture::class, Section::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Scope: only published courses.
     *
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    /**
     * Scope: filter by category, price range, level, search term.
     *
     * @param  Builder<Course>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Course>
     */
    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when(isset($filters['category']), fn (Builder $q) => $q->where('category_id', $filters['category']))
            ->when(isset($filters['level']), fn (Builder $q) => $q->where('level', $filters['level']))
            ->when(isset($filters['price_max']), fn (Builder $q) => $q->where('price', '<=', $filters['price_max']))
            ->when(isset($filters['free']), fn (Builder $q) => $q->where('price', 0))
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(function (Builder $inner) use ($filters) {
                $inner->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            }));
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0.0;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
