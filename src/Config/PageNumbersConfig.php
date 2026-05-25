<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

final readonly class PageNumbersConfig
{
    public function __construct(
        public string $position = 'center',
    ) {}
}
