<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Length
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
    ) {}
}
