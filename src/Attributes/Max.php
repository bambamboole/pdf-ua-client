<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Max
{
    public function __construct(public int|float $value) {}
}
