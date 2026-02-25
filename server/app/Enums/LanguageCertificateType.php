<?php

declare(strict_types=1);

namespace App\Enums;

enum LanguageCertificateType: string
{
    case UpperIntermediate = 'B2';
    case Advanced = 'C1';

    public function points(): int
    {
        return match ($this) {
            self::UpperIntermediate => 28,
            self::Advanced => 40,
        };
    }
}
