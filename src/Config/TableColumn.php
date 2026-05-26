<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Pattern;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\Align;

final readonly class TableColumn
{
    public function __construct(
        #[Title('Key')]
        #[Description('Runtime data key used for this table column.')]
        #[Pattern('^[A-Za-z][A-Za-z0-9_]*$')]
        public string $key,
        #[Title('Label')]
        #[Description('Header label rendered for this table column.')]
        public string $label,
        #[Title('Alignment')]
        #[Description('Text alignment for this table column.')]
        public ?Align $align = null,
        #[Title('Width')]
        #[Description('Column width as millimetres or a CSS width value.')]
        public ?string $width = null,
    ) {}
}
