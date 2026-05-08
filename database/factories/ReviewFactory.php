<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published()->free(),
            'rating' => fake()->numberBetween(1, 5),
            'body' => fake()->sentence(),
            'instructor_reply' => null,
        ];
    }
}
