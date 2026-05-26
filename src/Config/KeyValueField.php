<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Pattern;
use Bambamboole\PdfUaClient\Attributes\Title;

final readonly class KeyValueField
{
    public function __construct(
        #[Title('Key')]
        #[Description('Runtime data key used for this row value.')]
        #[Pattern('^[A-Za-z][A-Za-z0-9_]*$')]
        public string $key,
        #[Title('Label')]
        #[Description('Label rendered in the first column.')]
        public string $label,
    ) {}
}
