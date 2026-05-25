<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;
use Bambamboole\PdfUaClient\Config\BlockConfig;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Block
{
    /**
     * @param  class-string<BlockConfig>  $config
     */
    public function __construct(
        public string $type,
        public string $config = BlockConfig::class,
    ) {}
}
