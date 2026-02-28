<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read SubjectName $subject_name
 * @property-read RequirementType $requirement_type
 * @property-read ExamLevel $required_level
 */
final class ProgramSubject extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramSubjectFactory> */
    use HasFactory;

    use HasUuids;

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'subject_name' => SubjectName::class,
            'requirement_type' => RequirementType::class,
            'required_level' => ExamLevel::class,
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
