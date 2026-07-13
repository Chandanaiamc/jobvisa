<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Support;

final class CoverLetterRulesVersion
{
    public const CURRENT = '3.1.0';

    public const STATUS_PREVIEW = 'preview';

    public const STATUS_SAVED = 'saved';

    public const STYLE_PROFESSIONAL = 'professional';

    public const STYLE_EXECUTIVE = 'executive';

    public const STYLE_GRADUATE = 'graduate';

    public const STYLE_TECHNICAL = 'technical';

    public const STYLE_CREATIVE = 'creative';

    /** @return list<string> */
    public static function styles(): array
    {
        return [
            self::STYLE_PROFESSIONAL,
            self::STYLE_EXECUTIVE,
            self::STYLE_GRADUATE,
            self::STYLE_TECHNICAL,
            self::STYLE_CREATIVE,
        ];
    }
}
