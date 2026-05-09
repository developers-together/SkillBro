<?php

namespace App\Models;

use Database\Factories\CourseCertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCertificate extends Model
{
    /** @use HasFactory<CourseCertificateFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'enrollment_id',
        'certificate_number',
        'file_path',
        'issued_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
