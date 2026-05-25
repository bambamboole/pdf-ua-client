<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Enums\PageFormat;

final readonly class PageConfig
{
    public function __construct(
        public PageFormat $format = PageFormat::A4,
        public string $locale = 'de_DE',
        public SpacingConfig $margins = new SpacingConfig(20, 20, 20, 25),
        public ?PageNumbersConfig $pageNumbers = null,
    ) {}
}
