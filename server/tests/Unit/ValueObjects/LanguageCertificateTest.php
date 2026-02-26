<?php

declare(strict_types=1);

use App\Enums\LanguageCertificateType;
use App\ValueObjects\LanguageCertificate;

it('returns correct points per certificate type', function (LanguageCertificateType $type, int $expectedPoints): void {
    $cert = new LanguageCertificate($type, 'angol');
    expect($cert->points())->toBe($expectedPoints);
})->with([
    'B2 upper intermediate' => [LanguageCertificateType::UpperIntermediate, 28],
    'C1 advanced' => [LanguageCertificateType::Advanced, 40],
]);

it('returns the language string via method', function (): void {
    $cert = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'német');
    expect($cert->language())->toBe('német');
});

it('exposes type and language as public properties', function (): void {
    $cert = new LanguageCertificate(LanguageCertificateType::Advanced, 'francia');
    expect($cert->type)->toBe(LanguageCertificateType::Advanced)
        ->and($cert->language)->toBe('francia');
});

it('language method returns same value as language property', function (string $lang): void {
    $cert = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, $lang);
    expect($cert->language())->toBe($cert->language);
})->with(['angol', 'német', 'francia']);
