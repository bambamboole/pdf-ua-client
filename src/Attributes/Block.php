<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;
use Bambamboole\PdfUaClient\Config\BlockConfig;

#[Attribute(Attribute::TARGET_CLASS)]
final class Block
{
    /**
     * @param  class-string<BlockConfig>  $config
     */
    public function __construct(
        public readonly string $type,
        public readonly string $config = BlockConfig::class,
    ) {}
}
