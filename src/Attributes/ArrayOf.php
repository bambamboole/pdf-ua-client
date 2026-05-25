<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
    /** @param class-string $itemClass */
    public function __construct(public readonly string $itemClass) {}
}
