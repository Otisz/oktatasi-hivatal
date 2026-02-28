<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRequirementsInterface;
use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Exceptions\UnknownProgramException;
use App\Models\Program;
use App\Models\ProgramSubject;

final readonly class DatabaseProgramRequirements implements ProgramRequirementsInterface
{
    public function __construct(private Program $program)
    {
        //
    }

    public function getMandatorySubject(): SubjectName
    {
        $mandatory = $this->program->subjects->first(
            fn (ProgramSubject $subject): bool => RequirementType::Mandatory === $subject->requirement_type
        );

        throw_if(null === $mandatory, UnknownProgramException::class);

        return $mandatory->subject_name;
    }

    /** @return array<int, SubjectName> */
    public function getElectiveSubjects(): array
    {
        return $this->program->subjects
            ->filter(fn (ProgramSubject $subject): bool => RequirementType::Elective === $subject->requirement_type)
            ->values()
            ->map(fn (ProgramSubject $subject): SubjectName => $subject->subject_name)
            ->all();
    }

    public function getMandatorySubjectLevel(): ?ExamLevel
    {
        $mandatory = $this->program->subjects->first(
            fn (ProgramSubject $subject): bool => RequirementType::Mandatory === $subject->requirement_type
        );

        return $mandatory?->required_level;
    }
}
