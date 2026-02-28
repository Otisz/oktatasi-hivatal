<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Applicant extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;

    use HasUuids;

    public const string CASE_1_UUID = '0195a1b2-0000-7000-8000-000000000001';

    public const string CASE_2_UUID = '0195a1b2-0000-7000-8000-000000000002';

    public const string CASE_3_UUID = '0195a1b2-0000-7000-8000-000000000003';

    public const string CASE_4_UUID = '0195a1b2-0000-7000-8000-000000000004';

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<ApplicantExamResult, $this> */
    public function examResults(): HasMany
    {
        return $this->hasMany(ApplicantExamResult::class);
    }

    /** @return HasMany<ApplicantBonusPoint, $this> */
    public function bonusPoints(): HasMany
    {
        return $this->hasMany(ApplicantBonusPoint::class);
    }
}
