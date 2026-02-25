<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectName: string
{
    case HungarianLanguageAndLiterature = 'magyar nyelv és irodalom';
    case History = 'történelem';
    case Mathematics = 'matematika';
    case EnglishLanguage = 'angol nyelv';
    case GermanLanguage = 'német nyelv';
    case FrenchLanguage = 'francia nyelv';
    case ItalianLanguage = 'olasz nyelv';
    case RussianLanguage = 'orosz nyelv';
    case SpanishLanguage = 'spanyol nyelv';
    case Informatics = 'informatika';
    case Biology = 'biológia';
    case Physics = 'fizika';
    case Chemistry = 'kémia';

    /**
     * Returns the three globally mandatory subjects for all Hungarian secondary school exams.
     *
     * @return array<int, self>
     */
    public static function globallyMandatory(): array
    {
        return [
            self::HungarianLanguageAndLiterature,
            self::History,
            self::Mathematics,
        ];
    }

    /**
     * Returns true if this subject is a foreign language subject.
     */
    public function isLanguage(): bool
    {
        return in_array($this, [
            self::EnglishLanguage,
            self::GermanLanguage,
            self::FrenchLanguage,
            self::ItalianLanguage,
            self::RussianLanguage,
            self::SpanishLanguage,
        ], true);
    }
}
