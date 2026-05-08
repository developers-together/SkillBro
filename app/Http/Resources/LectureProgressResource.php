<?php

namespace App\Http\Resources;

use App\Models\LectureProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LectureProgress
 */
class LectureProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'lecture_id' => $this->lecture_id,
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
