<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

final readonly class UploadedPdf
{
    public function __construct(public ?string $documentUuid) {}
}
