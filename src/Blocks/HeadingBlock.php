<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\HeadingConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('heading', config: HeadingConfig::class)]
#[Title('Heading')]
#[Description('A section heading (h1–h6).')]
final readonly class HeadingBlock implements BlockInterface
{
    public function __construct(
        #[Example('Invoice 2026-001')]
        public string $text,
    ) {}

    public function render(HeadingConfig $config): string
    {
        $tag = "h{$config->level}";

        return "<{$tag}>".e($this->text)."</{$tag}>";
    }
}
