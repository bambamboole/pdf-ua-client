<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('html')]
#[Title('HTML')]
#[Description('Raw HTML escape hatch.')]
final class HtmlBlock implements BlockInterface
{
    public function __construct(
        #[Example('<p>Custom HTML</p>')]
        public readonly string $html,
    ) {}

    public function render(BlockConfig $config): string
    {
        return $this->html;
    }
}
