<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\SpacerConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('spacer', config: SpacerConfig::class)]
#[Title('Spacer')]
#[Description('Vertical spacing.')]
final class SpacerBlock implements BlockInterface
{
    public function __construct() {}

    public function render(SpacerConfig $config): string
    {
        return '';
    }
}
