<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\ImageConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('image', config: ImageConfig::class)]
final class ImageBlock implements BlockInterface
{
    public function __construct(
        public readonly string $src,
        public readonly string $alt = '',
    ) {}

    public function render(ImageConfig $config): string
    {
        return '<img src="'.e($this->src).'" alt="'.e($this->alt).'">';
    }
}
