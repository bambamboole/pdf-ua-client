<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\SpacerConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('spacer', config: SpacerConfig::class)]
final class SpacerBlock implements BlockInterface
{
    public function __construct() {}

    public function render(SpacerConfig $config): string
    {
        return '';
    }
}
