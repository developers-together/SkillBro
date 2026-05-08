<?php

namespace Database\Factories;

use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAnswer>
 */
class QuizAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => QuizQuestion::factory(),
            'answer' => fake()->words(3, true),
            'is_correct' => false,
        ];
    }
}
