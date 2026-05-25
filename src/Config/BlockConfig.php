<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Enums\Align;

readonly class BlockConfig
{
    public function __construct(
        public ?TypographyConfig $typography = null,
        public ?SpacingConfig $spacing = null,
        public ?string $width = null,
        public ?Align $align = null,
    ) {}
}
