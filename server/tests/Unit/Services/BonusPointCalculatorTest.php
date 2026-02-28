<?php

declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\LanguageCertificateType;
use App\Enums\SubjectName;
use App\Services\BonusPointCalculator;
use App\ValueObjects\ExamResult;
use App\ValueObjects\LanguageCertificate;

it('returns 50 for a single emelt exam result', function (): void {
    $calculator = new BonusPointCalculator;
    $emelt = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 50);

    expect($calculator->calculate([$emelt], []))->toBe(50);
});

it('returns 100 for two emelt exam results', function (): void {
    $calculator = new BonusPointCalculator;
    $emelt1 = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 50);
    $emelt2 = new ExamResult(SubjectName::Informatics, ExamLevel::Advanced, 80);

    expect($calculator->calculate([$emelt1, $emelt2], []))->toBe(100);
});

it('returns 0 when there are no emelt exams and no certificates', function (): void {
    $calculator = new BonusPointCalculator;
    $kozep = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 80);

    expect($calculator->calculate([$kozep], []))->toBe(0);
});

it('returns 28 for a single B2 language certificate', function (): void {
    $calculator = new BonusPointCalculator;
    $cert = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'angol');

    expect($calculator->calculate([], [$cert]))->toBe(28);
});

it('returns 40 for a single C1 language certificate', function (): void {
    $calculator = new BonusPointCalculator;
    $cert = new LanguageCertificate(LanguageCertificateType::Advanced, 'angol');

    expect($calculator->calculate([], [$cert]))->toBe(40);
});

it('deduplicates same-language certificates and keeps the highest points', function (): void {
    $calculator = new BonusPointCalculator;
    $b2 = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'angol');
    $c1 = new LanguageCertificate(LanguageCertificateType::Advanced, 'angol');

    expect($calculator->calculate([], [$b2, $c1]))->toBe(40);
});

it('counts both certificates when they are for different languages', function (): void {
    $calculator = new BonusPointCalculator;
    $b2Angol = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'angol');
    $c1Nemet = new LanguageCertificate(LanguageCertificateType::Advanced, 'nemet');

    expect($calculator->calculate([], [$b2Angol, $c1Nemet]))->toBe(68);
});

it('caps the total at 100 when emelt and certificates exceed the limit', function (): void {
    $calculator = new BonusPointCalculator;
    $emelt = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 70);
    $b2Angol = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'angol');
    $c1Nemet = new LanguageCertificate(LanguageCertificateType::Advanced, 'nemet');

    expect($calculator->calculate([$emelt], [$b2Angol, $c1Nemet]))->toBe(100);
});

it('returns exactly 100 when raw total exceeds cap', function (): void {
    $calculator = new BonusPointCalculator;
    $emelt1 = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 80);
    $emelt2 = new ExamResult(SubjectName::Informatics, ExamLevel::Advanced, 80);
    $c1 = new LanguageCertificate(LanguageCertificateType::Advanced, 'angol');

    expect($calculator->calculate([$emelt1, $emelt2], [$c1]))->toBe(100);
});

it('calculates applicant 1 scenario: 1 emelt + B2 angol + C1 nemet capped at 100', function (): void {
    $calculator = new BonusPointCalculator;
    $emelt = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 90);
    $b2Angol = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'angol');
    $c1Nemet = new LanguageCertificate(LanguageCertificateType::Advanced, 'nemet');

    expect($calculator->calculate([$emelt], [$b2Angol, $c1Nemet]))->toBe(100);
});

it('does not add bonus points for intermediate-level exam results', function (): void {
    $calculator = new BonusPointCalculator;
    $kozep = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 95);

    expect($calculator->calculate([$kozep], []))->toBe(0);
});

it('returns 0 for empty arrays', function (): void {
    $calculator = new BonusPointCalculator;

    expect($calculator->calculate([], []))->toBe(0);
});
