<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Enums;

enum PageFormat: string
{
    case A4 = 'A4';
    case A5 = 'A5';
    case Letter = 'Letter';
    case Legal = 'Legal';
    case ParcelLabel4x6 = 'ParcelLabel4x6';

    public function widthMm(): float
    {
        return match ($this) {
            self::A4 => 210.0,
            self::A5 => 148.0,
            self::Letter, self::Legal => 215.9,
            self::ParcelLabel4x6 => 101.6,
        };
    }

    public function heightMm(): float
    {
        return match ($this) {
            self::A4 => 297.0,
            self::A5 => 210.0,
            self::Letter => 279.4,
            self::Legal => 355.6,
            self::ParcelLabel4x6 => 152.4,
        };
    }

    public function cssSize(): string
    {
        return match ($this) {
            self::A4, self::A5, self::Letter, self::Legal => $this->value,
            self::ParcelLabel4x6 => '101.6mm 152.4mm',
        };
    }
}
