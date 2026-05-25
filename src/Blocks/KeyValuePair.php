<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

final readonly class KeyValuePair
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}
}
