<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read SubjectName $subject_name
 * @property-read ExamLevel $level
 */
final class ApplicantExamResult extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantExamResultFactory> */
    use HasFactory;

    use HasUuids;

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'subject_name' => SubjectName::class,
            'level' => ExamLevel::class,
        ];
    }

    /** @return BelongsTo<Applicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
