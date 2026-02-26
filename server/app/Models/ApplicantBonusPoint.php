<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LanguageCertificateType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApplicantBonusPoint extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantBonusPointFactory> */
    use HasFactory, HasUuids;

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'type' => LanguageCertificateType::class,
        ];
    }

    /** @return BelongsTo<Applicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
