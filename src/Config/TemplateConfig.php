<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

final readonly class TemplateConfig
{
    public function __construct(
        public PageConfig $page = new PageConfig,
        public TypographyConfig $typography = new TypographyConfig,
    ) {}
}
