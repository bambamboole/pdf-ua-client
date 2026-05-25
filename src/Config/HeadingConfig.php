<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Max;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class HeadingConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Min(1)] #[Max(6)] #[Title('Level')]
        #[Description('Heading level from 1 for the largest heading through 6 for the smallest.')]
        public int $level = 2,
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}
