<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final readonly class Row
{
    /**
     * @param  list<BlockInstance>  $blocks
     */
    public function __construct(
        public array $blocks,
        public ?int $gap = null,
    ) {}
}
