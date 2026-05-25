<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Example;

final readonly class KeyValuePair
{
    public function __construct(
        #[Example('Label')]
        public string $label,
        #[Example('Value')]
        public string $value,
    ) {}
}
