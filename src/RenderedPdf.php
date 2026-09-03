<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

final readonly class RenderedPdf
{
    public function __construct(
        public string $contents,
        public ?string $documentUuid,
    ) {}
}
