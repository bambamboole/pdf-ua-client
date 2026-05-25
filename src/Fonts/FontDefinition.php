<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Fonts;

final readonly class FontDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $family,
        public ?string $url = null,
        public ?string $weight = null,
        public string $style = 'normal',
        public string $display = 'swap',
        public string $format = 'woff2',
    ) {}
}
