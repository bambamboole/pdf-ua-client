<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

readonly class BlockConfig
{
    public function __construct(
        public ?TypographyConfig $typography = null,
        public ?SpacingConfig $spacing = null,
        #[Title('Width')]
        public ?string $width = null,
        #[Title('Alignment')]
        public ?Align $align = null,
    ) {}
}
