<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\HeadingConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('heading', config: HeadingConfig::class)]
final class HeadingBlock implements BlockInterface
{
    public function __construct(public readonly string $text) {}

    public function render(HeadingConfig $config): string
    {
        $tag = "h{$config->level}";

        return "<{$tag}>".e($this->text)."</{$tag}>";
    }
}
