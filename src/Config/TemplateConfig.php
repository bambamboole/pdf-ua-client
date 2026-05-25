<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;

final readonly class TemplateConfig
{
    public function __construct(
        #[Title('Page')]
        #[Description('Document page setup.')]
        public PageConfig $page = new PageConfig,
        #[Title('Typography')]
        #[Description('Default typography inherited by blocks.')]
        public TypographyConfig $typography = new TypographyConfig,
    ) {}
}
