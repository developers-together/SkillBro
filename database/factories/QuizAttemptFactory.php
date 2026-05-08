<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'quiz_id' => Quiz::factory(),
            'score' => 0,
            'passed' => false,
            'answers' => [],
            'created_at' => now(),
        ];
    }
}
