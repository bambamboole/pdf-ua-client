<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Block;

use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

final readonly class HydratedBlock
{
    public function __construct(
        public BlockInterface $block,
        public BlockConfig $config,
    ) {}

    public function render(): string
    {
        /** @var callable(BlockConfig): string $renderer */
        $renderer = [$this->block, 'render'];

        return $renderer($this->config);
    }
}
