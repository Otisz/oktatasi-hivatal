<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\LanguageCertificateType;

final readonly class LanguageCertificate
{
    public function __construct(
        public LanguageCertificateType $type,
        public string $language,
    ) {}

    public function points(): int
    {
        return $this->type->points();
    }

    public function language(): string
    {
        return $this->language;
    }
}
