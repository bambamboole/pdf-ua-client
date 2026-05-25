<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('text')]
final class TextBlock implements BlockInterface
{
    public function __construct(public readonly string $text) {}

    public function render(BlockConfig $config): string
    {
        $paragraphs = preg_split('/\R{2,}/', $this->text) ?: [''];

        $body = '';
        foreach ($paragraphs as $para) {
            $escaped = e($para);
            $withBreaks = preg_replace('/\R/', '<br>', $escaped);
            $body .= "<p>{$withBreaks}</p>";
        }

        return $body;
    }
}
