<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

readonly class BlockConfig
{
    public function __construct(
        #[Title('Typography')]
        #[Description('Overrides the page typography for this block.')]
        public ?TypographyConfig $typography = null,
        #[Title('Spacing')]
        #[Description('Outer spacing around this block in millimetres.')]
        public ?SpacingConfig $spacing = null,
        #[Title('Width')]
        #[Description('CSS width for this block, such as 50%, 80mm, or auto.')]
        public ?string $width = null,
        #[Title('Alignment')]
        #[Description('Horizontal placement of this block within its row cell.')]
        public ?Align $align = null,
    ) {}
}
