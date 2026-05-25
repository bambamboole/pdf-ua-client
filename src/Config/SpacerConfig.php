<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class SpacerConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Title('Height')]
        #[Description('Spacer height in millimetres.')]
        #[Min(0)]
        #[CssRule(key: 'height', value: '{value}mm')]
        public int $height = 5,
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}
