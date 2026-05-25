<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Length
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
    ) {}
}
