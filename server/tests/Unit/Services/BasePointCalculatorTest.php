<?php

declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Services\BasePointCalculator;
use App\ValueObjects\ExamResult;

it('calculates base points for typical inputs', function (): void {
    $calculator = new BasePointCalculator;
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 90);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, 95);

    expect($calculator->calculate($mandatory, $bestElective))->toBe(370);
});

it('returns exactly 400 for maximum inputs', function (): void {
    $calculator = new BasePointCalculator;
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 100);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, 100);

    expect($calculator->calculate($mandatory, $bestElective))->toBe(400);
});

it('calculates base points for minimum valid inputs', function (): void {
    $calculator = new BasePointCalculator;
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 20);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, 20);

    expect($calculator->calculate($mandatory, $bestElective))->toBe(80);
});

it('calculates base points for mixed percentages', function (int $mandatory, int $elective, int $expected): void {
    $calculator = new BasePointCalculator;
    $mandatoryResult = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $mandatory);
    $electiveResult = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, $elective);

    expect($calculator->calculate($mandatoryResult, $electiveResult))->toBe($expected);
})->with([
    '50+50=200' => [50, 50, 200],
    '75+80=310' => [75, 80, 310],
    '100+90=380' => [100, 90, 380],
]);

it('caps the result at 400 when formula exceeds 400', function (): void {
    $calculator = new BasePointCalculator;
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 100);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, 100);

    expect($calculator->calculate($mandatory, $bestElective))->toBe(400);
});
