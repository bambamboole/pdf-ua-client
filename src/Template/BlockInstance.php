<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final readonly class BlockInstance
{
    /**
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $type,
        public array $props = [],
        public ?string $id = null,
        public array $config = [],
    ) {}
}
