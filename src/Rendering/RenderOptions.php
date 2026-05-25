<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Rendering;

final readonly class RenderOptions
{
    public function __construct(
        public string $mode = 'print',
        public string $baseUrl = '',
        public string $title = 'Document',
    ) {}
}
