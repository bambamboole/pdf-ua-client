<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests\Fixtures;

use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class TestFixtureBlockConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        public int $level = 1,
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}
