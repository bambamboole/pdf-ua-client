<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Example
{
    public function __construct(public readonly mixed $value) {}
}
