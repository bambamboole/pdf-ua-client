<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Enums;

enum PageFormat: string
{
    case A4 = 'A4';
    case A5 = 'A5';
    case Letter = 'Letter';
    case Legal = 'Legal';

    public function heightMm(): float
    {
        return match ($this) {
            self::A4 => 297.0,
            self::A5 => 210.0,
            self::Letter => 279.4,
            self::Legal => 355.6,
        };
    }
}
