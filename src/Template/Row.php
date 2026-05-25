<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final readonly class Row
{
    /**
     * @param  list<BlockInstance>  $blocks
     * @param  list<int|string>|null  $columnWidths
     */
    public function __construct(
        public array $blocks,
        public ?int $gap = null,
        public ?array $columnWidths = null,
    ) {}
}
