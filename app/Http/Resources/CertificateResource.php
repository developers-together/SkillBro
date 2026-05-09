<?php

namespace App\Http\Resources;

use App\Models\CourseCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin CourseCertificate
 */
class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'certificate_number' => $this->certificate_number,
            'issued_at' => $this->issued_at?->toISOString(),
            'file_path' => $this->file_path,
            'download_url' => $this->file_path
                ? Storage::disk('public')->url($this->file_path)
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
