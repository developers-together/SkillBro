<?php

namespace Database\Factories;

use App\Enums\LectureType;
use App\Models\Lecture;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecture>
 */
class LectureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => fake()->sentence(4),
            'type' => LectureType::Video,
            'content' => null,
            'video_path' => null,
            'video_duration' => fake()->numberBetween(120, 3600),
            'is_preview' => false,
            'position' => 0,
        ];
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LectureType::Text,
            'content' => fake()->paragraphs(2, true),
            'video_path' => null,
            'video_duration' => null,
        ]);
    }

    public function preview(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preview' => true,
        ]);
    }
}
