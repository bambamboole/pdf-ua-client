<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('html')]
final class HtmlBlock implements BlockInterface
{
    public function __construct(public readonly string $html) {}

    public function render(BlockConfig $config): string
    {
        return $this->html;
    }
}
