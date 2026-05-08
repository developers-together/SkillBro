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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type->value,
            'is_preview' => $this->is_preview,
            'position' => $this->position,
            'video_duration' => $this->video_duration,
            // Only expose content/video_path if caller provides enrollment context or lecture is preview
            'content' => $this->when($this->is_preview || $this->resource->relationLoaded('progress'), $this->content),
            'video_path' => $this->when($this->is_preview, $this->video_path),
        ];
    }
}
