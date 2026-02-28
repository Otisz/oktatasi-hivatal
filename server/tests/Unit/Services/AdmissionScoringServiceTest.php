<?php

declare(strict_types=1);

use App\Contracts\BasePointCalculatorInterface;
use App\Contracts\BonusPointCalculatorInterface;
use App\Contracts\ProgramRegistryInterface;
use App\Contracts\ProgramRequirementsInterface;
use App\Enums\ExamLevel;
use App\Enums\LanguageCertificateType;
use App\Enums\SubjectName;
use App\Exceptions\FailedExamException;
use App\Exceptions\MissingElectiveSubjectException;
use App\Exceptions\MissingGlobalMandatorySubjectException;
use App\Exceptions\MissingProgramMandatorySubjectException;
use App\Exceptions\ProgramMandatorySubjectLevelException;
use App\Models\Applicant;
use App\Models\ApplicantBonusPoint;
use App\Models\ApplicantExamResult;
use App\Services\AdmissionScoringService;
use Illuminate\Database\Eloquent\Collection;

function makeExamResultRow(SubjectName $subject, ExamLevel $level, int $percentage): ApplicantExamResult
{
    $row = new ApplicantExamResult;
    $row->setAttribute('subject_name', $subject);
    $row->setAttribute('level', $level);
    $row->setAttribute('percentage', $percentage);

    return $row;
}

function makeBonusPointRow(LanguageCertificateType $type, string $language): ApplicantBonusPoint
{
    $row = new ApplicantBonusPoint;
    $row->setAttribute('type', $type);
    $row->setAttribute('language', $language);

    return $row;
}

function makeApplicantWithExams(array $examRows, array $bonusRows = []): Applicant
{
    $applicant = new Applicant;
    $applicant->setRelation('examResults', Collection::make($examRows));
    $applicant->setRelation('bonusPoints', Collection::make($bonusRows));

    return $applicant;
}

it('throws FailedExamException when an exam has percentage below 20', function (): void {
    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldNotReceive('findByApplicant');

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 15),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(FailedExamException::class);
});

it('throws MissingGlobalMandatorySubjectException when magyar is absent', function (): void {
    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldNotReceive('findByApplicant');

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::History, ExamLevel::Intermediate, 50),
        makeExamResultRow(SubjectName::Mathematics, ExamLevel::Intermediate, 60),
        makeExamResultRow(SubjectName::Informatics, ExamLevel::Intermediate, 80),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(MissingGlobalMandatorySubjectException::class);
});

it('throws MissingGlobalMandatorySubjectException when tortenelem is absent', function (): void {
    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldNotReceive('findByApplicant');

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
        makeExamResultRow(SubjectName::Mathematics, ExamLevel::Intermediate, 60),
        makeExamResultRow(SubjectName::Informatics, ExamLevel::Intermediate, 80),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(MissingGlobalMandatorySubjectException::class);
});

it('throws MissingGlobalMandatorySubjectException when matematika is absent', function (): void {
    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldNotReceive('findByApplicant');

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
        makeExamResultRow(SubjectName::History, ExamLevel::Intermediate, 60),
        makeExamResultRow(SubjectName::Informatics, ExamLevel::Intermediate, 80),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(MissingGlobalMandatorySubjectException::class);
});

it('throws MissingProgramMandatorySubjectException when programme mandatory subject is missing', function (): void {
    $requirements = Mockery::mock(ProgramRequirementsInterface::class);
    $requirements->shouldReceive('getMandatorySubject')->once()->andReturn(SubjectName::EnglishLanguage);

    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldReceive('findByApplicant')->once()->andReturn($requirements);

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
        makeExamResultRow(SubjectName::History, ExamLevel::Intermediate, 60),
        makeExamResultRow(SubjectName::Mathematics, ExamLevel::Intermediate, 70),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(MissingProgramMandatorySubjectException::class);
});

it('throws ProgramMandatorySubjectLevelException when mandatory subject is at wrong level', function (): void {
    $requirements = Mockery::mock(ProgramRequirementsInterface::class);
    $requirements->shouldReceive('getMandatorySubject')->once()->andReturn(SubjectName::EnglishLanguage);
    $requirements->shouldReceive('getMandatorySubjectLevel')->once()->andReturn(ExamLevel::Advanced);

    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldReceive('findByApplicant')->once()->andReturn($requirements);

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
        makeExamResultRow(SubjectName::History, ExamLevel::Intermediate, 60),
        makeExamResultRow(SubjectName::Mathematics, ExamLevel::Intermediate, 70),
        makeExamResultRow(SubjectName::EnglishLanguage, ExamLevel::Intermediate, 80),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(ProgramMandatorySubjectLevelException::class);
});

it('throws MissingElectiveSubjectException when no elective subject matches', function (): void {
    $requirements = Mockery::mock(ProgramRequirementsInterface::class);
    $requirements->shouldReceive('getMandatorySubject')->once()->andReturn(SubjectName::EnglishLanguage);
    $requirements->shouldReceive('getMandatorySubjectLevel')->once()->andReturn(ExamLevel::Advanced);
    $requirements->shouldReceive('getElectiveSubjects')->once()->andReturn([SubjectName::FrenchLanguage, SubjectName::GermanLanguage]);

    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldReceive('findByApplicant')->once()->andReturn($requirements);

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
        makeExamResultRow(SubjectName::History, ExamLevel::Intermediate, 60),
        makeExamResultRow(SubjectName::Mathematics, ExamLevel::Intermediate, 70),
        makeExamResultRow(SubjectName::EnglishLanguage, ExamLevel::Advanced, 80),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(MissingElectiveSubjectException::class);
});

it('returns Score VO with correct basePoints and bonusPoints on happy path', function (): void {
    $requirements = Mockery::mock(ProgramRequirementsInterface::class);
    $requirements->shouldReceive('getMandatorySubject')->once()->andReturn(SubjectName::Mathematics);
    $requirements->shouldReceive('getMandatorySubjectLevel')->once()->andReturn(null);
    $requirements->shouldReceive('getElectiveSubjects')->once()->andReturn([
        SubjectName::Informatics,
        SubjectName::Physics,
        SubjectName::Biology,
        SubjectName::Chemistry,
    ]);

    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldReceive('findByApplicant')->once()->andReturn($requirements);

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $baseCalc->shouldReceive('calculate')->once()->andReturn(370);

    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);
    $bonusCalc->shouldReceive('calculate')->once()->andReturn(100);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    $applicant = makeApplicantWithExams(
        [
            makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
            makeExamResultRow(SubjectName::History, ExamLevel::Intermediate, 60),
            makeExamResultRow(SubjectName::Mathematics, ExamLevel::Advanced, 90),
            makeExamResultRow(SubjectName::Informatics, ExamLevel::Intermediate, 95),
        ],
        [
            makeBonusPointRow(LanguageCertificateType::UpperIntermediate, 'angol'),
        ],
    );

    $score = $service->calculateForApplicant($applicant);

    expect($score->basePoints)->toBe(370)
        ->and($score->bonusPoints)->toBe(100)
        ->and($score->total())->toBe(470);
});

it('throws FailedExamException before MissingGlobalMandatorySubjectException — step 1 fires first', function (): void {
    $registry = Mockery::mock(ProgramRegistryInterface::class);
    $registry->shouldNotReceive('findByApplicant');

    $baseCalc = Mockery::mock(BasePointCalculatorInterface::class);
    $bonusCalc = Mockery::mock(BonusPointCalculatorInterface::class);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    // magyar is below 20 (step 1) AND tortenelem is absent (step 2) — step 1 must win
    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 15),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(FailedExamException::class);
});
