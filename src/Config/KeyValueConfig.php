<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class KeyValueConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Title('Label width')]
        #[Description('CSS width for the first column containing labels.')]
        #[CssRule(key: 'width', value: '{value}', selector: 'td:first-child')]
        public string $labelWidth = '30mm',
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}
