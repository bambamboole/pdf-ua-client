<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Config\DividerConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

#[Block('divider', config: DividerConfig::class)]
final class DividerBlock implements BlockInterface
{
    public function __construct() {}

    public function render(DividerConfig $config): string
    {
        return '<hr>';
    }
}
