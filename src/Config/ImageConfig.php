<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class ImageConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Title('Maximum height')]
        #[Description('Maximum rendered image height in pixels.')]
        #[Min(1)]
        #[CssRule(key: 'max-height', value: '{value}px', selector: 'img')]
        public int $maxHeight = 60,
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}
