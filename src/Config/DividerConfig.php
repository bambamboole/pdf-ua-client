<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Pattern;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class DividerConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Title('Thickness')]
        #[Description('Line thickness in points.')]
        #[CssRule(key: 'border-top-width', value: '{value}pt')]
        public int $thickness = 1,
        #[Title('Line color')]
        #[Description('Hex color used for the divider line.')]
        #[Pattern('^#[0-9A-Fa-f]{3,8}$')]
        #[CssRule(key: 'border-top-color', value: '{value}')]
        public string $lineColor = '#d1d5db',
        #[Title('Line style')]
        #[Description('CSS border style used for the divider line.')]
        #[CssRule(key: 'border-top-style', value: '{value}')]
        public string $style = 'solid',
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}
