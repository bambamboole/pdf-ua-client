<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Config;

use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Enums\PageNumberPosition;

final readonly class PageNumbersConfig
{
    public function __construct(
        #[Title('Enabled')]
        #[Description('Show page numbers in the page footer.')]
        public bool $enabled = false,
        #[Title('Position')]
        #[Description('Footer position used for page numbers.')]
        public PageNumberPosition $position = PageNumberPosition::Center,
    ) {}
}
