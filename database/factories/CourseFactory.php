<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->instructor(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'slug' => '',
            'description' => fake()->paragraphs(3, true),
            'thumbnail' => null,
            'price' => fake()->randomElement([0, 9.99, 19.99, 49.99]),
            'status' => CourseStatus::Draft,
            'level' => fake()->randomElement(CourseLevel::cases()),
            'language' => 'en',
            'requirements' => null,
            'what_you_learn' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 19.99,
        ]);
    }
}
