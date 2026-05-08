<?php

namespace App\Http\Resources;

use App\Models\QuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAnswer
 */
class QuizAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'answer' => $this->answer,
            'is_correct' => $this->is_correct,
        ];
    }
}
