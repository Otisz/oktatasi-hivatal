<?php

declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Exceptions\UnknownProgramException;
use App\Models\Program;
use App\Models\ProgramSubject;
use App\Services\DatabaseProgramRequirements;
use Illuminate\Database\Eloquent\Collection;

function makeProgramSubject(SubjectName $name, RequirementType $type, ?ExamLevel $level = null): ProgramSubject
{
    $subject = new ProgramSubject;
    $subject->setAttribute('subject_name', $name);
    $subject->setAttribute('requirement_type', $type);
    $subject->setAttribute('required_level', $level);

    return $subject;
}

function makeMandatorySubject(SubjectName $name, ?ExamLevel $level = null): ProgramSubject
{
    return makeProgramSubject($name, RequirementType::Mandatory, $level);
}

function makeElectiveSubject(SubjectName $name): ProgramSubject
{
    return makeProgramSubject($name, RequirementType::Elective);
}

it('returns the mandatory subject name', function (): void {
    $program = new Program;
    $program->setRelation('subjects', Collection::make([
        makeMandatorySubject(SubjectName::Mathematics),
        makeElectiveSubject(SubjectName::Physics),
    ]));

    $requirements = new DatabaseProgramRequirements($program);

    expect($requirements->getMandatorySubject())->toBe(SubjectName::Mathematics);
});

it('returns elective subject names as array', function (): void {
    $program = new Program;
    $program->setRelation('subjects', Collection::make([
        makeMandatorySubject(SubjectName::Mathematics),
        makeElectiveSubject(SubjectName::Physics),
        makeElectiveSubject(SubjectName::Biology),
    ]));

    $requirements = new DatabaseProgramRequirements($program);

    expect($requirements->getElectiveSubjects())->toBe([SubjectName::Physics, SubjectName::Biology]);
});

it('returns null when mandatory subject has no required level', function (): void {
    $program = new Program;
    $program->setRelation('subjects', Collection::make([
        makeMandatorySubject(SubjectName::Mathematics, null),
    ]));

    $requirements = new DatabaseProgramRequirements($program);

    expect($requirements->getMandatorySubjectLevel())->toBeNull();
});

it('returns the required level when mandatory subject specifies one', function (): void {
    $program = new Program;
    $program->setRelation('subjects', Collection::make([
        makeMandatorySubject(SubjectName::EnglishLanguage, ExamLevel::Advanced),
    ]));

    $requirements = new DatabaseProgramRequirements($program);

    expect($requirements->getMandatorySubjectLevel())->toBe(ExamLevel::Advanced);
});

it('throws UnknownProgramException when no mandatory subject exists', function (): void {
    $program = new Program;
    $program->setRelation('subjects', Collection::make([
        makeElectiveSubject(SubjectName::Physics),
    ]));

    $requirements = new DatabaseProgramRequirements($program);

    expect(fn () => $requirements->getMandatorySubject())
        ->toThrow(UnknownProgramException::class);
});
