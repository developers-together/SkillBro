<?php

namespace App\Http\Resources;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAttempt
 */
class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'enrollment_id' => $this->enrollment_id,
            'score' => $this->score,
            'passed' => $this->passed,
            'answers' => $this->answers,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
