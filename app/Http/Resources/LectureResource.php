<?php

namespace App\Http\Resources;

use App\Models\Lecture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lecture
 */
class LectureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // CourseController::show() sets 'is_enrolled' = true on request attributes for enrolled users
        $isEnrolled = $request->attributes->get('is_enrolled', false);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type->value,
            'is_preview' => $this->is_preview,
            'position' => $this->position,
            'video_duration' => $this->video_duration,
            'content' => $this->when($this->is_preview || $isEnrolled, $this->content),
            'video_path' => $this->when($this->is_preview || $isEnrolled, $this->video_path),
        ];
    }
}
