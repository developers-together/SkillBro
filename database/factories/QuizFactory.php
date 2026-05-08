<?php

namespace Database\Factories;

use App\Models\Lecture;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lecture_id' => Lecture::factory(),
            'pass_percentage' => 70,
        ];
    }
}
