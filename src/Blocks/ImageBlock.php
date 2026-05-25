<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\ImageConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('image', config: ImageConfig::class)]
#[Title('Image')]
final readonly class ImageBlock implements BlockInterface
{
    public function __construct(
        #[Example('https://placehold.co/200x80')]
        public string $src,
        #[Example('Logo')]
        public string $alt = '',
    ) {}

    public function render(ImageConfig $config): string
    {
        return '<img src="'.e($this->src).'" alt="'.e($this->alt).'">';
    }
}
