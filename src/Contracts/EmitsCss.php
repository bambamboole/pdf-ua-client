<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Contracts;

interface EmitsCss
{
    public function cssRules(string $blockId): string;
}
