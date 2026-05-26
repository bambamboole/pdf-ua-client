<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Config\TemplateConfig;

final readonly class Template
{
    /** @param list<Row> $rows */
    public function __construct(
        public int $version,
        public TemplateConfig $config,
        public array $rows,
        public TemplateData $data = new TemplateData,
    ) {}
}
