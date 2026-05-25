<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

final class RenderContext
{
    /** @var list<string> */
    private array $css = [];

    public function css(string $fragment): void
    {
        $this->css[] = $fragment;
    }

    public function collectedCss(): string
    {
        return implode("\n", $this->css);
    }
}
