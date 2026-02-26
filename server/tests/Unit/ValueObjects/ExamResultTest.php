<?php

declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Exceptions\FailedExamException;
use App\ValueObjects\ExamResult;

it('accepts valid percentages', function (int $percentage) {
    $result = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage);
    expect($result->percentage)->toBe($percentage);
})->with([20, 50, 99, 100]);

it('throws InvalidArgumentException for out-of-range percentage', function (int $percentage) {
    expect(fn () => new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage))
        ->toThrow(\InvalidArgumentException::class);
})->with([-1, 101]);

it('throws FailedExamException for percentage below 20', function (int $percentage) {
    expect(fn () => new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage))
        ->toThrow(FailedExamException::class);
})->with([0, 1, 19]);

it('returns percentage as points', function (int $percentage) {
    $result = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage);
    expect($result->points())->toBe($percentage);
})->with([20, 75, 100]);

it('detects advanced level correctly', function (ExamLevel $level, bool $expected) {
    $result = new ExamResult(SubjectName::Mathematics, $level, 50);
    expect($result->isAdvancedLevel())->toBe($expected);
})->with([
    'intermediate' => [ExamLevel::Intermediate, false],
    'advanced' => [ExamLevel::Advanced, true],
]);

it('exposes subject, level, and percentage as public properties', function () {
    $result = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 85);
    expect($result->subject)->toBe(SubjectName::Mathematics)
        ->and($result->level)->toBe(ExamLevel::Advanced)
        ->and($result->percentage)->toBe(85);
});
